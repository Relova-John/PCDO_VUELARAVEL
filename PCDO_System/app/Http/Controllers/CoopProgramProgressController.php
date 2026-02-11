<?php

namespace App\Http\Controllers;

use App\Models\CoopProgram;
use App\Models\CoopProgramProgress;
use App\Models\Programs;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CoopProgramProgressController extends Controller
{
    // Show the progress upload page (Inertia + Vue)

    public function create(Programs $program)
    {
        $coopPrograms = CoopProgram::with('cooperative')
            ->where('program_id', $program->id)
            ->get();

        // Send data to progress.vue
        return Inertia::render('programs/progress', [
            'program' => $program,
            'coopPrograms' => $coopPrograms,
        ]);
    }

    // Store a new progress report
    public function store(Request $request, Programs $program)
    {
        $data = $request->validate([
            'coop_program_id' => 'required|exists:coop_programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $fileName = null;
        $mimeType = null;
        $fileContent = null;

        if ($request->hasFile('file')) {
            $files = is_array($request->file('file'))
                ? $request->file('file')
                : [$request->file('file')];

            $count = count($files);
            $manager = new ImageManager(new Driver);

            // Fixed canvas size
            $canvasWidth = 1800;
            $canvasHeight = 1200;

            if ($count > 1) {
                // Auto grid layout that fills the canvas
                $cols = ceil(sqrt($count));
                $rows = ceil($count / $cols);

                // Adjust if too much empty space
                while (($cols - 1) * $rows >= $count) {
                    $cols--;
                }

                $cellWidth = $canvasWidth / $cols;
                $cellHeight = $canvasHeight / $rows;

                // Create blank canvas
                $canvas = $manager->create($canvasWidth, $canvasHeight);

                foreach ($files as $index => $file) {
                    $x = ($index % $cols) * $cellWidth;
                    $y = floor($index / $cols) * $cellHeight;

                    $img = $manager->read($file)->cover($cellWidth, $cellHeight);
                    $canvas->place($img, 'top-left', $x, $y);
                }

                $fileName = 'Progress_'.date('Y-m-d').'.jpg';
                $mimeType = 'image/jpeg';
                $fileContent = base64_encode($canvas->toJpeg(90));
            } else {
                // Single image fills the canvas
                $file = $files[0];
                $img = $manager->read($file)->cover($canvasWidth, $canvasHeight);

                $fileName = $file->getClientOriginalName();
                $mimeType = 'image/jpeg';
                $fileContent = base64_encode($img->toJpeg(90));
            }
        }

        CoopProgramProgress::create([
            'coop_program_id' => $data['coop_program_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_content' => $fileContent,
        ]);

        return back()->with('success', 'Progress report uploaded successfully!');
    }

    // View a stored image (inline)
    public function show(CoopProgramProgress $report)
    {
        $imageData = base64_decode($report->file_content);

        return response($imageData)
            ->header('Content-Type', $report->mime_type);
    }

    // Download the progress report
    public function download(CoopProgramProgress $report)
    {
        return response(base64_decode($report->file_content))
            ->header('Content-Type', $report->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.$report->file_name.'"');
    }
}
