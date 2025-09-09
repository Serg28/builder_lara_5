<div class="json-ext-pair" data-pair-{{ $id }}
    style="position:relative; display:flex; gap:6px; align-items:center; background:#f5f5f5; padding:12px 10px 10px; border-radius:4px;">
    <label class="input" style="display:flex; align-items:center; column-gap:5px; margin:0;">
        <div>{{ __cms('Название') }}:</div>
        <div>
            <input type="text" data-name="key" placeholder="{{ __cms('Название') }}"
                class="form-control input-sm unselectable" value="{{ $k }}">
        </div>
    </label>
    <label class="input" style="display:flex; align-items:center; column-gap:5px; margin:0;">
        <div>{{ __cms('Значение') }}:</div>
        <div>
            <input type="text" data-name="value" placeholder="{{ __cms('Значение') }}"
                class="form-control input-sm unselectable" value="{{ $v }}">
        </div>
    </label>
    <div class="json-ext-pair-actions" style="display:inline-flex; gap:6px; align-items:center;">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-default" title="{{ __cms('Вверх') }}"
                data-up-pair-{{ $id }}>
                <i class="fa fa-chevron-up"></i>
            </button>
            <button type="button" class="btn btn-default" title="{{ __cms('Вниз') }}"
                data-down-pair-{{ $id }}>
                <i class="fa fa-chevron-down"></i>
            </button>
        </div>
        <button type="button" class="btn btn-default btn-xs" title="{{ __cms('Удалить') }}"
            data-remove-pair-{{ $id }}>
            <i class="fa fa-trash"></i>
        </button>
    </div>
</div>
