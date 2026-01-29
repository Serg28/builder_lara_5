<div class='type_1 types' {!!  isset($info->type) && $info->type==1 ? 'style="display: block"' : "" !!}>
    @if ( is_array(config('cms.settings.langs')))
        <ul class="nav nav-tabs tabs-pull-right">
            <label class="label pull-left" style="line-height: 32px;">{{__cms('Значение')}}</label>
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <li class="{{$loop->index == 0 ? 'active' : ''}}">
                    <a href="#etextarea{{$prefix}}" data-toggle="tab">{{$name}}</a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content padding-5">
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <?php $value = 'value' . $prefix;?>
                <div id="etextarea{{$prefix}}" class="tab-pane {{$loop->index == 0 ? 'active' : ''}}">
                    <div style="position: relative;">
                        <label class="textarea">
                            <textarea name="value1{{$prefix}}" style="height: 250px"  placeholder="Текст {{$name}}" class="custom-scroll">{{ $info->$value ?? '' }}</textarea>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <label class="label" >{{__cms('Значение')}}</label>
        <label class="textarea">
            <textarea name="value1" style="height: 250px" class="custom-scroll">{{ $info->value ?? '' }}</textarea>
        </label>
    @endif
</div>
