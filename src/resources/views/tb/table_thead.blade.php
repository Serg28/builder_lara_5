<tr>
    @if ($controller->definitionClass->isSortable())
        <th style="width: 1%; padding: 0;">
            <i style="margin-left: -10px;" class="fa fa-reorder"></i>
        </th>
    @endif
    
    @if ($controller->definitionClass->isMultiActions())
        <th style="width: 1%; padding: 0;">
            <label class="checkbox multi-checkbox multi-main-checkbox" onclick="TableBuilder.doSelectAllMultiCheckboxes(this);">
                <input type="checkbox" /><i></i>
            </label>
        </th>
    @endif

    @foreach ($fieldsList as $field)
        @if ($field->getAttribute('is_sorting'))
                <th
                        style="position: relative"
                        class="sorting
                {!! $field->isOrder($controller) !!}
                "
                    onclick="TableBuilder.doChangeSortingDirection('{{$field->getFieldName()}}', this);"
                        {!! $field->getWidth() !!}
                >
                    @if ($field->isOrder($controller))
                        <button onclick="TableBuilder.doClearOrder();" class="close" style="position: absolute; top: 12px; left: 13px;">×</button>
                    @endif

                    {{ __cms($field->getAttribute('caption')) }}
                </th>
        @else
            <th {!! $field->getWidth() !!}>{{ __cms($field->getAttribute('caption')) }}</th>
        @endif
    @endforeach

    @if ($controller->definitionClass->isShowInsert())
        <th class="e-insert_button-cell" style="min-width: 69px;">
            {!! $controller->actions->fetch('insert') !!}
        </th>
    @else
        <th></th>
    @endif
</tr>
@if ($controller->definitionClass->isFilterPresent())
    <tr class="filters-row">
        @if ($controller->definitionClass->isSortable())
            <th></th>
        @endif

        @if ($controller->definitionClass->isMultiActions())
            <th></th>
        @endif

        @foreach ($fieldsList as $field)
                <td>{!! $field->getFilterInput() !!}</td>
        @endforeach

        <td style="width:1%">
            <button class="btn btn-default btn-sm tb-search-btn" style="min-width: 66px;"
                    type="button"
                    onclick="TableBuilder.search();">
                    {{ __cms('Поиск')}}
            </button>
            <button onclick="resetOrderFields(); return false;" class="btn btn-default btn-sm tb-search-btn" style="margin-top: 10px; min-width: 66px;"
                        type="button">
                {{ __cms('Сбросить')}}
            </button>
        </td>
    </tr>
@endif
