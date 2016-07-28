<?php

namespace DylanLamers\Translate\Middleware;

use Closure;
use DylanLamers\Translate\Facades\Translate;

class TranslateInitiator
{

    public function handle($request, Closure $next)
    {
        $possibleCode = false;

        if (config('translate.use_nice_urls') && substr($request->path(), 2, 1) === '/') {
            $possibleCode = substr($request->path(), 0, 2);
        }

        Translate::init($possibleCode);

        return $next($request);
    }
}
