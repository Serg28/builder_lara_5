<div class="table_center no-padding">

    <div class="dt-toolbar">
        <div class="col-xs-12 col-sm-6">
            <div id="dt_basic_filter" class="dataTables_filter">
                <form action="" method="get" id="search_form">
                    <label>
                  <span class="input-group-addon">
                  <i class="glyphicon glyphicon-search"></i>
                  </span>
                        <input class="form-control" name="search_q" type="search" value="{{$search_q ?? ""}}"
                               aria-controls="dt_basic">
                    </label>
                </form>
            </div>
        </div>
        <div class="col-sm-6 col-xs-12 hidden-xs">
            <div id="dt_basic_length" class="dataTables_length">
                <label>

                    <select class="form-control" name="dt_basic_length" aria-controls="dt_basic" onchange="changeCountShow(this)">

                        @foreach([20, 40, 100] as $val)
                            <option value="{{$val}}"
                                    @if($val == $count_show)
                                        selected
                                    @endif
                            >{{$val}}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div id="results_container">
        @include("admin::translation_cms.part.result_search")
    </div>

</div>

<script>
    function changeCountShow(obj) {
        let search = $('input[name="search_q"]').val();
        let url = window.location.pathname + '?count_show=' + obj.value;
        if(search) {
            url += '&search_q=' + search;
        }
        window.location.href = url;
    }
</script>