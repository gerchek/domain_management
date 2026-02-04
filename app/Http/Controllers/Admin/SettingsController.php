<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatGptModel;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'dynadot_api_key' => Setting::get('dynadot_api_key', ''),
            'domains_per_request' => Setting::get('domains_per_request', 20),
            'chatgpt_api_key' => Setting::get('chatgpt_api_key', ''),
            'chatgpt_model' => Setting::get('chatgpt_model', 'gpt-4'),
        ];

        $chatgptModels = ChatGptModel::getActiveModels();

        return view('admin.settings.index', compact('settings', 'chatgptModels'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'dynadot_api_key' => ['required', 'string'],
            'domains_per_request' => ['required', 'integer', 'min:1', 'max:100'],
            'chatgpt_api_key' => ['nullable', 'string'],
            'chatgpt_model' => ['nullable', 'string'],
        ]);

        Setting::set('dynadot_api_key', $validated['dynadot_api_key']);
        Setting::set('domains_per_request', $validated['domains_per_request']);
        Setting::set('chatgpt_api_key', $validated['chatgpt_api_key'] ?? '');
        Setting::set('chatgpt_model', $validated['chatgpt_model'] ?? 'gpt-4');

        ActivityLog::log('update_settings', 'Системные настройки обновлены');

        return redirect()->back()
            ->with('success', 'Настройки успешно обновлены');
    }
}
