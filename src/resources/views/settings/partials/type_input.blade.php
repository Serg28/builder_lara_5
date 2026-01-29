<div class='type_0 types' {!! !isset($info->type) || $info->type==0 ? 'style="display: block"' : "" !!} >
    @if (is_array(config('cms.settings.langs')))
        <ul class="nav nav-tabs tabs-pull-right">
            <label class="label pull-left" style="line-height: 32px;">{{__cms('Значение')}}</label>
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <li class="{{$loop->index == 0 ? 'active' : ''}}">
                    <a href="#etitle{{$prefix}}" data-toggle="tab">{{$name}}</a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content padding-5">
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <?php $value = 'value' . $prefix;?>
                <div id="etitle{{$prefix}}" class="tab-pane {{$loop->index == 0 ? 'active' : ''}}">
                    <div style="position: relative;">
                        <label class="input">
                            <input class="dblclick-edit-input form-control input-sm unselectable" value="{{ $info->$value ?? '' }}" name="value0{{$prefix}}" placeholder="Текст {{$name}}" type="text">
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <label class="label" >{{__cms('Значение')}}</label>
        <label class="input">
            <input type="text" value="{{ $info->value ?? '' }}" name="value0" class="dblclick-edit-input form-control input-sm unselectable">
        </label>
    @endif
</div>
