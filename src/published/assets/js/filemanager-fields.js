'use strict';

(function (window, $) {
    if (typeof window.TableBuilder === 'undefined') {
        return;
    }

    function getAdminLang() {
        var matches = document.cookie.match(
            new RegExp('(?:^| )' + 'lang_admin'.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)')
        );
        return matches ? decodeURIComponent(matches[1]) : 'ru';
    }

    var _uploadPrefix = null;
    var _uploadDir    = null;

    function detectUploadInfo() {
        if (_uploadPrefix !== null) return;
        _uploadPrefix = '';
        _uploadDir    = '';
        $('img[src_original], img[data_src_original], input[type="hidden"][data-id-picture]').each(function () {
            var v = $(this).is('input') ? $(this).val() : ($(this).attr('src_original') || $(this).attr('data_src_original'));
            if (!v || v.charAt(0) !== '/') return;
            var segments = v.replace(/^\/+/, '').split('/');
            if (segments.length >= 3) {
                _uploadPrefix = '/' + segments[0];
                _uploadDir    = segments[1];
                return false;
            }
        });
    }

    function toRootPath(url) {
        if (!url) return url;
        try {
            var a = document.createElement('a');
            a.href = url;
            if (a.pathname) url = a.pathname + (a.search || '') + (a.hash || '');
        } catch (e) {}
        url = ('/' + url).replace(/^\/+/, '/');
        detectUploadInfo();
        if (_uploadPrefix && _uploadDir && url.indexOf('/' + _uploadDir + '/') === 0) {
            url = _uploadPrefix + url;
        }
        return url;
    }

    function updateMultiHidden($multiWrapper) {
        var urls = [];
        $multiWrapper.find('ul.dop_foto img').each(function () {
            var u = $(this).attr('src_original') || $(this).attr('data_src_original') || $(this).attr('src') || '';
            u = u && toRootPath(u);
            if (u && urls.indexOf(u) === -1) urls.push(u);
        });
        var json = urls.length ? JSON.stringify(urls) : '[]';
        var $text = $multiWrapper.find('input[type="text"]').first();
        if ($text.length) $text.val(json).trigger('change');
        var $hidden = $multiWrapper.find('input[type="hidden"][name]').first();
        if ($hidden.length) $hidden.val(json);
        $multiWrapper.find('.no_photo').toggle(!urls.length);
    }

    window.TableBuilder.openFileManagerForImage      = function (fieldId) { window.TableBuilder.openFileManagerForField(fieldId); };
    window.TableBuilder.openFileManagerForMultiImage = function (fieldId) { window.TableBuilder.openFileManagerForField(fieldId); };

    window.TableBuilder.openFileManagerForField = function (fieldId) {
        var overlayId = 'filemanager_overlay_tb';
        var $overlay = $('#' + overlayId);
        
        if (!$overlay.length) {
            $('body').append(
                '<div id="' + overlayId + '" style="' +
                'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
                'z-index:999999;background:rgba(0,0,0,.5);' + // Increased z-index
                '">' +
                '  <input id="tb_fm_proxy_input" type="text" style="position:absolute;opacity:0;pointer-events:none;" />' +
                '  <div style="' +
                'position:absolute;top:2.5%;left:2.5%;width:95%;height:95%;' +
                'background:#fff;border-radius:4px;display:flex;flex-direction:column;' +
                '">' +
                '    <div style="padding:10px 15px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">' +
                '      <span style="font-size:16px;font-weight:600;">Медіасховище</span>' +
                '      <button type="button" class="close-fm-btn" style="background:none;border:none;font-size:22px;line-height:1;cursor:pointer;" aria-label="Close">' +
                '        &times;' +
                '      </button>' +
                '    </div>' +
                '    <div style="flex:1;overflow:hidden;">' +
                '      <iframe src="" frameborder="0" style="width:100%;height:100%;border:0;"></iframe>' +
                '    </div>' +
                '  </div>' +
                '</div>'
            );
            $overlay = $('#' + overlayId);
            $overlay.on('click mousedown mouseup', function (e) {
                e.stopPropagation();
                if (e.type === 'click' && (e.target === this || $(e.target).hasClass('close-fm-btn'))) {
                    window.TableBuilder.closeFileManagerOverlay();
                }
            });
        }

        $overlay.data('tb-real-field-id', fieldId);
        
        // Prevent ALL modals from closing while our overlay is active
        $(document).on('hide.bs.modal.fm_protection', function (e) {
            if ($('#' + overlayId).is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        var dialogUrl =
            '/packages/tinymce4/plugins/responsivefilemanager/filemanager/dialog.php' +
            '?type=1&field_id=tb_fm_proxy_input' +
            '&relative_url=0&lang=' + encodeURIComponent(getAdminLang());

        $overlay.find('iframe').attr('src', dialogUrl);
        $overlay.show();
        
        // Disable enforceFocus to prevent Bootstrap from fighting over focus
        if ($.fn.modal && $.fn.modal.Constructor) {
            var proto = $.fn.modal.Constructor.prototype;
            if (proto.enforceFocus && !proto._enforceFocusBackup) {
                proto._enforceFocusBackup = proto.enforceFocus;
                proto.enforceFocus = function() {};
            }
        }
    };

    window.TableBuilder.closeFileManagerOverlay = function () {
        var $overlay = $('#filemanager_overlay_tb');
        if (!$overlay.length || $overlay.is(':hidden')) return;

        var iframe = $overlay.find('iframe')[0];
        try { if (iframe) iframe.src = 'about:blank'; } catch (e) {}

        $overlay.hide();
        $(document).off('hide.bs.modal.fm_protection');
        
        // Restore enforceFocus
        if ($.fn.modal && $.fn.modal.Constructor) {
            var proto = $.fn.modal.Constructor.prototype;
            if (proto._enforceFocusBackup) {
                proto.enforceFocus = proto._enforceFocusBackup;
                delete proto._enforceFocusBackup;
            }
        }
    };

    window.TableBuilder.handleFilemanagerSelection = function (fieldId) {
        var $input = $('#' + fieldId);
        if (!$input.length) return;
        var value = $input.val();
        if (!value) return;
        var $multiWrapper = $input.closest('.multi_pictures');
        if ($multiWrapper.length) {
            var urls;
            try {
                var parsed = JSON.parse(value);
                urls = $.isArray(parsed) ? parsed : (parsed ? [parsed] : []);
            } catch (e) { urls = [value]; }
            urls = urls.map(toRootPath);
            var $ul = $multiWrapper.find('.tb-uploaded-image-container_' + fieldId + ', .tb-uploaded-image-container').first().find('ul.dop_foto');
            if (!$ul.length) $ul = $multiWrapper.find('ul.dop_foto');
            var seenMap = {};
            $ul.find('img').each(function () {
                var u = $(this).attr('src_original') || $(this).attr('data_src_original') || $(this).attr('src') || '';
                var n = u && toRootPath(u);
                if (n) seenMap[n] = true;
            });
            urls.forEach(function (url) {
                if (!url || seenMap[url]) return;
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
            updateMultiHidden($multiWrapper);
            return;
        }
        var $section = $input.closest('.pictures_input_field');
        if (!$section.length) return;
        try {
            var parsedSingle = JSON.parse(value);
            if ($.isArray(parsedSingle) && parsedSingle.length) value = parsedSingle[0];
        } catch (e) {}
        value = toRootPath(value);
        var $hidden = $section.find('input[type="hidden"][data-id-picture="' + fieldId + '"]');
        if (!$hidden.length) $hidden = $section.find('input[type="hidden"]').first();
        $hidden.val(value);
        $input.val(value).trigger('change');
        var $imageContainer = $section.find('.image-container_' + fieldId);
        if (!$imageContainer.length) $imageContainer = $section.find('.tb-uploaded-image-container').first();
        if ($imageContainer.length) {
            $imageContainer.html(
                '<div style="position:relative;display:inline-block;">' +
                '<img src="' + value + '" style="max-width:200px"/>' +
                '<div class="tb-btn-delete-wrap">' +
                '<button class="btn btn-default btn-sm tb-btn-image-delete" type="button" onclick="TableBuilder.deleteSingleImage(\'' + fieldId + '\',this);">' +
                '<i class="fa fa-times"></i></button></div>' +
                '</div>'
            );
        }
    };

    window.responsive_filemanager_callback = function () {
        var $overlay    = $('#filemanager_overlay_tb');
        var realFieldId = $overlay.data('tb-real-field-id');
        var proxyValue  = $('#tb_fm_proxy_input').val();
        if (realFieldId && proxyValue) {
            var $realInput = $('#' + realFieldId);
            if ($realInput.length) {
                $realInput.val(proxyValue);
                window.TableBuilder.handleFilemanagerSelection(realFieldId);
            }
        }
        $('#tb_fm_proxy_input').val('');
        setTimeout(function () {
            window.TableBuilder.closeFileManagerOverlay();
        }, 50);
    };

    $(function () {
        $('.multi_pictures').each(function () { updateMultiHidden($(this)); });
        $(document).on('sortupdate', 'ul.dop_foto', function () {
            var $mw = $(this).closest('.multi_pictures');
            if ($mw.length) updateMultiHidden($mw);
        });
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