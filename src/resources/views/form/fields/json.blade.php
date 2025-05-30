<section class="{{$field->getClassName()}}">
    @php
        $id = $field->getAllData()->id;
        $valueArray = json_decode($field->getValue() ?? '[]', true) ?? [];
        $isAssociative = $field->isAssociativeArray($valueArray) && !empty($valueArray);
    @endphp
    <label class="label" for="{{ $field->getNameField()}}">{{$field->getName()}}</label>
    <input type="hidden" name="{{ $field->getNameField() }}" id="json_array_input_{{ $id }}" value="{{ $field->getValue() }}">
    <div style="position: relative">
        <div class="div_input">
            <div class="input_content">
                <div id="form_{{ $field->getNameField() }}-{{$id}}" data-x-wrapper-{{$id}}="{{ $field->getNameField() }}">
                    <div class="col-md-12" style="margin-bottom: 15px;">
                        <div class="d-flex" style="display: flex;align-items: flex-start; gap: 1px;flex-direction: column;">
                            @if ($isAssociative)
                                @foreach ($valueArray as $key => $value)
                                    @include('admin::form.fields.partials.json_item', ['id' => $id, 'key' => $key, 'value' => $value, 'isAssociative' => true])
                                @endforeach
                            @else
                                @foreach ($valueArray as $index => $item)
                                    @include('admin::form.fields.partials.json_item', ['id' => $id, 'key' => $index, 'value' => $item, 'isAssociative' => false])
                                @endforeach
                            @endif
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" style="margin-top:15px" data-add-btn-{{$id}}>{{__cms('Добавить еще')}}</button>
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
    $(document).ready(function () {
        const wrapper = $('#form_{{ $field->getNameField() }}-{{$id}}');
        const addButton = wrapper.find('[data-add-btn-{{$id}}]');
        const jsonInput = $('#json_array_input_{{ $id }}');
        const isAssociative = {{ $isAssociative ? 'true' : 'false' }};

        // Шаблон для новой группы
        const groupTemplate = isAssociative ? `
        @include('admin::form.fields.partials.json_item', ['id' => $id, 'key' => '', 'value' => '', 'isAssociative' => true])
        ` : `
        @include('admin::form.fields.partials.json_item', ['id' => $id, 'key' => '', 'value' => '', 'isAssociative' => false])
        `;

        // Обработчик для кнопки "Добавить еще"
        addButton.on('click', function () {
            const newGroup = $(groupTemplate);
            wrapper.find('.d-flex').append(newGroup);
            addButtons(newGroup);
            updateJsonInput();
        });

        // Функция для добавления кнопок "Добавить" и стрелок для перемещения строк
        function addButtons(group) {
            const addButton = $('<button type="button" class="btn btn-primary btn-sm" data-add-btn-{{$id}}>{{__cms('Добавить')}}</button>');
            const upButton = $('<button type="button" class="btn btn-secondary btn-sm" data-up-btn-{{$id}}>&#8593;</button>');
            const downButton = $('<button type="button" class="btn btn-secondary btn-sm" data-down-btn-{{$id}}>&#8595;</button>');
            group.append(addButton);
            group.append(upButton);
            group.append(downButton);

            // Обработчик для кнопки "Добавить"
            addButton.on('click', function () {
                const newGroup = $(groupTemplate);
                group.after(newGroup);
                addButtons(newGroup);
                updateJsonInput();
            });

            // Обработчик для кнопки "Вверх"
            upButton.on('click', function () {
                group.prev().before(group);
                updateJsonInput();
            });

            // Обработчик для кнопки "Вниз"
            downButton.on('click', function () {
                group.next().after(group);
                updateJsonInput();
            });
        }

        // Обработчики для существующих кнопок "Удалить"
        wrapper.find('[data-remove-btn-{{$id}}]').on('click', function () {
            $(this).closest('[data-x-group-{{$id}}]').remove();
            updateJsonInput();
        });

        // Обработчики для существующих полей ввода
        wrapper.find('input').on('input', updateJsonInput);

        // Функция для обновления JSON
        function updateJsonInput() {
            const groups = wrapper.find('[data-x-group-{{$id}}]');
            let data;
            if (isAssociative) {
                data = {};
                groups.each(function () {
                    const key = $(this).find('[data-name="key"]').val();
                    const value = $(this).find('[data-name="value"]').val();
                    if (key) {
                        data[key] = value;
                    }
                });
            } else {
                data = [];
                groups.each(function () {
                    const item = $(this).find('[data-name="item"]').val();
                    if (item) {
                        data.push(item);
                    }
                });
            }
            jsonInput.val(JSON.stringify(data));
        }

        // Если массив пустой, добавляем одну группу по умолчанию
        if (wrapper.find('[data-x-group-{{$id}}]').length === 0) {
            wrapper.find('.d-flex').append($(groupTemplate));
            addButtons(wrapper.find('[data-x-group-{{$id}}]'));
        }

        // Добавление кнопок "Добавить" и стрелок для перемещения строк
        wrapper.find('[data-x-group-{{$id}}]').each(function () {
            addButtons($(this));
        });
    });
</script>