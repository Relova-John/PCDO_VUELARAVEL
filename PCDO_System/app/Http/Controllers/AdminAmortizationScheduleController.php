<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CoopProgram;

class AdminAmortizationScheduleController extends Controller
{
    public function index()
    {
        $loans = CoopProgram::with(['program', 'cooperative', 'amortizationSchedules'])
            ->withCount('amortizationSchedules')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->cooperative?->id ?? 'N/A',
                'cooperative_name' => $p->cooperative?->name ?? 'N/A',
                'program_name' => $p->program?->name ?? 'N/A',
                'loan_amount' => $p->loan_amount ?? 0,
                'status' => $p->program_status ?? 'N/A',
                'has_schedule' => $p->amortization_schedules_count > 0,
                'coop_program_id' => $p->id,
                'next_due_date' => optional(
                    $p->amortizationSchedules->where('status', '!=', 'Paid')->sortBy('due_date')->first()
                )->due_date?->format('Y-m-d') ?? 'N/A',
            ]);

        return Inertia::render('admin/payments/index', [
            'coopPrograms' => $loans,
        ]);
    }

    public function show($coopProgramId)
    {
        $coopProgram = CoopProgram::with('cooperative', 'program', 'amortizationSchedules')
            ->findOrFail($coopProgramId);

        $firstSchedule = $coopProgram->amortizationSchedules()->orderBy('due_date')->first();
        $lastSchedule = $coopProgram->amortizationSchedules()->orderByDesc('due_date')->first();

        $startDate = optional($firstSchedule?->due_date)->format('Y-m-d')
            ?? optional($coopProgram->start_date)->format('Y-m-d')
            ?? now()->format('Y-m-d');

        $gracePeriod = $coopProgram->with_grace ?? 0;
        $termMonths = max(($coopProgram->program?->term_months ?? 0) - $gracePeriod, 0);

        return Inertia::render('admin/payments/amortization', [
            'coopProgram' => [
                'id' => $coopProgram->id,
                'cooperative_name' => $coopProgram->cooperative?->name ?? 'N/A',
                'program_name' => $coopProgram->program?->name ?? 'N/A',
                'loan_amount' => $coopProgram->loan_amount ?? 0,
                'status' => $coopProgram->program_status ?? 'N/A',
                'program_status' => $coopProgram->program_status ?? 'N/A',
                'resolved' => $coopProgram->program_status === 'Resolved',
                'schedules' => $coopProgram->amortizationSchedules->map(fn ($s) => [
                    'id' => $s->id,
                    'due_date' => optional($s->due_date)->format('Y-m-d'),
                    'current_balance' => $s->current_balance ?? 0,
                    'penalty_amount' => $s->penalty_amount ?? 0,
                    'amount_paid' => $s->amount_paid ?? 0,
                    'balance' => $s->balance ?? $s->current_balance ?? 0,
                    'is_paid' => $s->status === 'Paid',
                    'status' => $s->status ?? 'Unpaid',
                ]),
                'start_date' => $startDate,
                'grace_period' => $gracePeriod,
                'term_months' => $termMonths,
                'expected_end_date' => optional($lastSchedule?->due_date)->format('Y-m-d'),
            ],
        ]);
    }
}
