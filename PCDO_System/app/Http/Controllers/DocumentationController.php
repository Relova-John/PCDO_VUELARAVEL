<?php

namespace App\Http\Controllers;

use App\Models\AmortizationOld;
use App\Models\CoopProgram;
use App\Models\Delinquent;
use App\Models\Resolved;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use setasign\Fpdi\Fpdi;

class DocumentationController extends Controller
{
    // Show the Documentation yearly filter of Cooperative
    public function index()
    {
        // Fetch completed, exported, and archived cooperative programs
        $coopPrograms = CoopProgram::with(['cooperative', 'program'])
            ->whereIn('program_status', ['Finished', 'Resolved'])
            ->where('exported', 1)
            ->where('archived', 1)
            ->get();

        // Group completed cooperatives by year
        $groupedByYear = $coopPrograms->groupBy(function ($coopProgram) {
            return $coopProgram->updated_at->format('Y');
        });

        // Determine range of years (from earliest record to current year)
        $minYear = $coopPrograms->min(fn ($c) => $c->updated_at->year) ?? date('Y');
        $maxYear = date('Y');

        // Create all years even if empty
        $years = collect(range($minYear, $maxYear))->map(function ($year) use ($groupedByYear) {
            $items = $groupedByYear->get($year, collect());

            return [
                'year' => $year,
                'cooperatives' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->cooperative->name ?? 'N/A',
                        'program_name' => $item->program->name ?? 'N/A',
                        'completed_at' => $item->updated_at->format('Y-m-d'),
                    ];
                })->values(),
            ];
        })->sortDesc()->values();

        return inertia('documentation/index', [
            'years' => $years,
        ]);
    }

    // Show the Full Documentation of the Selected Cooperative
    public function show($coopId)
    {
        // Load CoopProgram with related data
        $coopProgram = CoopProgram::with([
            'cooperative',
            'program',
            'coopDetails',
            'coopMemberFiles',
            'finishedChecklist',
            'programProgress',
            'delinquents',
            'resolvedItems',
        ])
            ->where('id', $coopId)
            ->whereIn('program_status', ['Finished', 'Resolved'])
            ->firstOrFail();

        // Map related data safely
        return inertia('documentation/show', [
            'cooperative' => [
                'id' => $coopProgram->cooperative->id,
                'name' => $coopProgram->cooperative->name,
                'program_id' => $coopProgram->id,
                'program_name' => $coopProgram->program->name,
                'program_status' => $coopProgram->program_status,
                'start_date' => optional($coopProgram->start_date)->format('F d, Y'),
                'completed_at' => optional($coopProgram->updated_at)->format('F d, Y'),

                // Coop Details
                'coop_details' => $coopProgram->coopDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'region' => $detail->region?->name ?? 'N/A',
                        'province' => $detail->province?->name ?? 'N/A',
                        'city' => $detail->city?->name ?? 'N/A',
                        'barangay' => $detail->barangay?->name ?? 'N/A',
                        'asset_size' => $detail->asset_size ?? 'N/A',
                        'coop_type' => $detail->coop_type ?? 'N/A',
                        'status_category' => $detail->status_category ?? 'N/A',
                        'bond_of_membership' => $detail->bond_of_membership ?? 'N/A',
                        'area_of_operation' => $detail->area_of_operation ?? 'N/A',
                        'citizenship' => $detail->citizenship ?? 'N/A',
                        'members_count' => $detail->members_count ?? 0,
                        'total_asset' => $detail->total_asset ?? 0,
                        'net_surplus' => $detail->net_surplus ?? 0,
                    ];
                }),

                // Coop Member Files (exclude file content)
                'coop_member_files' => $coopProgram->coopMemberFiles->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'filename' => $file->filename ?? '',
                        'uploaded_at' => optional($file->created_at)->format('F d, Y'),
                    ];
                }),

                // Finished Checklist
                'finished_coop_program_checklist' => $coopProgram->finishedChecklist->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'task_name' => $item->task_name ?? '',
                        'status' => $item->status ?? '',
                        'file_url' => route('documentation.checklist.file', $item->id),
                    ];
                }),

                // Program Progress
                'coop_program_progress' => $coopProgram->programProgress->map(function ($progress) {
                    return [
                        'id' => $progress->id,
                        'progress_name' => $progress->progress_name ?? '',
                        'status' => $progress->status ?? '',
                    ];
                }),

                // Delinquents
                'delinquents' => $coopProgram->delinquents->map(function ($delinquent) {
                    return [
                        'id' => $delinquent->id,
                        'name' => $delinquent->name ?? '',
                        'amount_due' => $delinquent->amount_due ?? 0,
                    ];
                }),

                // Resolved
                'resolved' => $coopProgram->resolvedItems->map(function ($resolved) {
                    return [
                        'id' => $resolved->id,
                        'issue' => $resolved->issue ?? '',
                        'resolved_at' => optional($resolved->resolved_at)->format('F d, Y'),
                        'file_url' => route('documentation.resolved.file', ['id' => $resolved->id]),
                    ];
                }),
            ],
        ]);
    }

    public function addFooterBySize(Fpdi $pdf, array $size, ?string $generatedBy = null)
    {
        // Disable auto page break
        $pdf->SetAutoPageBreak(false);

        // Footer position 18mm from bottom
        $footerY = $size['height'] - 10;
        $pdf->SetY($footerY);

        // Footer font and color
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 100, 100);

        $lineY = $footerY - 2;
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $lineY, $size['width'] - 10, $lineY);

        // Footer content
        $footerLines = [
            'Provincial Cooperative Development Office',
            'Generated on: '.now()->format('F d, Y h:i A').' | Printed by: '.($generatedBy ?? 'N/A'),
        ];

        foreach ($footerLines as $line) {
            $pdf->MultiCell(0, 4, $line, 0, 'R');
        }
    }

    // View the Amortization File in PDF
    public function amortizationFile($id)
    {
        $coopProgram = CoopProgram::find($id);
        if (! $coopProgram) {
            abort(404, 'Cooperative program not found.');
        }

        $amortization = AmortizationOld::where('coop_program_id', $id)->first();

        if (! $amortization || ! $amortization->file_content) {
            abort(404, 'Amortization schedule not found.');
        }
        $user = Auth::user()?->name ?? 'N/A';

        $tempPath = storage_path('app/amort_tmp.pdf');
        file_put_contents($tempPath, $amortization->file_content);

        $pdf = new Fpdi('P', 'mm', 'legal');

        $pageCount = $pdf->setSourceFile($tempPath);

        for ($i = 1; $i <= $pageCount; $i++) {

            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            $this->addFooterBySize($pdf, $size, $user);
        }

        @unlink($tempPath);

        $content = $amortization->file_content;

        return $this->pdfResponse($content, $coopProgram, 'Amortization_Schedule');
    }

    // View the Cooperative Details in PDF
    public function cooperativeDetailsFile($id)
    {
        $user = Auth::user();

        $coopProgram = CoopProgram::with([
            'cooperative',
            'program',
            'coopDetails.region',
            'coopDetails.province',
            'coopDetails.city',
            'coopDetails.barangay',
        ])->findOrFail($id);

        $coopDetail = $coopProgram->coopDetails->first();

        // Generate temporary PDF
        $bladePdf = Pdf::loadView('coop_details', compact('coopProgram', 'coopDetail'))
            ->setPaper('legal', 'portrait')
            ->output();

        $tempPath = storage_path('app/temp_coop_details_'.uniqid().'.pdf');
        file_put_contents($tempPath, $bladePdf);

        // Import into FPDI
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($tempPath);

        try {
            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);
                $this->addFooterBySize($pdf, $size, $user?->name ?? 'N/A');
            }
            $output = $pdf->Output('S');
        } finally {
            @unlink($tempPath);
        }

        return $this->pdfResponse($output, $coopProgram, 'Coop_Details');
    }

    // View the Resolved File that sent
    public function resolvedFile($id)
    {
        $coopProgram = CoopProgram::find($id);
        if (! $coopProgram) {
            abort(404, 'Cooperative program not found.');
        }
        // Fetch the latest resolved record
        $resolved = Resolved::where('coop_program_id', $id)->latest()->first();

        if (! $resolved) {
            abort(404, 'No resolved record found for this cooperative.');
        }

        if (empty($resolved->file_content)) {
            abort(404, 'No file attached for this resolved record.');
        }

        $user = Auth::user();

        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $resolved->file_content, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        // If it's already a PDF, add footer using FPDI
        if (str_contains($mimeType, 'pdf')) {
            // Save the original PDF to a temp file
            $tempPath = storage_path('app/temp_resolved.pdf');
            file_put_contents($tempPath, $resolved->file_content);

            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($tempPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);
                $this->addFooterBySize($pdf, $size, $user?->name ?? 'N/A');
            }

            @unlink($tempPath);

            $content = $resolved->file_content;

            return $this->pdfResponse($content, $coopProgram, 'Resolved');
        }

        // If it's an image, convert to PDF and add footer
        $extension = match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'jpg',
            str_contains($mimeType, 'png') => 'png',
            default => 'jpg',
        };

        $tempPath = storage_path('app/temp_resolved_image_'.uniqid().'.'.$extension);
        file_put_contents($tempPath, $resolved->file_content);

        $mime = mime_content_type($tempPath);
        if (! in_array($mime, ['image/jpeg', 'image/png'])) {
            $imgData = @imagecreatefromstring(file_get_contents($tempPath));
            if ($imgData === false) {
                @unlink($tempPath);
                abort(415, 'Invalid or corrupted image data.');
            }

            $convertedPath = storage_path('app/public/tmp_converted_'.uniqid().'.jpg');
            imagejpeg($imgData, $convertedPath, 90);
            imagedestroy($imgData);

            @unlink($tempPath); // delete old one
            $tempPath = $convertedPath;
        }

        // Convert the image into a PDF
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->Image($tempPath, 15, 25, 180, 230);
        $this->addFooterBySize($pdf, ['width' => $pdf->GetPageWidth(), 'height' => $pdf->GetPageHeight()], $user?->name ?? 'N/A');
        @unlink($tempPath);

        $out = $pdf->Output('S');

        return $this->pdfResponse($out, $coopProgram, 'Resolved');
    }

    // View all the Checklist File that is Uploaded in PDF
    public function checklistFile($coopProgramId)
    {
        $coopProgram = CoopProgram::with([
            'cooperative.members',
            'program.checklists',
            'finishedChecklist',
        ])->findOrFail($coopProgramId);

        $finishedChecklists = $coopProgram->finishedChecklist->keyBy('checklist_id');

        $checklists = $coopProgram->program->checklists->map(function ($item) use ($finishedChecklists, $coopProgram) {
            $finished = $finishedChecklists->get($item->id);

            $status = 'Incomplete';
            $fileContent = null;
            $mimeType = null;

            if ($finished && ! empty($finished->file_content)) {
                $status = 'Complete';
                $fileContent = $finished->file_content;
                $mimeType = $finished->mime_type ?? 'application/pdf';
            }

            if (strtolower($item->name) === 'bio data') {
                $hasMembers = $coopProgram->cooperative?->members?->isNotEmpty();
                if ($hasMembers) {
                    $status = 'Complete';
                }
            }

            return (object) [
                'task_name' => $item->name,
                'status' => $status,
                'file_content' => $fileContent,
                'mime_type' => $mimeType,
            ];
        });

        if ($checklists->isEmpty()) {
            abort(404, 'No checklist found for this cooperative program.');
        }

        // Generate a main checklist overview PDF
        $mainPdf = Pdf::loadView('coop_program_checklist', compact('checklists', 'coopProgram'))
            ->setPaper('legal', 'portrait');

        $mainPdfPath = storage_path('app/public/main_checklist.pdf');
        file_put_contents($mainPdfPath, $mainPdf->output());

        // Initialize the FPDI and add main PDF
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($mainPdfPath);
        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
            $this->addFooterBySize($pdf, $size, Auth::user()?->name ?? 'N/A');
        }

        // Append attachments
        foreach ($checklists as $item) {
            if (! empty($item->file_content)) {
                $content = $item->file_content;

                if (preg_match('/^data:(.*?);base64,/', $content, $match)) {
                    $item->mime_type = $match[1];
                    $content = base64_decode(substr($content, strpos($content, ',') + 1));
                } elseif (base64_encode(base64_decode($content, true)) === $content) {
                    $content = base64_decode($content);
                }

                $extension = str_contains($item->mime_type, 'pdf') ? 'pdf' : 'jpg';
                $tmpPath = storage_path('app/public/tmp_'.uniqid().'.'.$extension);
                file_put_contents($tmpPath, $item->file_content);

                if (str_contains($item->mime_type, 'png')) {
                    $image = @imagecreatefrompng($tmpPath);
                    if ($image) {
                        imagejpeg($image, $tmpPath.'.jpg', 90);
                        imagedestroy($image);
                        unlink($tmpPath);
                        $tmpPath = $tmpPath.'.jpg';
                        $item->mime_type = 'image/jpeg';
                    }
                }

                if (str_contains($item->mime_type, 'pdf')) {
                    $pages = $pdf->setSourceFile($tmpPath);
                    for ($p = 1; $p <= $pages; $p++) {
                        $tpl = $pdf->importPage($p);
                        $size = $pdf->getTemplateSize($tpl);

                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($tpl);
                        $this->addFooterBySize($pdf, $size, Auth::user()?->name ?? 'N/A');
                    }
                } elseif (str_contains($item->mime_type, 'image')) {
                    $pdf->AddPage();
                    $pdf->Image($tmpPath, 10, 10, 190, 260);

                    $this->addFooterBySize($pdf, ['width' => $pdf->GetPageWidth(), 'height' => $pdf->GetPageHeight()], Auth::user()?->name ?? 'N/A');
                }

                @unlink($tmpPath);
            }
        }

        @unlink($mainPdfPath);
        $out = $pdf->Output('S');

        // Stream final merged PDF
        return $this->pdfResponse($out, $coopProgram, 'Checklist');
    }

    public function memberFile($coopProgramId)
    {
        $coopProgram = CoopProgram::with(['cooperative.members', 'coopMemberFiles'])->findOrFail($coopProgramId);

        $memberFiles = $coopProgram->cooperative?->members->flatMap(fn ($m) => $m->files) ?? collect();
        $members = $coopProgram->cooperative?->members ?? collect();

        if ($memberFiles->isEmpty() && $members->isEmpty()) {
            abort(404, 'No member files or biodata found for this cooperative.');
        }

        // Create FPDI instance
        $pdf = new Fpdi;

        // ===  Add Biodata for Each Member ===
        foreach ($members as $member) {
            $data = [
                // === PERSONAL INFO ===
                'position_desired' => $member->position,
                'active_year' => $member->active_year,
                'given_name' => $member->first_name,
                'surname' => $member->last_name,
                'middle_initial' => $member->middle_name ? substr($member->middle_name, 0, 1).'.' : '',
                'date' => now()->format('F d, Y'),

                'present_address' => trim(($member->street ? $member->street.', ' : '').($member->city ?? '')),
                'present_tel' => $member->telephone,
                'permanent_address' => $member->parent_address,
                'permanent_tel' => $member->contact,

                'citizenship' => $member->citizenship,
                'birth_date' => Carbon::parse($member->birthdate)->format('F d, Y'),
                'birth_place' => $member->birthplace,
                'religion' => $member->religion,
                'age' => $member->age,
                'sex' => $member->sex,
                'civil_status' => $member->marital_status,
                'height' => $member->height ? $member->height.' cm' : '',
                'weight' => $member->weight ? $member->weight.' kg' : '',

                'spouse' => $member->spouse_name,
                'spouse_occupation' => $member->spouse_occupation,
                'spouse_age' => $member->spouse_age,
                'children' => collect([
                    $member->dependent1_name ? $member->dependent1_name.' ('.$member->dependent1_age.')' : null,
                    $member->dependent2_name ? $member->dependent2_name.' ('.$member->dependent2_age.')' : null,
                ])->filter()->join(', '),

                'father' => $member->father_name,
                'father_occupation' => $member->father_occupation,
                'father_age' => $member->father_age,
                'father_address' => $member->parent_address,

                'mother' => $member->mother_name,
                'mother_occupation' => $member->mother_occupation,
                'mother_age' => $member->mother_age,
                'mother_address' => $member->parent_address,

                'emergency_person' => $member->emergency_name,
                'emergency_tel' => $member->emergency_contact,
                'emergency_address' => $member->parent_address,

                // === EDUCATION ===
                'school_elem' => $member->elementary_name,
                'degree_elem' => $member->elementary_degree,
                'grad_elem' => $member->elementary_end,

                'school_hs' => $member->hs_name,
                'degree_hs' => $member->hs_degree,
                'grad_hs' => $member->hs_end,

                'school_college' => $member->college_name,
                'degree_college' => $member->college_degree,
                'grad_college' => $member->college_end,

                'school_voc' => $member->course_name,
                'degree_voc' => $member->course_degree,
                'grad_voc' => $member->course_end,

                'school_others' => $member->others_name,
                'degree_others' => $member->others_degree,
                'grad_others' => $member->others_end,

                'skills' => $member->course_degree,

                // === EMPLOYMENT RECORDS ===
                'job_company_1' => $member->company1_name,
                'job_occupation_1' => $member->company1_position,
                'job_period_1' => $member->company1_start && $member->company1_end
                                    ? Carbon::parse($member->company1_start)->format('M Y').' - '.Carbon::parse($member->company1_end)->format('M Y')
                                    : '',
                'job_earnings_1' => $member->company1_rfl,

                'job_company_2' => $member->company2_name,
                'job_occupation_2' => $member->company2_position,
                'job_period_2' => $member->company2_start && $member->company2_end
                                    ? Carbon::parse($member->company2_start)->format('M Y').' - '.Carbon::parse($member->company2_end)->format('M Y')
                                    : '',
                'job_earnings_2' => $member->company2_rfl,

                'job_company_3' => $member->company3_name,
                'job_occupation_3' => $member->company3_position,
                'job_period_3' => $member->company3_start && $member->company3_end
                                    ? Carbon::parse($member->company3_start)->format('M Y').' - '.Carbon::parse($member->company3_end)->format('M Y')
                                    : '',
                'job_earnings_3' => $member->company3_rfl,

                // === CHARACTER REFERENCES ===
                'ref_name_1' => $member->ref1_name,
                'ref_occupation_1' => $member->ref1_position,
                'ref_address_1' => $member->ref1_company,
                'ref_tel_1' => $member->ref1_contact,

                'ref_name_2' => $member->ref2_name,
                'ref_occupation_2' => $member->ref2_position,
                'ref_address_2' => $member->ref2_company,
                'ref_tel_2' => $member->ref2_contact,
            ];

            // Generate temporary PDF from biodata view
            $biodataPdf = Pdf::loadView('bio_data', $data)
                ->setPaper('legal', 'portrait')
                ->output();

            $tempPath = storage_path("app/temp_biodata_{$member->id}.pdf");
            file_put_contents($tempPath, $biodataPdf);

            // Merge biodata into main PDF
            $pages = $pdf->setSourceFile($tempPath);
            for ($p = 1; $p <= $pages; $p++) {
                $tpl = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                $this->addFooterBySize($pdf, $size, Auth::user()?->name ?? 'N/A');
            }

            unlink($tempPath);
        }

        // Merge Existing Uploaded Files
        foreach ($memberFiles as $file) {
            $filePath = storage_path('app/private/'.$file->file_path);
            if (! file_exists($filePath)) {
                continue;
            }

            $mime = mime_content_type($filePath);

            if (str_contains($mime, 'pdf')) {
                $pages = $pdf->setSourceFile($filePath);
                for ($p = 1; $p <= $pages; $p++) {
                    $tpl = $pdf->importPage($p);
                    $size = $pdf->getTemplateSize($tpl);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl);

                    $this->addFooterBySize($pdf, $size, Auth::user()?->name ?? 'N/A');
                }

            } elseif (str_contains($mime, 'image')) {
                $pdf->AddPage();
                $pdf->Image($filePath, 15, 25, 180, 230);

                $this->addFooterBySize($pdf, ['width' => $pdf->GetPageWidth(), 'height' => $pdf->GetPageHeight()], Auth::user()?->name ?? 'N/A');

            }
        }

        $out = $pdf->Output('S');

        return $this->pdfResponse($out, $coopProgram, 'Members_Biodata');
    }

    public function delinquentReport($coopProgramId, $forMerge = false)
    {
        $user = Auth::user();

        $coopProgram = CoopProgram::with(['cooperative'])->findOrFail($coopProgramId);
        $coop = $coopProgram->cooperative;

        // chairman
        $chairman = $coop->members->where('position', 'Chairman')->first();
        $chairmanFullName = $chairman
            ? trim("{$chairman->first_name} {$chairman->middle_initial} {$chairman->last_name}")
            : 'N/A';

        // treasurer
        $treasurer = $coop->members->where('position', 'Treasurer')->first();
        $treasurerFullName = $treasurer
            ? trim("{$treasurer->first_name} {$treasurer->middle_initial} {$treasurer->last_name}")
            : 'N/A';

        // manager
        $manager = $coop->members->where('position', 'Manager')->first();
        $managerFullName = $manager
            ? trim("{$manager->first_name} {$manager->middle_initial} {$manager->last_name}")
            : 'N/A';

        $details = $coop->details ?? null;
        $province = $details?->province?->name ?? null;
        $city = $details?->city?->name ?? null;
        $fulladdress = trim("{$province}, {$city}");
        $contact = $coopProgram->number ?? 'N/A';

        $delinquents = Delinquent::where('coop_program_id', $coopProgramId)->get();

        // Generate Blade PDF first
        $bladePdf = PDF::loadView('delinquent_report', [
            'coopProgram' => $coopProgram,
            'coop' => $coop,
            'address' => $fulladdress ?: 'N/A',
            'chairman' => $chairmanFullName,
            'treasurer' => $treasurerFullName,
            'manager' => $managerFullName,
            'contact' => $contact,
            'delinquents' => $delinquents,
        ])->setPaper('legal', 'portrait')
            ->output();

        if ($forMerge) {
            // Return raw PDF for merging
            return $bladePdf;
        }

        // Save temporary file
        $tempPath = storage_path('app/temp_delinquent_'.uniqid().'.pdf');
        file_put_contents($tempPath, $bladePdf);

        // Import into FPDI for footer
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($tempPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            $this->addFooterBySize($pdf, $size, $user?->name ?? 'N/A');
        }

        @unlink($tempPath);

        $output = $pdf->output();

        return $this->pdfResponse($output, $coopProgram, 'Delinquent_Report');
    }

    public function progressReportFile($coopProgramId)
    {
        $coopProgram = CoopProgram::with(['programProgress'])->findOrFail($coopProgramId);
        $progressReports = $coopProgram->programProgress;

        if ($progressReports->isEmpty()) {
            abort(404, 'No progress reports found for this cooperative.');
        }

        $user = Auth::user();

        // Create FPDI instance
        $pdf = new Fpdi;

        foreach ($progressReports as $progress) {
            $pdf->AddPage();

            // Disable auto page break
            $pdf->SetAutoPageBreak(false);

            // Title
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $progress->title), 0, 1, 'C');
            $pdf->Ln(5);

            // Description
            $pdf->SetFont('Arial', '', 11);
            $pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $progress->description ?? 'No description.'), 0, 'L');
            $pdf->Ln(5);

            // Handle image file
            if ($progress->file_content && str_contains($progress->mime_type ?? '', 'image')) {
                $decoded = base64_decode($progress->file_content);
                $tmpPath = storage_path('app/temp_'.uniqid().'.jpg');
                file_put_contents($tmpPath, $decoded);

                // Adjust placement or size as needed
                $pdf->Image($tmpPath, 20, 60, 170);
                unlink($tmpPath);
            }
            // Footer
            $size = ['width' => $pdf->GetPageWidth(), 'height' => $pdf->GetPageHeight()];
            $this->addFooterBySize($pdf, $size, $user?->name ?? 'N/A');
        }
        $output = $pdf->Output('S');

        // Output PDF inline to browser/iframe
        return $this->pdfResponse($output, $coopProgram, 'Progress_Report');

    }

    public function allFile($coopProgramId)
    {
        // Fetch all related data
        $coopProgram = CoopProgram::with([
            'cooperative.members',
            'coopMemberFiles',
            'programProgress',
            'program.checklists',
            'finishedChecklist',
            'delinquents',
            'coopDetails',
            'resolvedItems',
        ])->findOrFail($coopProgramId);

        $pdf = new Fpdi;
        $tempFiles = [];

        // Cooperative Details
        $coopDetail = $coopProgram->coopDetails->first();
        if ($coopDetail) {
            $detailsPdf = Pdf::loadView('coop_details', compact('coopProgram', 'coopDetail'))
                ->setPaper('legal', 'portrait')
                ->output();

            $detailsPath = storage_path('app/temp_details_'.uniqid().'.pdf');
            file_put_contents($detailsPath, $detailsPdf);
            $tempFiles[] = $detailsPath;
        }

        // Amortization File
        $amortization = AmortizationOld::where('coop_program_id', $coopProgramId)->first();
        if ($amortization && $amortization->file_content) {
            $amortPath = storage_path('app/temp_amortization_'.uniqid().'.pdf');
            file_put_contents($amortPath, $amortization->file_content);
            $tempFiles[] = $amortPath;
        }

        // Checklist Files
        if ($coopProgram->program && $coopProgram->program->checklists->isNotEmpty()) {
            $checklistResponse = app(self::class)->checklistFile($coopProgramId);
            $checklistPdf = $checklistResponse->getContent();
            $checklistPath = storage_path('app/temp_checklist_'.uniqid().'.pdf');
            file_put_contents($checklistPath, $checklistPdf);
            $tempFiles[] = $checklistPath;
        }

        // Member Files
        if ($coopProgram->cooperative && $coopProgram->cooperative->members->isNotEmpty()) {
            $memberResponse = app(self::class)->memberFile($coopProgramId);
            $memberPdf = $memberResponse->getContent();
            $memberPath = storage_path('app/temp_member_'.uniqid().'.pdf');
            file_put_contents($memberPath, $memberPdf);
            $tempFiles[] = $memberPath;
        }

        // Delinquent Report
        $delinquentPdf = app(self::class)->delinquentReport($coopProgramId, true);
        $delinquentPath = storage_path('app/temp_delinquent_'.uniqid().'.pdf');
        file_put_contents($delinquentPath, $delinquentPdf);
        $tempFiles[] = $delinquentPath;

        // === Progress Reports ===
        if ($coopProgram->programProgress->isNotEmpty()) {
            $progressResponse = app(self::class)->progressReportFile($coopProgramId);
            $progressPdf = $progressResponse->getContent();
            $progressPath = storage_path('app/temp_progress_'.uniqid().'.pdf');
            file_put_contents($progressPath, $progressPdf);
            $tempFiles[] = $progressPath;
        }

        // Resolved Files
        if ($coopProgram->resolvedItems->isNotEmpty()) {
            foreach ($coopProgram->resolvedItems as $resolved) {
                if (! empty($resolved->file_content)) {
                    $resolvedPath = storage_path('app/temp_resolved_'.uniqid().'.pdf');
                    file_put_contents($resolvedPath, $resolved->file_content);
                    $tempFiles[] = $resolvedPath;
                }
            }
        }

        // Merge Everything
        foreach ($tempFiles as $path) {
            if (! file_exists($path)) {
                continue;
            }

            try {
                $pageCount = $pdf->setSourceFile($path);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplIdx = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplIdx);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);

                    $this->addFooterBySize($pdf, $size, Auth::user()?->name ?? 'N/A');
                }
            } catch (\Exception $e) {
                continue; // Skip invalid files
            }
        }

        // Cleanup temp files
        foreach ($tempFiles as $f) {
            @unlink($f);
        }
        $out = $pdf->Output('S');

        // Stream final merged PDF
        return $this->pdfResponse($out, $coopProgram, 'Full_Report');
    }

    private function pdfResponse(string $pdfContent, CoopProgram $coopProgram, string $suffix): Response
    {
        $disposition = request()->boolean('download')
            ? 'attachment'
            : 'inline';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition.'; filename="'.$this->generateFileName($coopProgram, $suffix).'"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'public, max-age=0, must-revalidate')
            ->header('Accept-Ranges', 'bytes')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function generateFileName(CoopProgram $coopProgram, string $suffix)
    {
        $coopName = $coopProgram->cooperative?->name ?? 'Cooperative';
        $programName = $coopProgram->program?->name ?? 'Program';
        $createdDate = optional($coopProgram->created_at)->format('Y-m-d') ?? date('Y-m-d');

        // Clean and safe filename
        $safeCoop = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coopName);
        $safeProgram = preg_replace('/[^A-Za-z0-9_\-]/', '_', $programName);
        $safeSuffix = preg_replace('/[^A-Za-z0-9_\-]/', '_', $suffix);

        return "{$safeCoop}_{$safeProgram}_{$createdDate}_{$safeSuffix}.pdf";
    }
}
