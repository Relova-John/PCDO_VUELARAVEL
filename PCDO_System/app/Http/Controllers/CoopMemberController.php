<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\CoopMember;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoopMemberController extends Controller
{
    public function index(Cooperative $cooperative)
    {
        return Inertia::render('cooperatives/members/index', [
            'cooperative' => $cooperative->only('id', 'name'),
            'members' => $cooperative->members()->with('files')->get(),
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative->id)],
                ['title' => 'Members', 'href' => route('cooperatives.members.index', $cooperative->id)],
            ],
        ]);
    }

    public function create(Cooperative $cooperative)
    {
        return Inertia::render('cooperatives/members/create', [
            'cooperative' => $cooperative->only('id', 'name'),
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative->id)],
                ['title' => 'Members', 'href' => route('cooperatives.members.index', $cooperative->id)],
                ['title' => 'Add Member', 'href' => route('cooperatives.members.create', $cooperative->id)],
            ],
        ]);
    }

    public function store(Request $request, Cooperative $cooperative)
    {
        $rules = [
            'position' => 'required|in:Chairman,Secretary,Manager,Member',
        ];

        if ($request->position === 'Member') {
            $rules['files.*'] = 'required|file|mimes:pdf,jpg,jpeg,png';
        } elseif (in_array($request->position, [
            'Chairman', 'Secretary', 'Manager',
        ])) {
            $rules = array_merge($rules, [
                'first_name' => 'required|string',
                'middle_initial' => 'nullable|string|max:1',
                'last_name' => 'required|string',
                'is_representative' => 'boolean',
                'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png',
            ]);
        } else {
            return back()->withErrors(['position' => 'Invalid position selected.']);
        }

        $validated = $request->validate($rules);
        $member = $cooperative->members()->create($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $uploaded) {
                $path = $uploaded->store('member_files');
                $member->files()->create([
                    'file_path' => $path,
                    'file_name' => $uploaded->getClientOriginalName(),
                    'file_type' => $uploaded->getClientMimeType(),
                ]);
            }
        }

        return redirect()
            ->route('cooperatives.members.index', $cooperative->id)
            ->with('success', 'Member created successfully.');
    }

    public function show(Cooperative $cooperative, CoopMember $member)
    {
        return Inertia::render('cooperatives/members/show', [
            'cooperative' => $cooperative,
            'member' => $member->load('files'),
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative->id)],
                ['title' => 'Members', 'href' => route('cooperatives.members.index', $cooperative->id)],
                ['title' => $member->first_name.' '.$member->last_name, 'href' => route('cooperatives.members.show', [$cooperative->id, $member->id])],
            ],
        ]);
    }

    public function edit(Cooperative $cooperative, CoopMember $member)
    {
        return Inertia::render('cooperatives/members/edit', [
            'cooperative' => $cooperative,
            'member' => $member->load('files'),
            'breadcrumbs' => [
                ['title' => 'Cooperatives', 'href' => route('cooperatives.index')],
                ['title' => $cooperative->name, 'href' => route('cooperatives.show', $cooperative->id)],
                ['title' => 'Members', 'href' => route('cooperatives.members.index', $cooperative->id)],
                ['title' => $member->first_name.' '.$member->last_name, 'href' => route('cooperatives.members.show', [$cooperative->id, $member->id])],
                ['title' => 'Edit', 'href' => route('cooperatives.members.edit', [$cooperative->id, $member->id])],
            ],
        ]);
    }

    public function update(Request $request, Cooperative $cooperative, CoopMember $member)
    {
        $validated = $request->validate([
            'position' => 'required|in:chairman,vice_chairman,secretary,treasurer,auditor,member',
            'first_name' => 'nullable|string',
            'middle_initial' => 'nullable|string|max:1',
            'last_name' => 'nullable|string',
            'is_representative' => 'boolean',
            'files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $member->update($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $uploaded) {
                $path = $uploaded->store('member_files');
                $member->files()->create([
                    'file_path' => $path,
                    'file_name' => $uploaded->getClientOriginalName(),
                    'file_type' => $uploaded->getClientMimeType(),
                ]);
            }
        }

        return redirect()
            ->route('cooperatives.members.index', $cooperative->id)
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Cooperative $cooperative, CoopMember $member)
    {
        $member->delete();

        return redirect()
            ->route('cooperatives.members.index', $cooperative->id)
            ->with('success', 'Member deleted successfully.');
    }
}
