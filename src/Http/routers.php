<?php

$adminPrefix = config('builder.cms.admin_prefix', 'admin');
$loginPath = config('builder.cms.login_path', 'login');

    Route::pattern('tree', '[a-z0-9-_]+');
    Route::pattern('any', '[a-z0-9-_/\]+');

    Route::group(['middleware' => ['web']], function () use ($loginPath) {
        Route::get($loginPath, 'Vis\Builder\LoginController@index')->name('cms.login.index');
        Route::post($loginPath, 'Vis\Builder\LoginController@store')->name('cms.login.store');
    });

    Route::group(['middleware' => ['web']], function () use ($adminPrefix) {
        Route::group(
            ['prefix' => $adminPrefix, 'middleware' => 'auth.admin'],
            function () {
                Route::post('change-range-card', 'Vis\Builder\ChangeRangeController@changeValue');
                Route::post('change-range-trend', 'Vis\Builder\ChangeRangeController@changeValue');

                Route::post(
                    '/save_edit_on_site',
                    'Vis\Builder\ControllersNew\EditContentOnSiteController@index'
                );

                Route::get('logout', 'Vis\Builder\LoginController@logout')->name('cms.logout');

                Route::get('/logs', 'Vis\Builder\LogViewerController@index');

                Route::any(
                    '/tree',
                    'Vis\Builder\TreeAdminController@index'
                );

                Route::any(
                    '/actions/tree',
                    'Vis\Builder\TreeAdminController@handle'
                );

                Route::post(
                    '/show-all-tree',
                    'Vis\Builder\TreeAdminController@showAll'
                );

                Route::post(
                    '/photo/upload',
                    'Vis\Builder\PhotoController@upload'
                );

                Route::post(
                    '/file/upload',
                    'Vis\Builder\PhotoController@upload'
                );

                Route::any(
                    '/photo/select_photos',
                    'Vis\Builder\PhotoController@selectPhotos'
                );

                Route::post(
                    '/actions/{page_admin}',
                    'Vis\Builder\TableAdminController@actionsPage'
                );

                Route::get(
                    '/actions/{page_admin}/export',
                    'Vis\Builder\ExportController@download'
                );

                Route::get('/', 'Vis\Builder\TBController@showDashboard');

                Route::post('upload_image', 'Vis\Builder\EditorController@uploadImage');
                Route::post('upload_file', 'Vis\Builder\EditorController@uploadFile');
                Route::get('load_image', 'Vis\Builder\EditorController@getUploadedImages');
                Route::post('delete_image', 'Vis\Builder\EditorController@deleteImages');

                Route::post('quick_edit', 'Vis\Builder\QuickEditController');

                Route::post('change_skin', 'Vis\Builder\TBController@changeSkin');
                Route::get('change_lang', 'Vis\Builder\TBController@changeLanguage')->name('change_lang');

                Route::post('save_croped_img', 'Vis\Builder\TBController@saveCropImg');

                // Документация для Definition
                Route::get('/docs', '\Vis\Builder\DocsController@index')->name('admin.docs.index');
                Route::get('/docs/{definition}', 'Vis\Builder\DocsController@index')
                    ->where('definition', '[A-Za-z0-9_]+')
                    ->name('admin.docs.show');

                //router for pages builder
                Route::get(
                    '/{page_admin}',
                    'Vis\Builder\TableAdminController@showPage'
                );
                if (Request::ajax()) {
                    Route::get(
                        '/{page_admin}',
                        'Vis\Builder\TableAdminController@showPagePost'
                    );
                }

                Route::post(
                    '/{page_admin}',
                    'Vis\Builder\TableAdminController@actionsPage'
                );
                Route::post(
                    '/{page_admin}/fast-save/{id}',
                    'Vis\Builder\TableAdminController@fastEdit'
                );
                Route::get('/notifications/all', [Vis\Builder\NotificationController::class, 'index'])->name('admin.notifications');
                Route::post('/notifications/mark-read', [Vis\Builder\NotificationController::class, 'markRead'])->name('admin.notifications.mark-read');
                Route::post('/admin/notifications/mark-all-read', [Vis\Builder\NotificationController::class, 'markAllRead'])->name('admin.notifications.mark-all-read');
            }
        );
    });
