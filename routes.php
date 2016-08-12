<?php

Route::group(['namespace' => 'DylanLamers\Translate\Http\Controllers', 'prefix' => 'cms/page', 'middleware' => ['web']], function () {
    Route::get('change-locale-to/{locale}', 'TranslateController@setLocale')->name('translate.locale');
});
