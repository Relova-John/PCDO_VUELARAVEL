<?php

namespace App\Console\Commands;

use App\Models\AmortizationOld;
use App\Models\CoopProgram;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class ExportCompletedLoans extends Command
{
    protected $signature = 'export:completed-loans';

    protected $description = 'Export fully paid cooperative loans to CSV';

    public function handle()
    {
        try {
            $this->info('Running export of completed cooperative loans...');

            // Load finished cooperative programs
            $coopPrograms = CoopProgram::with([
                'amortizationSchedules',
                'program',
                'cooperative.details.province',
                'cooperative.details.city',
                'cooperative.members',
            ])
                ->whereIn('program_status', ['Finished', 'Resolved'])
                ->get();

            if ($coopPrograms->isEmpty()) {
                $this->info('No finished/resolved cooperative programs found.');

                return 0;
            }

            foreach ($coopPrograms as $coopProgram) {
                $schedules = $coopProgram->amortizationSchedules()
                    ->select('id', 'due_date', 'installment', 'date_paid', 'amount_paid', 'status', 'notes', 'receipt_image')
                    ->orderBy('due_date')
                    ->get();
                if ($schedules->isEmpty()) {
                    continue;
                }

                // Ensure all payments are fully paid
                $allPaid = $schedules->every(fn ($s) => in_array($s->status, ['Paid', 'Resolved']));
                if (! $allPaid) {
                    continue;
                }

                $coop = $coopProgram->cooperative;

                // chairman
                $chairman = $coopProgram->cooperative->members
                    ->where('position', 'Chairman')
                    ->first();
                $chairmanFullName = $chairman
                    ? trim("{$chairman->first_name} {$chairman->middle_initial} {$chairman->last_name}")
                    : 'N/A';

                // treasurer
                $treasurer = $coopProgram->cooperative->members
                    ->where('position', 'Treasurer')
                    ->first();
                $treasurerFullName = $treasurer
                    ? trim("{$treasurer->first_name} {$treasurer->middle_initial} {$treasurer->last_name}")
                    : 'N/A';

                // manager
                $manager = $coopProgram->cooperative->members
                    ->where('position', 'Manager')
                    ->first();
                $managerFullName = $manager
                    ? trim("{$manager->first_name} {$manager->middle_initial} {$manager->last_name}")
                    : 'N/A';

                $details = $coop->details ?? null;

                $province = $details?->province?->name ?? null;
                $city = $details?->city?->name ?? null;

                $fulladdress = trim("{$province},{$city}");

                $contact = $coopProgram->number ?? 'N/A';

                $project = $coopProgram->project ?? 'N/A';

                // Generate PDF directly from Blade view
                $pdf = Pdf::loadView('amortization_schedule', [
                    'coop' => $coop,
                    'coopProgram' => $coopProgram,
                    'schedules' => $schedules,
                    'address' => $fulladdress,
                    'contact' => $contact,
                    'project' => $project,
                    'chairman' => $chairmanFullName,
                    'treasurer' => $treasurerFullName,
                    'manager' => $managerFullName,
                ])
                    ->setPaper('legal', 'portrait')
                    ->setOptions([
                        'dpi' => 80, // lower DPI = more fits on one page
                        'defaultFont' => 'sans-serif',
                        'isHtml5ParserEnabled' => true,
                        'isRemoteEnabled' => true,
                    ]);

                // Get the binary content (for BLOB)
                $pdfBinary = $pdf->output();

                // Save binary PDF directly into the `Old` table
                AmortizationOld::create([
                    'coop_program_id' => $coopProgram->id,
                    'file_content' => $pdfBinary, // this is the BLOB column
                ]);

                // Mark program as exported
                $coopProgram->exported = true;
                $coopProgram->save();

                // Optionally clear schedules
                $coopProgram->amortizationSchedules()->delete();

                $this->info("Exported PDF for {$coop->name} saved in database (BLOB)");
            }

            $this->info('All fully paid cooperative loans have been exported successfully!');

            return 0;

        } catch (\Exception $e) {
            \Log::error('ExportCompletedLoans failed: '.$e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);
            $this->error('ExportCompletedLoans failed: '.$e->getMessage());

            return 1;
        }
    }
}
