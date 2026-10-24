<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('admin.services.index', ['services' => Service::withCount('agents')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Service::create($request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:services,code'],
            'name' => ['required', 'string', 'max:150'],
            'head_name' => ['nullable', 'string', 'max:150'],
        ], [], ['name' => 'nom', 'head_name' => 'responsable']));

        return back()->with('success', 'Service créé.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'head_name' => ['nullable', 'string', 'max:150'],
        ], [], ['name' => 'nom', 'head_name' => 'responsable']);

        $service->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Service mis à jour.');
    }
}
