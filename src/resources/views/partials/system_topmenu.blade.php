<ul class="header-dropdown-list hidden-xs" style="margin-right: 10px;">
    <li>
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <span> {{__cms('Додатково')}} </span> <i class="fa fa-angle-down"></i> </a>
        <ul class="dropdown-menu pull-right">
            <li>
                <a href="{{route('admin.clear-cache')}}" target="_blank" title="{{__cms('Скинути кеш')}}" style="display: flex; gap: 10px; align-content: center; flex-wrap: wrap;">
                    <span class="glyphicon glyphicon-refresh"></span>
                    {{__cms('Скинути кеш')}}
                </a>
            </li>
            <li>
                <a href="/" target="_blank" title="{{__cms('Відкрити сайт')}}" style="display: flex; gap: 10px; align-content: center; flex-wrap: wrap;">
                    <span class="glyphicon glyphicon-new-window"></span>
                    {{__cms('Відкрити сайт')}}
                </a>
            </li>
        </ul>
    </li>
</ul>

