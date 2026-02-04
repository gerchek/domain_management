<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PalladiumConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PalladiumConfigController extends Controller
{
    /**
     * Display a listing of Palladium configs
     */
    public function index()
    {
        $configs = PalladiumConfig::withCount('domainDeployments')
            ->latest()
            ->paginate(20);

        return view('admin.palladium-configs.index', compact('configs'));
    }

    /**
     * Show the form for creating a new config
     */
    public function create()
    {
        return view('admin.palladium-configs.create');
    }

    /**
     * Store a newly created config
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'geo' => ['nullable', 'string', 'max:255'],
            'palladium_file' => ['required', 'file', 'max:1024'],
        ], [
            'name.required' => 'Название обязательно',
            'palladium_file.required' => 'PHP файл обязателен',
            'palladium_file.max' => 'Файл должен быть меньше 1MB',
        ]);

        // Check file extension manually (mimes doesn't work well with PHP files)
        $file = $request->file('palladium_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['php', 'txt'])) {
            return back()
                ->withInput()
                ->withErrors(['palladium_file' => 'Файл должен быть PHP или TXT']);
        }

        // Parse the uploaded PHP file
        $content = file_get_contents($file->getRealPath());

        $parsedData = $this->parsePalladiumFile($content);

        if (!$parsedData) {
            return back()
                ->withInput()
                ->withErrors(['palladium_file' => 'Не удалось распарсить данные Palladium из файла. Убедитесь, что файл содержит clientId, clientCompany, clientSecret и bannerSource.']);
        }

        // Store the file
        $filePath = $file->store('palladium', 'local');

        // Create the config
        $config = PalladiumConfig::create([
            'name' => $request->name,
            'geo' => $request->geo,
            'client_id' => $parsedData['client_id'],
            'client_company' => $parsedData['client_company'],
            'client_secret' => $parsedData['client_secret'],
            'banner_source' => $parsedData['banner_source'] ?? 'adwords',
            'file_path' => $filePath,
        ]);

        Log::info('Palladium config created', [
            'id' => $config->id,
            'name' => $config->name,
            'client_id' => $config->client_id,
        ]);

        return redirect()
            ->route('admin.palladium-configs.index')
            ->with('success', "Palladium конфиг \"{$config->name}\" успешно создан!");
    }

    /**
     * Remove the specified config
     */
    public function destroy(PalladiumConfig $palladiumConfig)
    {
        // Check if in use
        if ($palladiumConfig->isInUse()) {
            return back()->with('error', "Невозможно удалить \"{$palladiumConfig->name}\" - используется в {$palladiumConfig->domainDeployments()->count()} деплоях.");
        }

        $name = $palladiumConfig->name;

        // Delete the file if exists
        if ($palladiumConfig->file_path && Storage::disk('local')->exists($palladiumConfig->file_path)) {
            Storage::disk('local')->delete($palladiumConfig->file_path);
        }

        $palladiumConfig->delete();

        Log::info('Palladium config deleted', ['name' => $name]);

        return redirect()
            ->route('admin.palladium-configs.index')
            ->with('success', "Palladium конфиг \"{$name}\" успешно удалён!");
    }

    /**
     * Parse Palladium data from PHP file content
     */
    protected function parsePalladiumFile(string $content): ?array
    {
        $data = [];

        // Pattern for: $headers['auth']['clientId'] = 5795;
        if (preg_match("/\['auth'\]\['clientId'\]\s*=\s*(\d+)/", $content, $matches)) {
            $data['client_id'] = (int) $matches[1];
        }

        // Pattern for: $headers['auth']['clientCompany'] = "VHAlcYWR55Dm0lSRSYfw";
        if (preg_match("/\['auth'\]\['clientCompany'\]\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $data['client_company'] = $matches[1];
        }

        // Pattern for: $headers['auth']['clientSecret'] = "NTc5NVZ...";
        if (preg_match("/\['auth'\]\['clientSecret'\]\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $data['client_secret'] = $matches[1];
        }

        // Pattern for: $headers['server']['bannerSource'] = 'adwords';
        if (preg_match("/\['server'\]\['bannerSource'\]\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $data['banner_source'] = $matches[1];
        }

        // Validate required fields
        if (empty($data['client_id']) || empty($data['client_company']) || empty($data['client_secret'])) {
            return null;
        }

        return $data;
    }
}
