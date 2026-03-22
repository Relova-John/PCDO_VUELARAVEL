<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportingDate;

class ReportingDateController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reporting_month' => 'required|integer|min:1|max:12',
            'reporting_year' => 'required|integer'
        ]);

        $reportingDate = ReportingDate::firstOrCreate(
            [
                'reporting_month' => $validated['reporting_month'],
                'reporting_year' => $validated['reporting_year']
            ]
        );

        return back()->with('success', 'Reporting date saved successfully');
    }
}
