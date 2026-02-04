<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatGptModel;
use Illuminate\Http\Request;

class ChatGptModelController extends Controller
{
    public function index()
    {
        $models = ChatGptModel::ordered()->paginate(20);
        return view('admin.chatgpt-models.index', compact('models'));
    }

    public function create()
    {
        return view('admin.chatgpt-models.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'model_id' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ChatGptModel::create($validated);

        return redirect()->route('admin.chatgpt-models.index')
            ->with('success', 'Модель ChatGPT успешно добавлена');
    }

    public function edit(ChatGptModel $chatgpt_model)
    {
        return view('admin.chatgpt-models.edit', ['model' => $chatgpt_model]);
    }

    public function update(Request $request, ChatGptModel $chatgpt_model)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'model_id' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $chatgpt_model->update($validated);

        return redirect()->route('admin.chatgpt-models.index')
            ->with('success', 'Модель ChatGPT успешно обновлена');
    }

    public function destroy(ChatGptModel $chatgpt_model)
    {
        $chatgpt_model->delete();

        return redirect()->route('admin.chatgpt-models.index')
            ->with('success', 'Модель ChatGPT удалена');
    }

    public function toggleStatus(ChatGptModel $chatgpt_model)
    {
        $chatgpt_model->update(['is_active' => !$chatgpt_model->is_active]);

        $status = $chatgpt_model->is_active ? 'активирована' : 'деактивирована';
        return back()->with('success', "Модель {$status}");
    }
}
