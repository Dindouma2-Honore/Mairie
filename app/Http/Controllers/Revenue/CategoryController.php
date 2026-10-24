<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Models\RevenueCategory;
use App\Models\Tariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('revenue.categories.index', [
            'categories' => RevenueCategory::with(['tariffs' => fn ($q) => $q->orderBy('label')->orderByDesc('effective_from')])
                ->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:revenue_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'allow_custom_amount' => ['nullable', 'boolean'],
        ], [], ['name' => 'nom']);

        RevenueCategory::create($data + ['allow_custom_amount' => $request->boolean('allow_custom_amount')]);

        return back()->with('success', 'Catégorie créée.');
    }

    public function toggleCategory(RevenueCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active ? 'Catégorie activée.' : 'Catégorie désactivée.');
    }

    /**
     * Un tarif n'est jamais modifié : on en crée un nouveau avec une date d'effet,
     * et l'ancien tarif de même libellé est clôturé la veille (historique conservé).
     */
    public function storeTariff(Request $request, RevenueCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'effective_from' => ['required', 'date'],
        ], [], ['label' => 'libellé', 'amount' => 'montant', 'effective_from' => 'date d’effet']);

        $from = $request->date('effective_from');

        $category->tariffs()
            ->where('label', $data['label'])
            ->whereNull('effective_to')
            ->where('effective_from', '<', $from)
            ->get()
            ->each(fn (Tariff $t) => $t->update(['effective_to' => $from->copy()->subDay()]));

        $category->tariffs()->create($data);

        return back()->with('success', 'Tarif enregistré.');
    }

    public function toggleTariff(Tariff $tariff): RedirectResponse
    {
        $tariff->update(['is_active' => ! $tariff->is_active]);

        return back()->with('success', 'Tarif mis à jour.');
    }
}
