<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OfferController extends Controller
{
    /**
     * Display a listing of offers
     */
    public function index()
    {
        $offers = Offer::withCount('domainDeployments')
            ->latest()
            ->paginate(20);

        return view('admin.offers.index', compact('offers'));
    }

    /**
     * Show the form for creating a new offer
     */
    public function create()
    {
        return view('admin.offers.create');
    }

    /**
     * Store a newly created offer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zip_file' => ['required', 'file', 'max:102400'], // 100MB max
        ], [
            'name.required' => 'Название обязательно',
            'zip_file.required' => 'ZIP файл обязателен',
            'zip_file.max' => 'ZIP файл должен быть меньше 100MB',
        ]);

        // Check file extension manually
        $file = $request->file('zip_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'zip') {
            return back()
                ->withInput()
                ->withErrors(['zip_file' => 'Файл должен быть ZIP архивом']);
        }
        $filePath = $file->store('offers', 'local');

        // Create the offer
        $offer = Offer::create([
            'name' => $request->name,
            'zip_file_path' => $filePath,
        ]);

        Log::info('Offer created', [
            'id' => $offer->id,
            'name' => $offer->name,
            'file_path' => $filePath,
        ]);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', "Оффер \"{$offer->name}\" успешно создан!");
    }

    /**
     * Remove the specified offer
     */
    public function destroy(Offer $offer)
    {
        // Check if in use
        if ($offer->isInUse()) {
            return back()->with('error', "Невозможно удалить \"{$offer->name}\" - используется в {$offer->domainDeployments()->count()} деплоях.");
        }

        $name = $offer->name;

        // Delete the ZIP file if exists
        if ($offer->zip_file_path && Storage::disk('local')->exists($offer->zip_file_path)) {
            Storage::disk('local')->delete($offer->zip_file_path);
        }

        $offer->delete();

        Log::info('Offer deleted', ['name' => $name]);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', "Оффер \"{$name}\" успешно удалён!");
    }
}
