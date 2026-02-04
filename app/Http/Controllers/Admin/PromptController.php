<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index()
    {
        $prompts = Prompt::latest()->paginate(20);
        return view('admin.prompts.index', compact('prompts'));
    }

    public function create()
    {
        return view('admin.prompts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prompt_text' => ['required', 'string'],
            'language' => ['required', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Prompt::create($validated);

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Промпт успешно создан');
    }

    public function edit(Prompt $prompt)
    {
        return view('admin.prompts.edit', compact('prompt'));
    }

    public function update(Request $request, Prompt $prompt)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prompt_text' => ['required', 'string'],
            'language' => ['required', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $prompt->update($validated);

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Промпт успешно обновлён');
    }

    public function destroy(Prompt $prompt)
    {
        $prompt->delete();

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Промпт удалён');
    }

    public function toggleStatus(Prompt $prompt)
    {
        $prompt->update(['is_active' => !$prompt->is_active]);

        $status = $prompt->is_active ? 'активирован' : 'деактивирован';
        return back()->with('success', "Промпт {$status}");
    }
}
