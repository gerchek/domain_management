<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainDeploymentRequest;
use App\Http\Requests\UpdateDomainDeploymentRequest;
use App\Http\Requests\AttachBlackSiteRequest;
use App\Jobs\DeployBlackSiteJob;
use App\Models\Domain;
use App\Models\DomainDeployment;
use App\Models\PalladiumConfig;
use App\Models\Offer;
use App\Services\DeployService;
use Illuminate\Http\Request;

class DomainDeploymentController extends Controller
{
    /**
     * Build tracking config (Keitaro) from request
     */
    private function buildTrackingConfig(Request $request): array
    {
        return [
            'khost' => $request->khost,
            'kapitoken' => $request->kapitoken,
        ];
    }

    /**
     * Build Palladium config from selected config ID
     */
    private function buildPalladiumConfig(int $configId): array
    {
        $config = PalladiumConfig::findOrFail($configId);

        return [
            'client_id' => $config->client_id,
            'client_company' => $config->client_company,
            'client_secret' => $config->client_secret,
            'banner_source' => $config->banner_source,
        ];
    }

    /**
     * List of domain deployments
     */
    public function index()
    {
        $deployments = DomainDeployment::whereHas('domain', function ($query) {
                $query->where('buyer_id', auth()->id());
            })
            ->with(['domain.server', 'siteProject', 'palladiumConfigRelation', 'offer'])
            ->latest()
            ->paginate(20);

        return view('buyer.domain-deployments.index', compact('deployments'));
    }

    /**
     * Show deployment details
     */
    public function show(DomainDeployment $deployment)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        $deployment->load(['domain.server', 'siteProject.prompt', 'palladiumConfigRelation', 'offer']);

        return view('buyer.domain-deployments.show', compact('deployment'));
    }

    /**
     * Create new deployment form
     */
    public function create()
    {
        // Domains with completed white site deployment (existing project)
        // that don't have active DomainDeployment (black site) yet
        $domains = Domain::where('buyer_id', auth()->id())
            ->whereHas('siteDeployments', function ($query) {
                $query->where('status', 'completed')
                    ->whereHas('project', function ($q) {
                        $q->where('status', 'ready');
                    });
            })
            ->whereDoesntHave('domainDeployments', function ($query) {
                $query->whereIn('status', ['pending', 'deployed']);
            })
            ->with(['server', 'siteDeployments' => function ($query) {
                $query->where('status', 'completed')
                    ->whereHas('project', function ($q) {
                        $q->where('status', 'ready');
                    })
                    ->with('project.prompt');
            }])
            ->get();

        // Get available Palladium configs and Offers
        $palladiumConfigs = PalladiumConfig::orderBy('name')->get();
        $offers = Offer::orderBy('name')->get();

        return view('buyer.domain-deployments.create', compact('domains', 'palladiumConfigs', 'offers'));
    }

    /**
     * Store new deployment(s)
     */
    public function store(StoreDomainDeploymentRequest $request)
    {

        // Verify all domains belong to buyer and have completed white site deployment
        $domainIds = $request->domain_ids;
        $validDomains = Domain::whereIn('id', $domainIds)
            ->where('buyer_id', auth()->id())
            ->whereHas('siteDeployments', function ($query) {
                $query->where('status', 'completed')
                    ->whereHas('project', function ($q) {
                        $q->where('status', 'ready');
                    });
            })
            ->with(['siteDeployments' => function ($query) {
                $query->where('status', 'completed')
                    ->whereHas('project', function ($q) {
                        $q->where('status', 'ready');
                    })
                    ->latest();
            }])
            ->get();

        if ($validDomains->count() !== count($domainIds)) {
            return back()->withInput()->withErrors(['domain_ids' => 'Некоторые выбранные домены недействительны или не готовы']);
        }

        // Build configs - Palladium is ALWAYS used now
        $palladiumConfigId = $request->palladium_config_id;
        $palladiumConfig = $this->buildPalladiumConfig($palladiumConfigId);

        // Tracking config only for keitaro type
        $trackingConfig = null;
        if ($request->tracking_type === 'keitaro') {
            $trackingConfig = $this->buildTrackingConfig($request);
        }

        // Offer only for offer type
        $offerId = null;
        if ($request->tracking_type === 'offer') {
            $offerId = $request->offer_id;
        }

        // Create deployments for each domain
        $createdDeployments = [];
        foreach ($validDomains as $domain) {
            $siteDeployment = $domain->siteDeployments->first();
            $projectId = $siteDeployment?->project_id;

            $deployment = DomainDeployment::create([
                'domain_id' => $domain->id,
                'site_project_id' => $projectId,
                'status' => 'pending',
                'tracking_type' => $request->tracking_type,
                'palladium_config_id' => $palladiumConfigId,
                'offer_id' => $offerId,
                'tracking_config' => $trackingConfig,
                'palladium_config' => $palladiumConfig,
            ]);

            // Dispatch job to deploy black site (async)
            DeployBlackSiteJob::dispatch($deployment);

            $createdDeployments[] = $deployment;
        }

        // Redirect based on number of deployments
        if (count($createdDeployments) === 1) {
            return redirect()->route('buyer.domain-deployments.show', $createdDeployments[0])
                ->with('success', 'Деплой black сайта запущен. Следите за статусом на этой странице.');
        }

        return redirect()->route('buyer.domain-deployments.index')
            ->with('success', count($createdDeployments) . ' деплоев black сайтов запущено. Следите за статусом в списке.');
    }

    /**
     * Edit deployment settings form (only Keitaro type - Palladium is read-only)
     */
    public function edit(DomainDeployment $deployment)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        // Only allow editing for keitaro type deployments (they have Keitaro config to edit)
        if (!$deployment->isKeitaroType()) {
            return redirect()->route('buyer.domain-deployments.show', $deployment)
                ->with('error', 'Только деплои с Keitaro можно редактировать');
        }

        $deployment->load(['domain.server', 'siteProject.prompt', 'palladiumConfigRelation']);

        return view('buyer.domain-deployments.edit', compact('deployment'));
    }

    /**
     * Update deployment settings (only Keitaro type)
     */
    public function update(UpdateDomainDeploymentRequest $request, DomainDeployment $deployment)
    {
        // Only allow editing for keitaro type deployments
        if (!$deployment->isKeitaroType()) {
            return redirect()->route('buyer.domain-deployments.show', $deployment)
                ->with('error', 'Только деплои с Keitaro можно редактировать');
        }

        $trackingConfig = $this->buildTrackingConfig($request);

        // Update deployment config in database
        $deployment->update([
            'tracking_config' => $trackingConfig,
        ]);

        // Update config files on server (without reinstalling)
        $deployment->update(['deployment_log' => []]);
        $deployment->addLog('info', 'Обновляем настройки Keitaro на сервере...');

        $deployService = app(DeployService::class);
        $result = $deployService->updateBlackSiteConfig($deployment);

        if ($result['success']) {
            return redirect()->route('buyer.domain-deployments.show', $deployment)
                ->with('success', 'Настройки Keitaro обновлены на сервере.');
        } else {
            return redirect()->route('buyer.domain-deployments.show', $deployment)
                ->with('error', 'Ошибка обновления на сервере: ' . $result['message']);
        }
    }

    /**
     * Form to attach black site
     */
    public function attachBlackSiteForm(DomainDeployment $deployment)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        $palladiumConfigs = PalladiumConfig::orderBy('name')->get();
        $offers = Offer::orderBy('name')->get();

        return view('buyer.domain-deployments.attach-black-site', compact('deployment', 'palladiumConfigs', 'offers'));
    }

    /**
     * Attach black site to existing deployment
     */
    public function attachBlackSite(AttachBlackSiteRequest $request, DomainDeployment $deployment)
    {
        // Build configs - Palladium is ALWAYS used now
        $palladiumConfigId = $request->palladium_config_id;
        $palladiumConfig = $this->buildPalladiumConfig($palladiumConfigId);

        // Tracking config only for keitaro type
        $trackingConfig = null;
        if ($request->tracking_type === 'keitaro') {
            $trackingConfig = $this->buildTrackingConfig($request);
        }

        // Offer only for offer type
        $offerId = null;
        if ($request->tracking_type === 'offer') {
            $offerId = $request->offer_id;
        }

        // Update deployment
        $deployment->update([
            'tracking_config' => $trackingConfig,
            'palladium_config' => $palladiumConfig,
            'tracking_type' => $request->tracking_type,
            'palladium_config_id' => $palladiumConfigId,
            'offer_id' => $offerId,
            'status' => 'pending',
        ]);

        // Dispatch job (async)
        DeployBlackSiteJob::dispatch($deployment);

        return redirect()->route('buyer.domain-deployments.show', $deployment)
            ->with('success', 'Деплой black сайта запущен. Следите за статусом.');
    }

    /**
     * Get deployment logs
     */
    public function getLogs(DomainDeployment $deployment)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        return view('buyer.domain-deployments.logs', compact('deployment'));
    }

    /**
     * API: Get deployment status (for AJAX)
     */
    public function status(DomainDeployment $deployment)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'id' => $deployment->id,
            'status' => $deployment->status,
            'domain' => $deployment->domain->domain_name,
            'server_host' => $deployment->server_host,
            'deployed_at' => $deployment->deployed_at?->format('d.m.Y H:i'),
            'logs' => $deployment->deployment_log,
        ]);
    }

    /**
     * Delete deployment (only removes black site, keeps white site)
     */
    public function destroy(DomainDeployment $deployment, DeployService $deployService)
    {
        if ($deployment->domain->buyer_id !== auth()->id()) {
            abort(403);
        }

        $domain = $deployment->domain;
        $server = $domain->server;

        // Remove only black site components from server (keep white site intact)
        if ($server && $deployment->isDeployed()) {
            $result = $deployService->removeBlackSite($domain->domain_name, $server);

            if (!$result['success']) {
                return back()->with('error', 'Ошибка удаления black сайта: ' . $result['message']);
            }
        }

        $deployment->delete();

        return redirect()->route('buyer.domain-deployments.index')
            ->with('success', 'Деплой black сайта удалён. White сайт остаётся активным.');
    }
}
