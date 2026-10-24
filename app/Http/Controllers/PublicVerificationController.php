<?php

namespace App\Http\Controllers;

use App\Models\BirthAct;

/**
 * Page publique de contrôle d'authenticité d'un acte (code imprimé sur le document).
 */
class PublicVerificationController extends Controller
{
    public function show(string $code)
    {
        $act = BirthAct::with('centre')
            ->where('verification_code', strtoupper($code))
            ->where('status', 'validated')
            ->first();

        return view('civil.verify', ['act' => $act, 'code' => strtoupper($code)]);
    }
}
