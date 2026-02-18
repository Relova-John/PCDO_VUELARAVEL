<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notifications;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = Notifications::with('schedule.coopProgram.cooperative')
            ->latest()
            ->get();

        return Inertia::render('admin/notification/index', [
            'notifications' => $notifications,
        ]);
    }

    public function show($id)
    {
        $notification = Notifications::with('schedule.coopProgram.cooperative')
            ->findOrFail($id);

        return Inertia::render('admin/notification/show', [
            'notification' => $notification,
        ]);
    }
}
