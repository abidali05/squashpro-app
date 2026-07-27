<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private string $filePath = 'settings.json';

    public function index(): View
    {
        $settings = $this->loadSettings();
        return view('content.admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['maintenance_mode'] = $request->has('maintenance_mode');

        Storage::disk('local')->put($this->filePath, json_encode($validated, JSON_PRETTY_PRINT));

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }

    private function loadSettings(): array
    {
        if (Storage::disk('local')->exists($this->filePath)) {
            $content = Storage::disk('local')->get($this->filePath);
            return json_decode($content, true) ?: $this->defaults();
        }

        return $this->defaults();
    }

    private function defaults(): array
    {
        return [
            'platform_name' => 'Squash Pro',
            'contact_email' => 'support@squashpro.com',
            'currency' => 'PKR',
            'commission_percentage' => 10.0,
            'service_fee' => 50.00,
            'maintenance_mode' => false,
        ];
    }
}
