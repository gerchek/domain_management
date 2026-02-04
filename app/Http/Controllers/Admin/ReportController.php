<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainBatch;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $buyerId = $request->input('buyer_id');
        $serverId = $request->input('server_id');

        $query = Domain::query()
            ->with(['buyer', 'server'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $buyerId);
        }

        if ($request->filled('server_id')) {
            $query->where('server_id', $serverId);
        }

        $domains = $query->latest()->paginate(50);

        $stats = [
            'total' => (clone $query)->count(),
            'successful' => (clone $query)->where('status', 'dns_set')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        $buyerStats = Domain::select('buyer_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('buyer_id')
            ->with('buyer')
            ->get();

        $serverStats = Domain::select('server_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('server_id')
            ->with('server')
            ->get();

        $buyers = User::where('role', 'buyer')->get();
        $servers = Server::all();

        return view('admin.reports.index', compact(
            'domains',
            'stats',
            'buyerStats',
            'serverStats',
            'buyers',
            'servers',
            'dateFrom',
            'dateTo',
            'buyerId',
            'serverId'
        ));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $buyerId = $request->input('buyer_id');
        $serverId = $request->input('server_id');

        $query = Domain::query()
            ->with(['buyer', 'server'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $buyerId);
        }

        if ($request->filled('server_id')) {
            $query->where('server_id', $serverId);
        }

        $domains = $query->get();

        $filename = 'domains_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($domains) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Domain', 'Buyer', 'Server', 'Status', 'Purchased At', 'DNS Set At', 'Error']);

            foreach ($domains as $domain) {
                fputcsv($file, [
                    $domain->domain_name,
                    $domain->buyer->name ?? 'N/A',
                    $domain->server->name ?? 'N/A',
                    $domain->status,
                    $domain->purchased_at?->format('Y-m-d H:i:s') ?? '',
                    $domain->dns_set_at?->format('Y-m-d H:i:s') ?? '',
                    $domain->error_message ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
