@if(config("cms.translations.cms.languages"))
    <ul class="header-dropdown-list hidden-xs">
        <li>
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <img alt="" src="/packages/linecore/cms/img/flags/{{$thisLang == "uk" ? "ukr" : $thisLang}}.png">
                <span> {{config("cms.translations.cms.languages")[$thisLang] ?? ""}} </span> <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-right">

                @foreach(config("cms.translations.cms.languages") as $alias => $title)

                    <li {{$thisLang == $alias ? "class='active'" : ""}}>
                        <a href="{{route("change_lang"). "?lang=" .$alias}}"><img src="/packages/linecore/cms/img/flags/{{$alias == "uk" ? "ukr" : $alias }}.png"> {{$title}}</a>
                    </li>
                @endforeach

            </ul>
        </li>
    </ul>
@endif
