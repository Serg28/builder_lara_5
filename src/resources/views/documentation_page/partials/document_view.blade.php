@if(!empty($document))
    <h1 class="page-header">{{ $document['title'] }}</h1>

    {{--<div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ $document['title'] }}</h3>
        </div>
        <div class="panel-body">
            {!! $document['content'] !!}
        </div>
    </div> --}}
    <div class="content">
        {!! $document['content'] ?: __cms('Текст документации отстутствует') !!}
    </div>
@else
    <div class="content">
        <div class="alert alert-warning">{{__cms('Документ не выбран')}}</div>
    </div>
@endif

