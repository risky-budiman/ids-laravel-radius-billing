<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    public function index($page = 'introduction')
    {
        $path = base_path('docs/' . $page . '.md');

        if (!File::exists($path)) {
            abort(404);
        }

        $content = File::get($path);
        // Convert Markdown to HTML using Laravel's built-in Str::markdown
        $html = Str::markdown($content);

        // Define custom order
        $order = [
            'introduction',
            'dashboard',
            'customers',
            'broadband',
            'billing',
            'accounting',
            'reports',
            'ticketing',
            'inventory',
            'users',
            'network',
            'settings',
            'troubleshooting'
        ];

        // Get all doc files
        $files = File::files(base_path('docs'));
        $navigation = collect($files)
            ->map(function ($file) {
                $name = $file->getFilenameWithoutExtension();
                return [
                    'slug' => $name,
                    'title' => Str::title(str_replace('-', ' ', $name))
                ];
            })
            ->sortBy(function ($nav) use ($order) {
                $pos = array_search($nav['slug'], $order);
                return $pos !== false ? $pos : 999;
            })
            ->values();

        return view('docs.index', compact('html', 'navigation', 'page'));
    }
}
