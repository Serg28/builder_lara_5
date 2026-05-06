<div class="json-ext-group"
    style="position:relative; display:flex; gap:10px; padding:10px; border: 1px solid #dddddd;; width:100%; box-sizing:border-box; align-items:flex-start; justify-content: space-between;"
    data-group-{{ $id }}>
    <div class="json-ext-pairs" data-pairs-container-{{ $id }}
        style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-start;">
        @foreach ($pairs ?? [] as $k => $v)
            @include('admin::form.fields.partials.json_ext_pair', ['id' => $id, 'k' => $k, 'v' => $v])
        @endforeach
    </div>
    <div class="json-ext-controls" style="display:inline-flex; gap:6px; align-items:center;">
        <button type="button" class="btn btn-default btn-xs" title="{{ __cms('Добавить пару') }}"
            data-add-pair-{{ $id }}>
            <i class="fa fa-plus"></i>
        </button>
        <div class="btn-group" role="group" style="width: max-content;">
            <button type="button" class="btn btn-default" title="{{ __cms('Вверх') }}"
                data-up-group-{{ $id }}>
                <i class="fa fa-chevron-up"></i>
            </button>
            <button type="button" class="btn btn-default" title="{{ __cms('Вниз') }}"
                data-down-group-{{ $id }}>
                <i class="fa fa-chevron-down"></i>
            </button>
        </div>
        <button type="button" class="btn btn-default btn-xs" title="{{ __cms('Удалить группу') }}"
            data-remove-group-{{ $id }}>
            <i class="fa fa-trash"></i>
        </button>
    </div>
</div>
