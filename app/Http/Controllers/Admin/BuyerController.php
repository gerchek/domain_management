<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = User::where('role', 'buyer')
            ->withCount('domains')
            ->latest()
            ->paginate(15);

        return view('admin.buyers.index', compact('buyers'));
    }

    public function create()
    {
        return view('admin.buyers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $buyer = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'buyer',
            'is_active' => true,
        ]);

        ActivityLog::log('create_buyer', "Создан байер: {$buyer->email}");

        return redirect()->route('admin.buyers.index')
            ->with('success', 'Байер успешно создан');
    }

    public function edit(User $buyer)
    {
        if (!$buyer->isBuyer()) {
            abort(404);
        }

        return view('admin.buyers.edit', compact('buyer'));
    }

    public function update(Request $request, User $buyer)
    {
        if (!$buyer->isBuyer()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $buyer->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        $buyer->name = $validated['name'];
        $buyer->email = $validated['email'];
        $buyer->is_active = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $buyer->password = Hash::make($validated['password']);
        }

        $buyer->save();

        ActivityLog::log('update_buyer', "Обновлён байер: {$buyer->email}");

        return redirect()->route('admin.buyers.index')
            ->with('success', 'Байер успешно обновлён');
    }

    public function destroy(User $buyer)
    {
        if (!$buyer->isBuyer()) {
            abort(404);
        }

        $email = $buyer->email;
        $buyer->delete();

        ActivityLog::log('delete_buyer', "Удалён байер: {$email}");

        return redirect()->route('admin.buyers.index')
            ->with('success', 'Байер успешно удалён');
    }

    public function toggleStatus(User $buyer)
    {
        if (!$buyer->isBuyer()) {
            abort(404);
        }

        $buyer->is_active = !$buyer->is_active;
        $buyer->save();

        $status = $buyer->is_active ? 'активирован' : 'деактивирован';
        ActivityLog::log('toggle_buyer_status', "Байер {$buyer->email} {$status}");

        return redirect()->back()
            ->with('success', "Байер успешно {$status}");
    }
}
