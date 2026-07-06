<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentManagementController extends Controller
{
    public function editSupport(): Response
    {
        $content = AppContent::query()
            ->where('key', 'support')
            ->firstOrCreate(
                ['key' => 'support'],
                [
                    'title' => 'Support',
                    'body' => '<p>Support content coming soon.</p>',
                ],
            );

        return Inertia::render('Admin/EditContent', [
            'content' => $content,
            'type' => 'support',
        ]);
    }

    public function editAbout(): Response
    {
        $content = AppContent::query()
            ->where('key', 'about')
            ->firstOrCreate(
                ['key' => 'about'],
                [
                    'title' => 'About the App',
                    'body' => '<p>About content coming soon.</p>',
                ],
            );

        return Inertia::render('Admin/EditContent', [
            'content' => $content,
            'type' => 'about',
        ]);
    }

    public function updateSupport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        AppContent::query()
            ->where('key', 'support')
            ->firstOrCreate(['key' => 'support'])
            ->update($validated);

        return redirect()
            ->route('admin.content.edit-support')
            ->with('status', 'Support content updated successfully.');
    }

    public function updateAbout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        AppContent::query()
            ->where('key', 'about')
            ->firstOrCreate(['key' => 'about'])
            ->update($validated);

        return redirect()
            ->route('admin.content.edit-about')
            ->with('status', 'About content updated successfully.');
    }
}
