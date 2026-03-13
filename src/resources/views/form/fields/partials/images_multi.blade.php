@php $normalizedValue = '/' . ltrim($value, '/'); @endphp
<li>
    <img class="image-attr-editable"
         data-tbnum="{{$key ?? ''}}"
         @if (strpos($normalizedValue, '.svg'))
            width='120'
            height='120'
            src="{{$normalizedValue}}"
         @else
            src="{{glide($normalizedValue, ['w'=>'130','h'=>'130'])}}"
         @endif
         data_src_original="{{$normalizedValue}}"
         src_original="{{$normalizedValue}}"
         data-width='120'
         data-height='120'
    />

    <div class="tb-btn-delete-wrap">
        <button class="btn2 btn-default btn-sm tb-btn-image-delete"
                type="button"
                onclick="TableBuilder.deleteImage(this);">
            <i class="fa fa-times"></i>
        </button>
    </div>
</li>
