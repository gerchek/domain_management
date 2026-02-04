<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::withCount('domains')
            ->latest()
            ->paginate(15);

        return view('admin.servers.index', compact('servers'));
    }

    public function create()
    {
        return view('admin.servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'ssh_username' => ['required', 'string', 'max:255'],
            'ssh_password' => ['nullable', 'string', 'required_without:ssh_private_key'],
            'ssh_private_key' => ['nullable', 'string'],
            'ssh_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'max_domains' => ['required', 'integer', 'min:1'],
        ]);

        // Remove empty ssh_password or ssh_private_key
        if (empty($validated['ssh_password'])) {
            unset($validated['ssh_password']);
        }
        if (empty($validated['ssh_private_key'])) {
            unset($validated['ssh_private_key']);
        }

        $server = Server::create($validated);

        ActivityLog::log('create_server', "Создан сервер: {$server->name} ({$server->ip_address})");

        return redirect()->route('admin.servers.index')
            ->with('success', 'Сервер успешно создан');
    }

    public function edit(Server $server)
    {
        return view('admin.servers.edit', compact('server'));
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'ssh_username' => ['required', 'string', 'max:255'],
            'ssh_password' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],
            'ssh_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'max_domains' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $server->name = $validated['name'];
        $server->ip_address = $validated['ip_address'];
        $server->ssh_username = $validated['ssh_username'];
        $server->ssh_port = $validated['ssh_port'];
        $server->max_domains = $validated['max_domains'];
        $server->is_active = $request->boolean('is_active');

        if (!empty($validated['ssh_password'])) {
            $server->ssh_password = $validated['ssh_password'];
        }

        if (!empty($validated['ssh_private_key'])) {
            $server->ssh_private_key = $validated['ssh_private_key'];
        }

        $server->save();

        ActivityLog::log('update_server', "Обновлён сервер: {$server->name}");

        return redirect()->route('admin.servers.index')
            ->with('success', 'Сервер успешно обновлён');
    }

    public function destroy(Server $server)
    {
        if ($server->domains()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Невозможно удалить сервер с привязанными доменами');
        }

        $name = $server->name;
        $server->delete();

        ActivityLog::log('delete_server', "Удалён сервер: {$name}");

        return redirect()->route('admin.servers.index')
            ->with('success', 'Сервер успешно удалён');
    }

    public function toggleStatus(Server $server)
    {
        $server->is_active = !$server->is_active;
        $server->save();

        $status = $server->is_active ? 'активирован' : 'деактивирован';
        ActivityLog::log('toggle_server_status', "Сервер {$server->name} {$status}");

        return redirect()->back()
            ->with('success', "Сервер успешно {$status}");
    }
}
