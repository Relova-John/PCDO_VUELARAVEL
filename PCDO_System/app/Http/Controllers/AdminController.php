<?php

namespace App\Http\Controllers;

use App\Mail\UserMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $logsPage = $request->query('logs_page', 1);
        $usersPage = $request->query('users_page', 1);

        $users = User::with('roles:id,name')
            ->select('id', 'name', 'email', 'created_at', 'active')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
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
                    'roles' => $user->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
                    'created_at' => $user->created_at,
                    'active' => $user->active,
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

        return Inertia::render('admin/Dashboard', [
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
            'filters' => ['search' => $search],
        ]);
    }

    public function getLogChanges($id)
    {
        $log = DB::table('sync_logs')->select('changes')->where('id', $id)->first();

        if (! $log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        return response()->json([
            'changes' => $log->changes,
        ]);
    }

    public function storeUser(Request $request)
    {
        $authUser = $request->user();

        if ($authUser->hasRole('superadmin')) {
            $allowedRoles = ['admin', 'officer'];
        } elseif ($authUser->hasRole('admin')) {
            $allowedRoles = ['officer'];
        } else {
            return back()->with('error', 'User not authorized.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $data['password'] = bin2hex(random_bytes(8));

        Mail::to($data['email'])->send(new UserMail($data['password']));

        $data['active'] = true;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'active' => $data['active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        return back()->with('success', 'User created successfully.');
    }

    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $user->active = true;
        $user->save();

        return back()->with('success', 'User activated successfully.');
    }

    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);
        $user->active = false;
        $user->save();

        return back()->with('success', 'User deactivated successfully.');
    }

    public function changeRole(Request $request, User $user)
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('superadmin')) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,officer'],
        ]);

        if ($authUser->id === $user->id) {
            abort(403);
        }

        $user->syncRoles([$validated['role']]);

        return back();
    }
}
