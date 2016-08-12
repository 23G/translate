<?php

namespace DylanLamers\Translate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application as App;

class TranslateController extends Controller
{
    public function setLocale(App $app, $languageCode)
    {
        $app->setLocale($languageCode);

        return redirect()->back();
    }
}
