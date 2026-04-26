<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::orderBy('id', 'asc')->get();
        return view('whatsapp-templates.index', compact('templates'));
    }

    public function edit(WhatsappTemplate $whatsappTemplate)
    {
        $variables = json_decode($whatsappTemplate->variables_description, true) ?? [];
        return view('whatsapp-templates.edit', compact('whatsappTemplate', 'variables'));
    }

    public function update(Request $request, WhatsappTemplate $whatsappTemplate)
    {
        $request->validate([
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $whatsappTemplate->update([
            'message' => $request->message,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('whatsapp-templates.index')->with('success', 'Template pesan WhatsApp berhasil diperbarui.');
    }

    public function reset()
    {
        Artisan::call('db:seed', ['--class' => 'WhatsappTemplateSeeder']);
        return redirect()->route('whatsapp-templates.index')->with('success', 'Semua template berhasil dikembalikan ke default bawaan sistem.');
    }
}
