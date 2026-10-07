{{-- Добавлено. Если у документа (в структуре сайта) есть меню (шаблон с функционалом ссылки на каталог, новость или стуктуру), то формиурем ссылку на него.
Иначе - стандартная ссылка --}}
@if ($active)
	<li><a href="{{ $item->getTreeUrl() }}?show=1" target="_blank"><i class="fa fa-eye"></i> {{__cms('Предпросмотр')}} </a></li>
@endif

{{-- Original --}}
{{--
@if ($active)
	<li><a href="{{ ($item->getUrl() }}?show=1" target="_blank"><i class="fa fa-eye"></i>3 {{__cms('Предпросмотр')}} </a></li>
@endif
--}}
{{-- / --}}
