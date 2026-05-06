@if(empty($results))
    <div class="alert alert-info">
        {{str_replace('[_]', e($query),__cms('Ничего не найдено по запросу «[_]»'))}}.
    </div>
@else
    <h1 class="page-header">{{str_replace('[_]', e($query),__cms('Документы по запросу «[_]»'))}}</h1>
    <ul class="list-group">
        @foreach($results as $result)
            <li class="list-group-item">
                <h5>
                    <a href="{{ $result['link'] }}" class="document-link">
                        {{ $result['name'] }}
                    </a>
                </h5>
                <div class="text-muted small">
                    {!! $result['snippet'] !!}
                </div>
            </li>
        @endforeach
    </ul>
@endif

