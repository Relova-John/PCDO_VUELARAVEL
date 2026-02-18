<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CoopMemController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $cooperative = $user->cooperatives()->with(['members.files'])->firstOrFail();

        if (! $cooperative) {
            abort(404, 'No cooperative assigned.');
        }

        $members = $cooperative->members()
            ->with('files')
            ->orderBy('active_year', 'desc')
            ->orderBy('position')
            ->get();

        return Inertia::render('coop/members/index', [
            'cooperative' => $cooperative->only('id', 'name'),
            'members' => $members,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $cooperative = $user->cooperatives()->firstOrFail();

        $member = $cooperative->members()
            ->with('files')
            ->findOrFail($id);

        return Inertia::render('coop/members/show', [
            'cooperative' => $cooperative->only('id', 'name'),
            'member' => $member,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => '/coop/dashboard'],
                ['title' => 'Members', 'href' => '/coop/members'],
                ['title' => $member->id.' - '.$member->first_name.' '.$member->last_name, 'href' => null],
            ],
        ]);
    }
}
