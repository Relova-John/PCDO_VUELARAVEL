<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CoopController extends Controller
{
    public function index()
    {
        $cooperative = auth()->user()
            ->cooperatives()
            ->with([
                'details',
                'programs.checklist',
                'programs.amortizationSchedules',
                'programs.olds',
                'programs.program',
                'programs.program.checklists',
            ])
            ->get();

        return Inertia::render('coop/Dashboard', [
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'link' => route('coop.dashboard')],
            ],
            'cooperative' => $cooperative,
        ]);
    }
}