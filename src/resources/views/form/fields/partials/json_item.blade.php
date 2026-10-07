<div class="col-md-10_" style="display: flex; gap: 10px; padding: 5px; background: #f0f0f0; width: 100%; box-sizing: border-box;" data-x-group-{{$id}}>
    @if ($isAssociative)
        <label class="input" style="display: flex; align-items: center; column-gap: 5px;">
            <div>{{__cms('Название')}}: </div>
            <div>
                <input type="text" name="key" data-name="key" placeholder="{{__cms('Название')}}" class="form-control input-sm unselectable" value="{{$key}}">
            </div>
        </label>
        <label class="input" style="display: flex; align-items: center; column-gap: 5px;">
            <div>{{__cms('Значение')}}: </div>
            <div>
                <input type="text" name="value" data-name="value" placeholder="{{__cms('Значение')}}" class="form-control input-sm unselectable" value="{{$value}}">
            </div>
        </label>
    @else
        <label class="input" style="display: flex; align-items: center; column-gap: 5px;">
            <div>{{__cms('Элемент')}}: </div>
            <div>
                <input type="text" name="item" data-name="item" placeholder="{{__cms('Элемент')}}" class="form-control input-sm unselectable" value="{{$value}}">
            </div>
        </label>
    @endif
    <button type="button" class="btn btn-danger btn-sm" data-remove-btn-{{$id}}>{{__cms('Удалить')}}</button>
</div>
