<section class="{{ $field->getClassName() }}">
    @php
        $id = $field->getAllData()?->id ?? 0;
        $groups = $groups ?? [];
    @endphp
    <label class="label" for="{{ $field->getNameField() }}">{{ $field->getName() }}</label>
    <input type="hidden" name="{{ $field->getNameField() }}" id="json_ext_input_{{ $id }}"
        value="{{ $field->getValue() }}">
    <div style="position: relative">
        <div class="div_input">
            <div class="input_content">
                <div id="form_{{ $field->getNameField() }}-{{ $id }}"
                    data-x-wrapper-{{ $id }}="{{ $field->getNameField() }}">
                    <div class="col-md-12" style="margin-bottom: 15px;">
                        <div class="json-ext-groups"
                            style="display: flex;align-items: flex-start; gap: 8px;flex-direction: column;">
                            @foreach ($groups as $groupIndex => $pairs)
                                @include('admin::form.fields.partials.json_ext_group', [
                                    'id' => $id,
                                    'pairs' => $pairs,
                                ])
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" style="margin-top:15px"
                            data-add-group-{{ $id }}>{{ __cms('Добавить группу') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($field->getComment())
        <div class="note">
            {!! $field->getComment() !!}
        </div>
    @endif
</section>

<script>
    $(document).ready(function() {
        const wrapper = $('#form_{{ $field->getNameField() }}-{{ $id }}');
        const addGroupBtn = wrapper.find('[data-add-group-{{ $id }}]');
        const hiddenInput = $('#json_ext_input_{{ $id }}');

        // Предотвращаем повторную инициализацию скрипта для этого инстанса
        if (wrapper.data('jsonExtInit')) {
            return;
        }
        wrapper.data('jsonExtInit', true);

        const groupTemplate = `
@include('admin::form.fields.partials.json_ext_group', ['id' => $id, 'pairs' => []])
        `;

        const pairTemplate = `
@include('admin::form.fields.partials.json_ext_pair', ['id' => $id, 'k' => '', 'v' => ''])
        `;

        function bindPairs(groupEl) {
            groupEl.find('[data-remove-group-{{ $id }}]').off('click').on('click', function() {
                groupEl.remove();
                updateJson();
            });

            groupEl.find('[data-up-group-{{ $id }}]').off('click').on('click', function() {
                groupEl.prev().before(groupEl);
                updateJson();
            });

            groupEl.find('[data-down-group-{{ $id }}]').off('click').on('click', function() {
                groupEl.next().after(groupEl);
                updateJson();
            });

            bindInputs(groupEl);
        }

        function bindInputs(scope) {
            scope.find('input').off('input').on('input', updateJson);
        }

        addGroupBtn.off('click').on('click', function() {
            const newGroup = $(groupTemplate);
            wrapper.find('.json-ext-groups').append(newGroup);
            bindPairs(newGroup);
            updateJson();
        });

        wrapper.find('[data-group-{{ $id }}]').each(function() {
            bindPairs($(this));
        });

        // Делегированные обработчики для динамически добавленных пар
        wrapper.off('click', '[data-remove-pair-{{ $id }}]').on('click',
            '[data-remove-pair-{{ $id }}]',
            function() {
                $(this).closest('[data-pair-{{ $id }}]').remove();
                updateJson();
            });

        wrapper.off('click', '[data-add-pair-{{ $id }}]').on('click',
            '[data-add-pair-{{ $id }}]',
            function() {
                const groupEl = $(this).closest('[data-group-{{ $id }}]');
                const container = groupEl.find('[data-pairs-container-{{ $id }}]');
                const newPair = $(pairTemplate);
                container.append(newPair);
                bindInputs(groupEl);
                updateJson();
            });

        // Перемещение пары внутри группы
        wrapper.off('click', '[data-up-pair-{{ $id }}]').on('click',
            '[data-up-pair-{{ $id }}]',
            function() {
                const pair = $(this).closest('[data-pair-{{ $id }}]');
                const prev = pair.prev('[data-pair-{{ $id }}]');
                if (prev.length) {
                    prev.before(pair);
                    updateJson();
                }
            });

        wrapper.off('click', '[data-down-pair-{{ $id }}]').on('click',
            '[data-down-pair-{{ $id }}]',
            function() {
                const pair = $(this).closest('[data-pair-{{ $id }}]');
                const next = pair.next('[data-pair-{{ $id }}]');
                if (next.length) {
                    next.after(pair);
                    updateJson();
                }
            });

        function updateJson() {
            const groups = [];
            wrapper.find('[data-group-{{ $id }}]').each(function() {
                const pairs = {};
                $(this).find('[data-pair-{{ $id }}]').each(function() {
                    const key = $(this).find('[data-name="key"]').val();
                    const val = $(this).find('[data-name="value"]').val();
                    if (key !== '') {
                        pairs[key] = val;
                    }
                });
                groups.push(pairs);
            });
            hiddenInput.val(JSON.stringify(groups));
        }

        updateJson();
    });
</script>
