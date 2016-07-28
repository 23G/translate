<?php 

namespace DylanLamers\Translate\Middleware;

use Closure;
use DylanLamers\Translate\Facades\Translate;

class TranslateInitiator {

    public function handle($request, Closure $next){
        Translate::init();
        return $next($request);
    }

}