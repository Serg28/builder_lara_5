{{-- TODO: автогенерация страницы и формы --}}
<div id="content">
    <div class="row" id="content_admin">
        <section id="widget-grid" class="">
            <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12" style="padding-right: 0px; padding-left: 0px;">
                <div id="table-preloader" class="smoke_lol"><i class="fa fa-gear fa-4x fa-spin"></i></div>
                <div class="jarviswidget jarviswidget-color-blue" id="wid-id-1" data-widget-editbutton="false"
                     data-widget-colorbutton="false" data-widget-deletebutton="false" data-widget-sortable="false">

                    <header>
                        <span class="widget-icon"> <i class="fa fa-table"></i> </span>
                        <h2>{{ $isCreating ? __cms('Создание документа') : __cms('Редактирование') . ': ' . $name }}</h2>
                        <span class="btn btn-success btn-sm" style="float: right;margin-top:4px;margin-right:7px;"
                              onclick="$('#doc-form').find('button[type=submit]').click();">
                                <span class="glyphicon glyphicon-floppy-disk"></span> {{__cms('Сохранить')}}
                            </span>
                        <span class="jarviswidget-loader"><i class="fa fa-refresh fa-spin"></i></span>
                    </header>
                </div>
            </article>
            <div class="row">
                <div class="col-md-12">
                            <form action="{{ url()->current() }}" method="post" id="doc-form" class="smart-form" style="border:1px solid #ccc">
                                @csrf
                                <input type="hidden" name="original_name" value="{{ $name }}">
                                <input type="hidden" name="query_type" value="{{ $isCreating ? 'save_add_form' : 'save_edit_form'}}">

                                <fieldset>
                                    <div class="col-lg-12 col-md-12">
                                        <section class="section_name">
                                            <label class="label" for="name">{{__cms('Документация для формы')}}</label>
                                            <div style="position: relative;">
                                                <div class="div_input">
                                                    <div class="input_content">
                                                        <label class="input">
                                                            <input type="text" class="form-control input-sm" id="name" name="name" value="{{ $name }}" autocomplete="off" required>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="note">
                                                <span>{!! __cms('Slug страницы документации. Совпдает с последней частью url страницы, для которой создается документация. Напр., если url:  http://site.com/admin/<b>products</b> то укажите <b>products</b>. Для документации, не привязанной к конкретной форме, укажите любое уникально название латинскими буквами') !!}</span>
                                            </div>
                                        </section>
                                    </div>

                                    <div class="col-lg-12 col-md-12">
                                        <section class="section_name">
                                            <label class="label" for="custom_title">{{__cms('Название документа')}}</label>
                                            <div style="position: relative;">
                                                <div class="div_input">
                                                    <div class="input_content">
                                                        <label class="input">
                                                            <input type="text" class="form-control input-sm" id="custom_title" name="custom_title" value="{{ $customTitle }}" autocomplete="off">
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="note">
                                                <span>{{__cms('Если указано, будет использоваться как заголовок документа')}}</span>
                                            </div>
                                        </section>
                                    </div>

                                   {!! $tinymceHtml !!}

                                    <div class="col-lg-12 col-md-12">
                                        <a href="{{ url()->current() }}" class="btn btn-default">{{__cms('К списку документов')}}</a>
                                        <button type="submit" class="btn btn-success pull-right"><span class="glyphicon glyphicon-floppy-disk"></span> {{__cms('Сохранить')}}</button>
                                    </div>
                                </fieldset>

                            </form>
                </div>
            </div>
        </section>
    </div>
</div>


<script>
    const form = document.getElementById('doc-form');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // если используется TinyMCE – обновляем textarea
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    (window.TableBuilder?.showSuccessNotification ?? alert)(data.message || 'Файл успешно сохранён!');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    (window.TableBuilder?.showErrorNotification ?? alert)(data.error || 'Ошибка сохранения!');
                }
            })
            .catch(() => {
                (window.TableBuilder?.showErrorNotification ?? alert)('Ошибка запроса!');
            });
    });
</script>
