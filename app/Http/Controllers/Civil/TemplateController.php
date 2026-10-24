<?php

namespace App\Http\Controllers\Civil;

use App\Http\Controllers\Controller;
use App\Models\ActDelivery;
use App\Models\ActTemplate;
use App\Models\BirthAct;
use App\Services\ActRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gabarits d'impression des actes. Le format officiel fourni par la mairie
 * est collé ici (HTML + jetons {{nom_enfant}}…) sans modifier le code.
 */
class TemplateController extends Controller
{
    public function index()
    {
        return view('civil.templates.index', [
            'templates' => ActTemplate::where('type', 'birth')->latest('id')->get(),
            'tokens' => ActRenderer::TOKENS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'html' => ['required', 'string', 'max:500000'],
        ], [], ['name' => 'nom', 'html' => 'contenu HTML']);

        ActTemplate::create($data + ['type' => 'birth', 'is_active' => false, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Gabarit enregistré. Vérifiez l’aperçu avant de l’activer.');
    }

    public function activate(Request $request, ActTemplate $template): RedirectResponse
    {
        ActTemplate::where('type', $template->type)->update(['is_active' => false]);
        $template->update(['is_active' => true, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Gabarit « ' . $template->name . ' » activé pour les nouvelles impressions.');
    }

    public function deactivate(Request $request, ActTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => false, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Gabarit désactivé : le gabarit provisoire est de nouveau utilisé.');
    }

    /** Aperçu sur le dernier acte validé (rien n'est enregistré). */
    public function preview(ActTemplate $template, ActRenderer $renderer)
    {
        $act = BirthAct::where('status', 'validated')->latest('id')->first();
        abort_unless($act, 404, 'Aucun acte validé pour l’aperçu : validez d’abord un acte de test.');

        $delivery = new ActDelivery(['copy_type' => 'extrait']);
        $data = $renderer->data($act, $delivery);
        $replace = [];
        foreach ($data as $token => $value) {
            $replace['{{' . $token . '}}'] = e($value);
        }

        return view('civil.births.print', [
            'html' => strtr((string) $template->html, $replace),
            'act' => $act,
            'delivery' => $delivery,
            'preview' => true,
        ]);
    }
}
