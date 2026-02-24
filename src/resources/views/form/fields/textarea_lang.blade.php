<section class="multilang {{$field->getClassName()}}">
    <div class="tab-pane active">

        <ul class="nav nav-tabs tabs-pull-right">
            <label class="label pull-left" style="line-height: 32px;">{{$field->getName()}}</label>
            @foreach ($field->getLanguage() as $tab)
                <li class="{{$loop->first ? 'active' : ''}}">
                    <a href="#{{$field->getNameFieldLangTab($definition, $tab)}}" class="tab_{{$tab->language}}" data-toggle="tab">{{$tab->language}}</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content padding-5">
            @foreach ($field->getLanguage() as $tab)
                <div class="tab-pane section_tab_{{$tab->language}} {{ $loop->first ? 'active' : '' }}" id="{{$field->getNameFieldLangTab($definition, $tab)}}">
                    <div style="position: relative;">
                        <label class="textarea">
                            <textarea rows="{{$field->getRows()}}"
                                      style="resize: none;overflow-y: hidden;"
                                      class="custom-scroll"
                                      id="{{ $field->getNameField() . $tab->language}}"
                                      data-max-rows="{{$field->hasMaxRows() ? $field->getMaxRows() : ''}}"
                                      name="{{ $field->getNameField()}}[{{$tab->language}}]">{{$field->getValueLanguage($tab->language)}}</textarea>
                            @if ($field->getComment())
                                <div class="note">
                                    {!! $field->getComment() !!}
                                </div>
                            @endif
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {

        function getLineHeight(el) {
            const computed = window.getComputedStyle(el);
            let lineHeight = computed.lineHeight;

            if (lineHeight === 'normal') {
                return parseFloat(computed.fontSize) * 1.2;
            }

            return parseFloat(lineHeight);
        }

        function autoResize(textarea) {
            if (!textarea.dataset.maxRows) return;

            const maxRows = parseInt(textarea.dataset.maxRows);
            const lineHeight = getLineHeight(textarea);

            textarea.style.height = 'auto';

            const scrollHeight = textarea.scrollHeight;
            const maxHeight = lineHeight * maxRows;

            textarea.style.overflowY = scrollHeight > maxHeight ? 'auto' : 'hidden';
            textarea.style.height = Math.min(scrollHeight, maxHeight) + 'px';
        }

        const textareas = document.querySelectorAll(
            'textarea[data-max-rows][id^="{{ $field->getNameField() }}"]'
        );

        textareas.forEach(function(textarea) {

            // input
            textarea.addEventListener('input', function() {
                autoResize(textarea);
            });

            textarea.addEventListener('paste', function() {
                setTimeout(() => autoResize(textarea), 0);
            });

            textarea.addEventListener('cut', function() {
                setTimeout(() => autoResize(textarea), 0);
            });

            // перший рендер (якщо активний таб)
            if ($(textarea).is(':visible')) {
                autoResize(textarea);
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            textareas.forEach(function(textarea) {
                if ($(textarea).is(':visible')) {
                    autoResize(textarea);
                }
            });
        });

    });
</script>