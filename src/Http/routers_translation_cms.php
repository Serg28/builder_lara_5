<?php

Route::group(['middleware' => ['web']], function () {
    Route::group(
        ['prefix' => 'admin', 'middleware' => 'auth.admin'],
        function () {
            Route::any('translations_cms/phrases', 'Linecore\Cms\Http\Controllers\TranslateCmsController@index');

            if (Request::ajax()) {
                Route::post('translations_cms/create', 'Linecore\Cms\Http\Controllers\TranslateCmsController@create');
                Route::post('translations_cms/translate', 'Linecore\Cms\Http\Controllers\TranslateCmsController@doTranslate');
                Route::post('translations_cms/add_record', 'Linecore\Cms\Http\Controllers\TranslateCmsController@saveTranslate');
                Route::post('translations_cms/change-text-lang', 'Linecore\Cms\Http\Controllers\TranslateCmsController@changeTranslate');
                Route::post('translations_cms/remove/{id}', 'Linecore\Cms\Http\Controllers\TranslateCmsController@destroy');
            }
        }
    );
});
