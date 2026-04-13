<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserMail;
use App\Models\AccessControl;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccessControlController extends Controller
{
    public function index(Request $request): Response
    {
        $regions = Region::query()
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        $provinces = Province::query()
            ->select('code', 'name', 'region_code')
            ->orderBy('name')
            ->get();

        $cities = City::query()
            ->select('code', 'name', 'region_code', 'province_code')
            ->orderBy('name')
            ->get();

        $barangays = Barangay::query()
            ->select('code', 'name', 'city_code')
            ->orderBy('name')
            ->get();

        $search = $request->query('search', '');
        $logsPage = $request->query('logs_page', 1);
        $usersPage = $request->query('users_page', 1);

        $users = User::with('roles:id,name')
            ->select('id', 'name', 'email', 'created_at', 'is_active')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'users_page', $usersPage)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->map(fn ($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                    ]),
                    'created_at' => $user->created_at,
                    'active' => $user->is_active,
                ];
            });

        $roles = Role::select('id', 'name')->orderBy('name')->get();

        $recentLogs = DB::table('sync_logs')
            ->select(
                'id',
                'user_name',
                'table_name',
                'user_id',
                'operation',
                'record_id',
                DB::raw("CONVERT_TZ(executed_at, '+00:00', '+08:00') as executed_at")
            )
            ->orderByDesc('executed_at')
            ->paginate(10, ['*'], 'logs_page', $logsPage)
            ->withQueryString();

        return Inertia::render('admin/AccessControl/Index', [
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'users' => $users,
            'roles' => $roles,
            'recentLogs' => [
                'data' => $recentLogs->items(),
                'current_page' => $recentLogs->currentPage(),
                'last_page' => $recentLogs->lastPage(),
                'prev_page_url' => $recentLogs->previousPageUrl(),
                'next_page_url' => $recentLogs->nextPageUrl(),
                'links' => $recentLogs->linkCollection(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function createUsers(Request $request)
    {
        $authUser = $request->user();

        if ($authUser->hasRole('superadmin')) {
            $allowedRoles = ['admin', 'officerI', 'officerII'];
        } elseif ($authUser->hasRole('admin')) {
            $allowedRoles = ['officerI', 'officerII'];
        } elseif ($authUser->hasRole('officerI')) {
            $allowedRoles = ['officerII'];
        } else {
            return back()->with('error', 'User not authorized.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in($allowedRoles)],
            'city_code' => ['nullable', 'exists:cities,code'],
            'city_codes' => ['nullable', 'array'],
            'city_codes.*' => ['required', 'exists:cities,code'],
        ]);

        if ($data['role'] === 'officerI') {
            if (empty($data['city_codes']) || ! is_array($data['city_codes'])) {
                return back()->with('error', 'Officer I must have at least one assigned municipality.')->withInput();
            }

            if (! empty($data['city_code'])) {
                return back()->with('error', 'Officer I should use multiple municipality assignment.')->withInput();
            }
        }

        if ($data['role'] === 'officerII') {
            if (empty($data['city_code'])) {
                return back()->with('error', 'Officer II must have one assigned municipality.')->withInput();
            }

            if (! empty($data['city_codes'])) {
                return back()->with('error', 'Officer II should only have one municipality assignment.')->withInput();
            }
        }

        if ($data['role'] === 'admin') {
            if (! empty($data['city_code']) || ! empty($data['city_codes'])) {
                return back()->with('error', 'Admin should not have municipality assignments.')->withInput();
            }
        }

        $role = Role::where('name', $data['role'])->first();

        if (! $role) {
            return back()->with('error', 'Selected role is invalid.')->withInput();
        }

        $plainPassword = bin2hex(random_bytes(8));
        $user = null;

        DB::transaction(function () use ($data, $authUser, $plainPassword, $role, &$user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($plainPassword),
                'is_active' => true,
                'created_by' => $authUser->id,
            ]);

            $user->roles()->sync([$role->id]);

            if ($data['role'] === 'officerII') {
                $user->locationAccesses()->create([
                    'city_code' => $data['city_code'],
                ]);
            }

            if ($data['role'] === 'officerI') {
                foreach (array_unique($data['city_codes']) as $cityCode) {
                    $user->locationAccesses()->create([
                        'city_code' => $cityCode,
                    ]);
                }
            }
        });

        Mail::to($data['email'])->send(new UserMail($plainPassword));

        return redirect()->route('admin.access-control.index')->with('success', 'User created successfully.');
    }

    public function getLogChanges($id)
    {
        $log = DB::table('sync_logs')->select('changes')->where('id', $id)->first();

        if (! $log) {
            return response()->json(['error' => 'Log is not found.'], 404);
        }

        return response()->json([
            'changes' => $log->changes,
        ]);
    }

    public function close(AccessControl $accessControl)
    {
        $accessControl->update([
            'is_active' => false,
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Access control closed successfully.');
    }

    public function reopen(AccessControl $accessControl)
    {
        $accessControl->update([
            'is_active' => true,
            'closed_at' => null,
        ]);

        return back()->with('success', 'Access control reopened successfully.');
    }

    public function downloadQr(AccessControl $accessControl)
    {
        $payload = route('qr.resolve', ['token' => $accessControl->token]);
        $svg = $this->makeQrSvg($payload);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'inline; filename="qr.svg"',
        ]);
    }

    public function downloadStaticFormQr()
    {
        $payload = url('/form');
        $svg = $this->makeQrSvg($payload);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'inline; filename="form.svg"',
        ]);
    }

    protected function makeQrSvg(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($payload);
    }

    protected function resolveLocationName(AccessControl $accessControl): ?string
    {
        if ($accessControl->barangay_code) {
            return Barangay::where('code', $accessControl->barangay_code)->value('name');
        }

        if ($accessControl->city_code) {
            return City::where('code', $accessControl->city_code)->value('name');
        }

        if ($accessControl->province_code) {
            return Province::where('code', $accessControl->province_code)->value('name');
        }

        if ($accessControl->region_code) {
            return Region::where('code', $accessControl->region_code)->value('name');
        }

        return null;
    }

    protected function generateCode(string $type): string
    {
        $prefix = $type === 'access' ? 'OFFICER' : 'FORM';

        return $prefix . '-' . strtoupper(Str::random(8));
    }

    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = true;
        $user->save();

        return redirect()->route('admin.access-control.index')->with('success', 'User activated successfully.');
    }

    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = false;
        $user->save();

        return redirect()->route('admin.access-control.index')->with('success', 'User deactivated successfully.');
    }

    public function changeRole(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->hasRole('superadmin')) {
            $allowedRoles = ['admin', 'officerI', 'officerII'];
        } elseif ($authUser->hasRole('admin')) {
            $allowedRoles = ['officerI', 'officerII'];
        } elseif ($authUser->hasRole('officerI')) {
            $allowedRoles = ['officerII'];
        } else {
            return back()->with('error', 'User not authorized.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
            'city_code' => ['nullable', 'exists:cities,code'],
            'city_codes' => ['nullable', 'array'],
            'city_codes.*' => ['required', 'exists:cities,code'],
        ]);

        if ($data['role'] === 'officerI') {
            if (empty($data['city_codes']) || ! is_array($data['city_codes'])) {
                return back()->with('error', 'Officer I must have at least one assigned municipality.')->withInput();
            }

            if (! empty($data['city_code'])) {
                return back()->with('error', 'Officer I should use multiple municipality assignment.')->withInput();
            }
        }

        if ($data['role'] === 'officerII') {
            if (empty($data['city_code'])) {
                return back()->with('error', 'Officer II must have one assigned municipality.')->withInput();
            }

            if (! empty($data['city_codes'])) {
                return back()->with('error', 'Officer II should only have one municipality assignment.')->withInput();
            }
        }

        if ($data['role'] === 'admin') {
            if (! empty($data['city_code']) || ! empty($data['city_codes'])) {
                return back()->with('error', 'Admin should not have municipality assignments.')->withInput();
            }
        }

        $role = Role::where('name', $data['role'])->first();

        if (! $role) {
            return back()->with('error', 'Selected role is invalid.')->withInput();
        }

        DB::transaction(function () use ($user, $data, $role) {
            $user->roles()->sync([$role->id]);
            $user->locationAccesses()->delete();

            if ($data['role'] === 'officerII') {
                $user->locationAccesses()->create([
                    'city_code' => $data['city_code'],
                ]);
            }

            if ($data['role'] === 'officerI') {
                foreach (array_unique($data['city_codes']) as $cityCode) {
                    $user->locationAccesses()->create([
                        'city_code' => $cityCode,
                    ]);
                }
            }
        });

        return back()->with('success', 'User role changed successfully.');
    }
}