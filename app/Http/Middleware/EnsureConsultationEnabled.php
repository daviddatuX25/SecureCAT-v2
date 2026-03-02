<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsultationEnabled
{
    /**
     * Block consultation routes when the consultation feature is disabled in settings.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::consultationEnabled()) {
            abort(403, 'Consultation is disabled in settings.');
        }

        return $next($request);
    }
}
