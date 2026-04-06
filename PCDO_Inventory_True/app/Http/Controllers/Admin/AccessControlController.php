<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessControl;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;

class AccessControlController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/AccessControl/Index', [
            'regions' => Region::query()
                ->select('code', 'name')
                ->orderBy('name')
                ->get(),

            'provinces' => Province::query()
                ->select('code', 'name', 'region_code')
                ->orderBy('name')
                ->get(),

            'cities' => City::query()
                ->select('code', 'name', 'region_code', 'province_code')
                ->orderBy('name')
                ->get(),

            'barangays' => Barangay::query()
                ->select('code', 'name', 'city_code')
                ->orderBy('name')
                ->get(),

            'accessControls' => AccessControl::query()
                ->select([
                    'id',
                    'type',
                    'token',
                    'region_code',
                    'province_code',
                    'city_code',
                    'barangay_code',
                    'code',
                    'expires_at',
                    'one_time',
                    'max_uses',
                    'is_active',
                ])
                ->where('is_active', true)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:access,form'],
            'code' => ['required', 'string'],
            'one_time' => ['required', 'boolean'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'region_code' => ['nullable', 'string'],
            'province_code' => ['nullable', 'string'],
            'city_code' => ['nullable', 'string'],
            'barangay_code' => ['nullable', 'string'],
        ]);

        $existing = AccessControl::query()->where([
            'type' => $validated['type'],
            'city_code' => $validated['city_code'] ?: null,
            'barangay_code' => $validated['barangay_code'] ?: null,
        ])->first();

        if ($existing) {
            User::query()
                ->where('access_control_id', $existing->id)
                ->update([
                    'access_control_id' => null,
                    'region_code' => null,
                    'province_code' => null,
                    'city_code' => null,
                    'barangay_code' => null,
                ]);
        }

        AccessControl::updateOrCreate(
            [
                'type' => $validated['type'],
                'city_code' => $validated['city_code'] ?: null,
                'barangay_code' => $validated['barangay_code'] ?: null,
            ],
            [
                'created_by' => auth()->id(),
                'token' => Str::random(64),
                'code' => $validated['code'],
                'is_active' => true,
                'one_time' => $validated['one_time'],
                'max_uses' => $validated['max_uses'],
                'expires_at' => $validated['expires_at'],
                'region_code' => $validated['region_code'] ?: null,
                'province_code' => $validated['province_code'] ?: null,
                'city_code' => $validated['city_code'] ?: null,
                'barangay_code' => $validated['barangay_code'] ?: null,
                'closed_at' => null,
                'used_count' => 0,
                'last_used_at' => null,
            ]
        );

        return back()->with('success', 'Access control saved.');
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
}