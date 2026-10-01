<span class="dblclick-edit selectable"
      data-type="text"
      data-pk="{{ $row['id'] }}"
      data-url="/admin/handle/{{$def['db']['table']}}/fast-edit"
      data-name="{{ $ident }}"
      data-title="{{__cms('Введите')}}: {{ $field->getAttribute('caption')}}"
>{!! strip_tags($field->getListValue($row), "<a><span><img>") !!}</span>
