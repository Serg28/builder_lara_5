<section class="{{$field->getClassName()}}">
    <label class="label" for="{{ $field->getNameField()}}">{{$field->getName()}}</label>
    <div style="position: relative;">
        <div class="div_input">
            <div class="input_content">
                <label class="textarea">
                    <textarea rows="{{$field->getRows()}}"
                              style="resize: none;overflow-y: hidden;"
                              class="custom-scroll"
                              id="{{ $field->getNameField() }}"
                              data-max-rows="{{$field->hasMaxRows() ? $field->getMaxRows() : ''}}"

                              @if ($field->isDisabled())
                                  disabled="disabled"
                              @endif

                              name="{{ $field->getNameField() }}">{{ $field->getValue() }}</textarea>
                </label>
                @if ($field->getComment())
                    <div class="note">
                        {!! $field->getComment() !!}
                    </div>
                @endif
            </div>
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

            setTimeout(() => autoResize(textarea), 500);
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