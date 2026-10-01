<ul class="nav nav-sidebar">
    @foreach($documents as $doc)
        <li class="{{ (isset($current) && $current === $doc['link']) ? 'active' : '' }}">
            <a href="{{ $doc['link'] }}">
                {{ $doc['title'] }}
            </a>
            {{-- @if(!empty($doc['languages']))
                <small>({{ implode(', ', $doc['languages']) }})</small>
            @endif --}}
        </li>
    @endforeach
</ul>