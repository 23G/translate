<?php

Route::group(['namespace' => 'DylanLamers\Translate\Http\Controllers', 'middleware' => ['web']], function () {
    Route::get('change-locale-to/{locale}', 'TranslateController@setLocale')->name('translate.locale');
});
