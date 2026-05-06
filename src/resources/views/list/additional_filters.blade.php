{{-- Дополнительные фильтры для списка -- }}
     Используется в случае, если метод getAdditionalFilterFields() определен в классе определения списка.
     Позволяет добавлять дополнительные поля фильтрации, которые не входят в стандартный набор. --}}

{{-- Проверяем, что метод getAdditionalFilterFields() существует в определении списка --}}
{{-- Если метод существует, то отображаем дополнительные фильтры в виде выпадающего меню --}}
{{-- При клике на кнопку "Застосувати" (Применить) добавляем значения фильтров в форму и запускаем поиск --}}
{{-- Используется для расширения функционала фильтрации в списках CMS --}}

@if (method_exists($list->getDefinition(), 'getAdditionalFilterFields') && count($list->getDefinition()->getAdditionalFilterFields()))
<div class="widget-toolbar" role="menu">
    <div class="btn-group">
        <div class="dropdown">
            <button class="btn btn-default dropdown-toggle btn-xs" type="button" id="dropdownFilter" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <i class="fa fa-filter"></i> {{ __cms('Додаткові фільтри') }} <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-left-fix" aria-labelledby="dropdownFilter" style="padding: 10px; min-width: 250px;">
                @foreach ($list->getDefinition()->getAdditionalFilterFields() as $field)
                    <div class="form-group">
                        <label style="color: #333; padding: 0">{{ $field->getName() }}</label>
                        {!! $field->getFilterInput($list) !!}
                    </div>
                @endforeach
                <button type="button" class="btn btn-primary btn-sm btn-apply-filters">{{ __cms('Застосувати') }}</button>
            </ul>
        </div>
    </div>
</div>
<style>
    .dropdown-menu-left-fix {
        right: 0;
        left: auto;
    }
</style>
<script>
    $(document).ready(function () {
        $('.dropdown-menu :input').on('keydown', function (e) {
            e.stopPropagation(); // щоб не закривалось меню при вводі
        });

        $('.dropdown-menu').on('click', function (e) {
            e.stopPropagation(); // щоб не закривалось меню при кліку всередині
        });

        // При кліку на кнопку "Застосувати"
        $('.btn-apply-filters').on('click', function () {
            var $form = $('form[target=submiter]');

            // 🔁 Видалити старі додані фільтри (якщо є)
            $form.find('.__extra-filter').remove();

            // 🔁 Клонувати всі input/select/textarea з dropdown і додати до форми
            $('.dropdown-menu :input[name]').each(function () {
                const $clone = $(this).clone();
                $clone.addClass('__extra-filter');
                $form.append($clone);
            });

            // 🔁 Тепер запускаємо TableBuilder.search()
            TableBuilder.search();
        });
    });
</script>
@endif
