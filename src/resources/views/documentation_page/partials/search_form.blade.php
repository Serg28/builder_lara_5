<form action="{{ route('admin.docs.index') }}" method="get" class="navbar-form navbar-right search-form" role="form">
    <div class="form-group">
        <input type="text" name="q" value="{{ e($query) }}"  class="form-control" placeholder="{{__cms('Введите текст для поиска')}}">
    </div>
    <button type="submit" class="btn btn-success">{{__cms('Найти')}}</button>
</form>

