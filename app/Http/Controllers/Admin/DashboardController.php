<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainBatch;
use App\Models\Server;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_buyers' => User::where('role', 'buyer')->count(),
            'active_buyers' => User::where('role', 'buyer')->where('is_active', true)->count(),
            'total_servers' => Server::count(),
            'active_servers' => Server::where('is_active', true)->count(),
            'total_domains' => Domain::count(),
            'successful_domains' => Domain::where('status', 'dns_set')->count(),
            'pending_domains' => Domain::where('status', 'pending')->count(),
            'failed_domains' => Domain::where('status', 'failed')->count(),
        ];

        $recentBatches = DomainBatch::with(['buyer', 'server'])
            ->latest()
            ->take(10)
            ->get();

        $topBuyers = User::where('role', 'buyer')
            ->withCount('domains')
            ->orderByDesc('domains_count')
            ->take(5)
            ->get();

        $serverUsage = Server::select('id', 'name', 'max_domains', 'current_domains_count')
            ->where('is_active', true)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentBatches', 'topBuyers', 'serverUsage'));
    }
}
