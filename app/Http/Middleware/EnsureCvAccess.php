<?php

namespace App\Http\Middleware;

use App\Models\AccessCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-validates the unlocked access code on every request, so deactivating
 * or expiring a code in the admin takes effect immediately.
 */
class EnsureCvAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = AccessCode::find($request->session()->get('access_code_id'));

        if (! $code?->isUsable()) {
            $request->session()->forget('access_code_id');

            return redirect()->route('unlock');
        }

        $request->attributes->set('accessCode', $code);

        return $next($request);
    }
}
