<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Localize
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = config('app.available_locales', ['en', 'fr']);
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        // Récupérer le header Accept-Language
        $acceptLanguage = (string) $request->header('Accept-Language', $fallbackLocale);

        // Extraire la langue principale (fr-FR -> fr, en-US -> en)
        // Gère aussi les formats complexes: "fr-FR,en;q=0.9,en-US;q=0.8"
        $primaryLanguage = explode(',', $acceptLanguage)[0];
        $locale = substr($primaryLanguage, 0, 2);

        // Valider que la langue est supportée
        if (! in_array($locale, $availableLocales, true)) {
            $locale = $fallbackLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
