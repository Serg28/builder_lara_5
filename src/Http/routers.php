<?php

    Route::pattern('tree', '[a-z0-9-_]+');
    Route::pattern('any', '[a-z0-9-_/\]+');

    Route::group(['middleware' => ['web']], function () {
        Route::get('login', 'Linecore\Cms\LoginController@index')->name('cms.login.index');
        Route::post('login', 'Linecore\Cms\LoginController@store')->name('cms.login.store');
    });

    Route::group(['middleware' => ['web']], function () {
        Route::group(
            ['prefix' => 'admin', 'middleware' => 'auth.admin'],
            function () {
                Route::post('change-range-card', 'Linecore\Cms\ChangeRangeController@changeValue');
                Route::post('change-range-trend', 'Linecore\Cms\ChangeRangeController@changeValue');

                Route::post(
                    '/save_edit_on_site',
                    'Linecore\Cms\ControllersNew\EditContentOnSiteController@index'
                );

                Route::get('logout', 'Linecore\Cms\LoginController@logout')->name('cms.logout');

                Route::get('/logs', 'Linecore\Cms\LogViewerController@index');

                Route::any(
                    '/tree',
                    'Linecore\Cms\TreeAdminController@index'
                );

                Route::any(
                    '/actions/tree',
                    'Linecore\Cms\TreeAdminController@handle'
                );

                Route::post(
                    '/show-all-tree',
                    'Linecore\Cms\TreeAdminController@showAll'
                );

                Route::post(
                    '/photo/upload',
                    'Linecore\Cms\PhotoController@upload'
                );

                Route::post(
                    '/file/upload',
                    'Linecore\Cms\PhotoController@upload'
                );

                Route::any(
                    '/photo/select_photos',
                    'Linecore\Cms\PhotoController@selectPhotos'
                );

                Route::post(
                    '/actions/{page_admin}',
                    'Linecore\Cms\TableAdminController@actionsPage'
                );

                Route::get(
                    '/actions/{page_admin}/export',
                    'Linecore\Cms\ExportController@download'
                );

                Route::get('/', 'Linecore\Cms\TBController@showDashboard');

                Route::post('upload_image', 'Linecore\Cms\EditorController@uploadImage');
                Route::post('upload_file', 'Linecore\Cms\EditorController@uploadFile');
                Route::get('load_image', 'Linecore\Cms\EditorController@getUploadedImages');
                Route::post('delete_image', 'Linecore\Cms\EditorController@deleteImages');

                Route::post('quick_edit', 'Linecore\Cms\QuickEditController');

                Route::post('change_skin', 'Linecore\Cms\TBController@changeSkin');
                Route::get('change_lang', 'Linecore\Cms\TBController@changeLanguage')->name('change_lang');

                Route::post('save_croped_img', 'Linecore\Cms\TBController@saveCropImg');

                // Документация для Definition
                Route::get('/docs', '\Linecore\Cms\DocsController@index')->name('admin.docs.index');
                Route::get('/docs/{definition}', 'Linecore\Cms\DocsController@index')
                    ->where('definition', '[A-Za-z0-9_]+')
                    ->name('admin.docs.show');

                //router for pages builder
                Route::get(
                    '/{page_admin}',
                    'Linecore\Cms\TableAdminController@showPage'
                );
                if (Request::ajax()) {
                    Route::get(
                        '/{page_admin}',
                        'Linecore\Cms\TableAdminController@showPagePost'
                    );
                }

                Route::post(
                    '/{page_admin}',
                    'Linecore\Cms\TableAdminController@actionsPage'
                );
                Route::post(
                    '/{page_admin}/fast-save/{id}',
                    'Linecore\Cms\TableAdminController@fastEdit'
                );
            }
        );
    });
