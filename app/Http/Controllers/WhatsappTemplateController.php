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
            'is_active' => 'boolean',
            // Hari pengingat tagihan: angka -30..30 dipisah koma (mis. "3,1,0")
            'reminder_days' => ['nullable', 'string', 'max:50', 'regex:/^\s*-?\d{1,2}(\s*,\s*-?\d{1,2})*\s*$/'],
        ], [
            'reminder_days.regex' => 'Isi angka hari dipisah koma, mis. 3,1,0.',
        ]);

        $whatsappTemplate->update([
            'message' => $request->message,
            'is_active' => $request->has('is_active')
        ]);

        if ($whatsappTemplate->type === 'invoice_reminder' && $request->filled('reminder_days')) {
            \App\Models\Setting::updateOrCreate(['key' => 'invoice_reminder_days'],
                ['value' => implode(',', array_map('intval', array_map('trim', explode(',', $request->reminder_days))))]);
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        }

        return redirect()->route('whatsapp-templates.index')->with('success', 'Template pesan WhatsApp berhasil diperbarui.');
    }

    public function reset()
    {
        Artisan::call('db:seed', ['--class' => 'WhatsappTemplateSeeder']);
        return redirect()->route('whatsapp-templates.index')->with('success', 'Semua template berhasil dikembalikan ke default bawaan sistem.');
    }
}
