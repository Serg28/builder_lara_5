<?php

Route::group(['middleware' => ['web']], function () {
    Route::group(
        ['prefix' => 'admin', 'middleware' => 'auth.admin'],
        function () {
            Route::any('/settings/settings_all', 'Linecore\Cms\SettingsController@fetchIndex')
                    ->name('m.show_settings');

            if (Request::ajax()) {
                Route::post('/settings/create_pop', 'Linecore\Cms\SettingsController@fetchCreate');
                Route::post('/settings/add_record', 'Linecore\Cms\SettingsController@doSave');
                Route::post('/settings/delete', 'Linecore\Cms\SettingsController@doDelete');
                Route::post('/settings/edit_record', 'Linecore\Cms\SettingsController@fetchEdit');
                Route::post('/settings/del_select', 'Linecore\Cms\SettingsController@doDeleteSettingSelect');
                Route::post('/settings/fast_save', 'Linecore\Cms\SettingsController@doFastSave');
            }
        }
    );
});
