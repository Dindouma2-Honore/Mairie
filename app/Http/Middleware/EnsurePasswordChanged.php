<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force le changement du mot de passe initial (compte créé par un administrateur).
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs('password.*', 'logout', 'offline.*')) {
            return redirect()->route('password.edit')
                ->with('warning', 'Pour votre sécurité, choisissez un nouveau mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
