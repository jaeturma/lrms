<?php

namespace App\Http\Controllers;

use App\Models\AppContent;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function support(): Response
    {
        $content = AppContent::query()
            ->where('key', 'support')
            ->firstOr(fn () => (object) [
                'title' => 'Support',
                'body' => '<p>Support content coming soon.</p>',
            ]);

        return Inertia::render('SupportPage', [
            'content' => [
                'title' => $content->title,
                'body' => $content->body,
            ],
        ]);
    }

    public function about(): Response
    {
        $content = AppContent::query()
            ->where('key', 'about')
            ->firstOr(fn () => (object) [
                'title' => 'About the App',
                'body' => '<p>About content coming soon.</p>',
            ]);

        return Inertia::render('AboutPage', [
            'content' => [
                'title' => $content->title,
                'body' => $content->body,
            ],
        ]);
    }
}
