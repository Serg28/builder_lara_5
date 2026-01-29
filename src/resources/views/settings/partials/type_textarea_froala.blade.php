<div class='type_6 types' {!!  isset($info->type) && $info->type==6 ? 'style="display: block"' : "" !!}>
    @if (is_array(config('cms.settings.langs')))
        <ul class="nav nav-tabs tabs-pull-right">
            <label class="label pull-left" style="line-height: 32px;">{{__cms('Значение')}}</label>
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <li class="{{$loop->index == 0 ? 'active' : ''}}">
                    <a href="#etextarea_froala{{$prefix}}" data-toggle="tab">{{$name}}</a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content padding-5">
            @foreach(config('cms.settings.langs') as $prefix => $name)
                <?php $value = 'value' . $prefix;?>
                <div id="etextarea_froala{{$prefix}}" class="tab-pane {{$loop->index == 0 ? 'active' : ''}}">

                  <textarea name="value6{{$prefix}}" style="height: 250px"  placeholder="Текст {{$name}}" class="custom-scroll text_block">{{ $info->$value ?? '' }}</textarea>

                </div>
            @endforeach
        </div>
    @else
        <label class="label" >{{__cms('Значение')}}</label>
        <textarea name="value6" style="height: 250px" class="custom-scroll text_block">{{ $info->value ?? '' }}</textarea>
    @endif
</div>
