'use strict';

(function (window, $) {
    if (typeof window.TableBuilder === 'undefined') {
        return;
    }

    /**
     * Получить текущий язык админки из cookie или вернуть ru по умолчанию.
     */
    function getAdminLang() {
        var matches = document.cookie.match(
            new RegExp('(?:^|; )' + 'lang_admin'.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)')
        );
        return matches ? decodeURIComponent(matches[1]) : 'ru';
    }

    /**
     * Определить префикс upload-директории и саму папку загрузок динамически,
     * сканируя src_original / data_src_original существующих на странице изображений.
     *
     * Например: "/storage/files/foo.jpg" → prefix="/storage", uploadDir="files"
     * Если файловый менеджер вернёт "/files/foo.jpg", toRootPath восстановит "/storage/files/foo.jpg".
     */
    var _uploadPrefix = null;   // e.g. "/storage"
    var _uploadDir    = null;   // e.g. "files"

    function detectUploadInfo() {
        if (_uploadPrefix !== null) return;
        _uploadPrefix = '';
        _uploadDir    = '';

        var found = false;
        $('img[src_original], img[data_src_original], input[type="hidden"][data-id-picture]').each(function () {
            var v = $(this).is('input') ? $(this).val() : ($(this).attr('src_original') || $(this).attr('data_src_original'));
            if (!v || v.charAt(0) !== '/') return;
            var segments = v.replace(/^\/+/, '').split('/');
            // Need at least 3 segments: prefix / uploadDir / filename
            if (segments.length >= 3) {
                _uploadPrefix = '/' + segments[0];
                _uploadDir    = segments[1];
                found = true;
                return false; // break
            }
        });
    }

    /**
     * Нормализовать путь к файлу:
     * - Убрать протокол/домен, оставить путь от корня
     * - Привести к единственному ведущему слешу
     * - Восстановить пропущенный префикс upload-директории (определяется динамически)
     */
    function toRootPath(url) {
        if (!url) return url;
        try {
            var a = document.createElement('a');
            a.href = url;
            if (a.pathname) url = a.pathname + (a.search || '') + (a.hash || '');
        } catch (e) {}

        // Единственный ведущий слеш
        url = ('/' + url).replace(/^\/+/, '/');

        // Восстановить префикс если путь начинается сразу с папки загрузок
        detectUploadInfo();
        if (_uploadPrefix && _uploadDir && url.indexOf('/' + _uploadDir + '/') === 0) {
            url = _uploadPrefix + url;
        }

        return url;
    }

    /**
     * Пересобрать значение скрытого text-инпута мультиполя из текущих превью.
     */
    function updateMultiHidden($multiWrapper) {
        var urls = [];
        $multiWrapper.find('ul.dop_foto img').each(function () {
            var u = $(this).attr('src_original') || $(this).attr('data_src_original') || $(this).attr('src') || '';
            u = u && toRootPath(u);
            if (u && urls.indexOf(u) === -1) urls.push(u);
        });

        var json = urls.length ? JSON.stringify(urls) : '[]';

        // Обновить [type=text] — наш основной носитель значения (используется при выборе через файловый менеджер)
        var $text = $multiWrapper.find('input[type="text"]').first();
        if ($text.length) $text.val(json).trigger('change');

        // Обновить [type=hidden name] — носитель значения при сабмите формы.
        // Ищем именно с атрибутом name, чтобы не зацепить служебные hidden других компонентов.
        var $hidden = $multiWrapper.find('input[type="hidden"][name]').first();
        if ($hidden.length) $hidden.val(json);

        $multiWrapper.find('.no_photo').toggle(!urls.length);
    }

    /**
     * Открыть файловый менеджер для одиночного или мультиполя изображений.
     */
    window.TableBuilder.openFileManagerForImage      = function (fieldId) { window.TableBuilder.openFileManagerForField(fieldId); };
    window.TableBuilder.openFileManagerForMultiImage = function (fieldId) { window.TableBuilder.openFileManagerForField(fieldId); };

    window.TableBuilder.openFileManagerForField = function (fieldId) {
        var modalId = 'filemanager_modal_' + fieldId;
        var $modal  = $('#' + modalId);

        if (!$modal.length) {
            $('body').append(
                '<div class="modal fade filemanager-modal" id="' + modalId + '" tabindex="-1" role="dialog" data-backdrop="static" style="z-index:10600;">' +
                '  <div class="modal-dialog modal-lg" style="width:95%;max-width:1400px;">' +
                '    <div class="modal-content">' +
                '      <div class="modal-header">' +
                '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                '        <h4 class="modal-title">Медиахранилище</h4>' +
                '      </div>' +
                '      <div class="modal-body" style="height:75vh;padding:0;">' +
                '        <iframe src="" frameborder="0" style="width:100%;height:100%;border:0;"></iframe>' +
                '      </div>' +
                '    </div>' +
                '  </div>' +
                '</div>'
            );
            $modal = $('#' + modalId);
        }

        var dialogUrl =
            '/packages/tinymce4/plugins/responsivefilemanager/filemanager/dialog.php' +
            '?type=1&field_id=' + encodeURIComponent(fieldId) +
            '&relative_url=0&lang=' + encodeURIComponent(getAdminLang());

        $modal.data('tb-return-focus', document.activeElement);

        $modal.off('.tb-fm')
            .on('hide.bs.modal.tb-fm', function () {
                var $m      = $(this);
                var iframe  = $m.find('iframe')[0];
                try { if (iframe && iframe.contentWindow) iframe.contentWindow.blur(); } catch (e) {}
                var el = $m.data('tb-return-focus');
                if (!el || !document.contains(el)) el = document.getElementById(fieldId);
                if (el && typeof el.focus === 'function') {
                    setTimeout(function () { try { el.focus(); } catch (e) {} }, 0);
                } else {
                    var body    = document.body;
                    var prevTab = body.getAttribute('tabindex');
                    body.setAttribute('tabindex', '-1');
                    setTimeout(function () {
                        try { body.focus(); } catch (e) {}
                        if (prevTab === null) body.removeAttribute('tabindex');
                        else body.setAttribute('tabindex', prevTab);
                    }, 0);
                }
            })
            .on('hidden.bs.modal.tb-fm', function () {
                $(this).find('iframe').attr('src', '');
            });

        $modal.one('shown.bs.modal', function () {
            $(this).find('iframe').attr('src', dialogUrl);
        }).modal('show');
    };

    /**
     * Обработка выбранных файлов из Responsive Filemanager.
     * Вызывается через глобальный callback responsive_filemanager_callback.
     */
    window.TableBuilder.handleFilemanagerSelection = function (fieldId) {
        var $input = $('#' + fieldId);
        if (!$input.length) return;

        var value = $input.val();
        if (!value) return;

        var $multiWrapper = $input.closest('.multi_pictures');

        // ── МУЛЬТИПОЛЕ ──────────────────────────────────────────────────────────
        if ($multiWrapper.length) {
            var urls;
            try {
                var parsed = JSON.parse(value);
                urls = $.isArray(parsed) ? parsed : (parsed ? [parsed] : []);
            } catch (e) {
                urls = [value];
            }
            urls = urls.map(toRootPath);

            var $ul = $multiWrapper.find('.tb-uploaded-image-container_' + fieldId + ', .tb-uploaded-image-container').first().find('ul.dop_foto');
            if (!$ul.length) $ul = $multiWrapper.find('ul.dop_foto');

            // Нормализовать и дедублировать уже существующие элементы
            var seenMap = {};
            $ul.find('img').each(function () {
                var $img = $(this);
                var orig = $img.attr('src_original') || $img.attr('data_src_original') || $img.attr('src') || '';
                var norm = orig && toRootPath(orig);
                if (!norm) return;
                $img.attr('src_original', norm).attr('data_src_original', norm);
                if (seenMap[norm]) {
                    $img.closest('li').remove();
                } else {
                    seenMap[norm] = true;
                }
            });

            // Добавить новые, пропуская дубли
            urls.forEach(function (url) {
                if (!url || seenMap[url]) return;
                $ul.find('img[data_src_original="' + url.replace(/([\\"'])/g, '\\$1') + '"]').closest('li').remove();
                $ul.append(
                    '<li>' +
                    '<img src="' + url + '" data_src_original="' + url + '" src_original="' + url + '" width="120px">' +
                    '<div class="tb-btn-delete-wrap">' +
                    '<button class="btn2 btn-default btn-sm tb-btn-image-delete" type="button" onclick="TableBuilder.deleteImage(this);">' +
                    '<i class="fa fa-times"></i></button></div>' +
                    '</li>'
                );
                seenMap[url] = true;
            });

            var $fileInput = $multiWrapper.find('input[type=file]').first();
            if ($fileInput.length && typeof window.TableBuilder.setInputImages === 'function') {
                window.TableBuilder.setInputImages($fileInput);
            }
            updateMultiHidden($multiWrapper);
            return;
        }

        // ── ОДИНОЧНОЕ ИЗОБРАЖЕНИЕ (включая image_lang) ──────────────────────────
        var $section = $input.closest('.pictures_input_field');
        if (!$section.length) return;

        // Если файловый менеджер вернул JSON-массив — взять первый элемент
        try {
            var parsedSingle = JSON.parse(value);
            if ($.isArray(parsedSingle) && parsedSingle.length) value = parsedSingle[0];
        } catch (e) {}

        value = toRootPath(value);

        var $hidden = $section.find('input[type="hidden"][data-id-picture="' + fieldId + '"]');
        if (!$hidden.length) $hidden = $section.find('input[type="hidden"]').first();
        $hidden.val(value);
        $input.val(value);

        var $imageContainer = $section.find('.image-container_' + fieldId);
        if (!$imageContainer.length) $imageContainer = $section.find('.tb-uploaded-image-container').first();
        if (!$imageContainer.length) return;

        $imageContainer.html(
            '<div style="position:relative;display:inline-block;">' +
            '<img src="' + value + '" style="max-width:200px"/>' +
            '<div class="tb-btn-delete-wrap">' +
            '<button class="btn btn-default btn-sm tb-btn-image-delete" type="button" onclick="TableBuilder.deleteSingleImage(\'' + fieldId + '\',this);">' +
            '<i class="fa fa-times"></i></button></div>' +
            '</div>'
        );
    };

    /**
     * Глобальный callback Responsive Filemanager — вызывается из iframe после выбора файла.
     */
    window.responsive_filemanager_callback = function (fieldId) {
        window.TableBuilder.handleFilemanagerSelection(fieldId);

        var $modal = $('#filemanager_modal_' + fieldId);
        if ($modal.length) {
            var iframe = $modal.find('iframe')[0];
            try { if (iframe && iframe.contentWindow) iframe.contentWindow.blur(); } catch (e) {}
            try { if ($modal[0].contains(document.activeElement)) document.activeElement.blur(); } catch (e) {}
            try { if (iframe) iframe.src = 'about:blank'; } catch (e) {}
            setTimeout(function () { $modal.modal('hide'); }, 0);
        }
    };

    // ── Инициализация при загрузке DOM ────────────────────────────────────────
    $(function () {
        // Сбросить кеш: prefix определяется из уже отрендеренного DOM
        _uploadPrefix = null;
        _uploadDir    = null;

        // Нормализовать атрибуты оригинальных путей на всех существующих картинках
        $('img[src_original], img[data_src_original]').each(function () {
            var $img = $(this);
            $.each(['src_original', 'data_src_original'], function (_, attr) {
                var v = $img.attr(attr);
                if (v) $img.attr(attr, toRootPath(v));
            });
        });

        // Пересобрать значения всех мультиполей
        $('.multi_pictures').each(function () { updateMultiHidden($(this)); });

        // Следить за изменениями списка картинок (сортировка, удаление)
        var MutationObs = window.MutationObserver || window.WebKitMutationObserver;
        $('.multi_pictures').each(function () {
            var $mw = $(this);
            var $ul = $mw.find('ul.dop_foto').first();
            if (!$ul.length || $mw.data('tb-observer-attached')) return;
            if (MutationObs) {
                new MutationObs(function () {
                    setTimeout(function () { updateMultiHidden($mw); }, 0);
                }).observe($ul[0], { childList: true });
            } else {
                $ul.on('DOMNodeRemoved DOMNodeInserted', function () {
                    setTimeout(function () { updateMultiHidden($mw); }, 0);
                });
            }
            $mw.data('tb-observer-attached', true);
        });

        // Патч setInputImages.
        //
        // Оригинальный TableBuilder.setInputImages(context):
        //   - ищет картинки через $(context).parents('.multi_pictures').find('ul li img')
        //   - пишет результат в $(context).parents('.multi_pictures').find('[type=hidden]')
        //   Работает корректно для СТАРОГО поля, где context = [type=file].
        //
        // В НОВОМ поле нет [type=file]: сортировка передаёт пустой jQuery-объект,
        // поэтому оригинал ничего не делает и не находит нужный wrapper.
        //
        // Наш патч после origSet дополнительно запускает updateMultiHidden:
        //   - если context найден и находится внутри .multi_pictures → обновляем именно его (старое поле)
        //   - если context пустой → это вызов из sortable нового поля; находим wrapper
        //     по ближайшему ul.dop_foto, который sortable передаёт через `this` в update-callback,
        //     но здесь мы его уже не имеем — поэтому патчим сам sortable отдельно (см. ниже).
        if (typeof window.TableBuilder.setInputImages === 'function' && !window.TableBuilder._setInputImagesPatched) {
            var origSet = window.TableBuilder.setInputImages;
            window.TableBuilder.setInputImages = function (input) {
                var res = origSet.apply(this, arguments);
                var $mw = $(input).closest('.multi_pictures');
                if ($mw.length) updateMultiHidden($mw);
                return res;
            };
            window.TableBuilder._setInputImagesPatched = true;
        }

        // Патч sortable для НОВЫХ полей (без [type=file]).
        // Оригинальный blade-код: update → find("[type=file]") → setInputImages.
        // В новом поле [type=file] нет → setInputImages ничего не делает.
        //
        // Blade инициализирует sortable через $('.dop_foto') без контекста —
        // один вызов на все ul сразу. Переопределять общий update-callback опасно
        // при нескольких полях на странице: closure захватит неверный $mw.
        //
        // Безопасное решение: вешаем обработчик события 'sortupdate' на каждый $ul
        // индивидуально. jQuery UI триггерит 'sortupdate' на элементе после update-callback,
        // только при реальном изменении порядка. DOM к этому моменту уже обновлён.
        $(document).on('sortupdate', 'ul.dop_foto', function () {
            var $mw = $(this).closest('.multi_pictures');
            if ($mw.length) updateMultiHidden($mw);
        });

        // Обновить hidden перед отправкой формы
        $(document).on('submit', 'form', function () {
            $(this).find('.multi_pictures').each(function () { updateMultiHidden($(this)); });
        });

        // Патч deleteImage — пересобирать hidden строго после удаления элемента из DOM
        if (typeof window.TableBuilder.deleteImage === 'function' && !window.TableBuilder._deleteImagePatched) {
            var origDel = window.TableBuilder.deleteImage;
            window.TableBuilder.deleteImage = function (btn) {
                var $mw = $(btn).closest('.multi_pictures');
                var res = origDel.apply(this, arguments);
                if ($mw.length) updateMultiHidden($mw);
                return res;
            };
            window.TableBuilder._deleteImagePatched = true;
        }
    });

})(window, jQuery);