<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainBatch;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_domains' => $user->domains()->count(),
            'successful_domains' => $user->domains()->where('status', 'dns_set')->count(),
            'pending_domains' => $user->domains()->where('status', 'pending')->count(),
            'failed_domains' => $user->domains()->where('status', 'failed')->count(),
        ];

        $recentBatches = $user->domainBatches()
            ->with('server')
            ->latest()
            ->take(5)
            ->get();

        $recentDomains = $user->domains()
            ->with('server')
            ->latest()
            ->take(10)
            ->get();

        return view('buyer.dashboard', compact('stats', 'recentBatches', 'recentDomains'));
    }
}
