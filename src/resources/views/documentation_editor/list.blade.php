{{-- TODO: автогенерация таблицы --}}

<section id="widget-grid" class="">
    <div class="row" style="padding-right: 13px; padding-left: 13px;">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12" style="padding-right: 0px; padding-left: 0px;">
            <div id="table-preloader" class="smoke_lol"><i class="fa fa-gear fa-4x fa-spin"></i></div>
            <div class="jarviswidget jarviswidget-color-blue" id="wid-id-1" data-widget-editbutton="false" data-widget-colorbutton="false" data-widget-deletebutton="false" data-widget-sortable="false">

                <header>
                    <span class="widget-icon"> <i class="fa fa-table"></i> </span>
                    <h2>{{ $definition->getTitle() }}</h2>

                    <a href="{{ url()->current() }}?create=true" class="btn btn-success btn-sm" style="float: right;margin-top:4px;margin-right:7px;">
                        <i class="fa fa-plus"></i> {{__cms('Добавить')}}
                    </a>
                </header>
                <div>
                    <div class="jarviswidget-editbox"></div>
                    <div class="widget-body no-padding">
                        <form action="/admin/actions/warehouses" method="post" class="form-horizontal tb-table" target="submiter">

                            <table id="datatable_fixed_column" class="table  table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th>{{__cms('Страница')}}</th>
                                    <th>Slug</th>
                                    <th width="80px">{{__cms('Действия')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($docs as $doc)
                                    <tr>
                                        <td class="text-left">{{$doc['title']}} </td>
                                        <td class="text-left">{{ $doc['name'] }} </td>
                                        <td>
                                            <div style="display: inline-block">
                                                <div class="btn-group  pull-right">
                                                    <a class="btn dropdown-toggle btn-default" data-toggle="dropdown">
                                                        <i class="fa fa-cog"></i> <i class="fa fa-caret-down"></i>
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a href="{{ url()->current() }}?edit={{ $doc['path'] }}"><i class="fa fa-pencil"></i> {{__cms('Редактировать')}}</a>
                                                        </li>
                                                        <li>
                                                            <a href="/admin/docs/{{ $doc['path'] }}" target="_blank"><i class="fa fa-eye"></i> {{__cms('Открыть')}}</a>
                                                        </li>
                                                        {{--
                                                        <li>
                                                            <a style="color: red" onclick="TableBuilder.doDelete(1, $(this));"><i class="fa red fa-times"></i> Видалити</a>
                                                        </li>
                                                        --}}
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">{{__cms('Файлы документации не найдены')}}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
{{--
                            <div class="row tb-pagination">
                                <div class="col-sm-4 col-xs-12 hidden-xs">
                                    <div id="dt_basic_info" class="dataTables_info" role="status" aria-live="polite">
                                        Показано

                                        <span class="txt-color-darken listing_from">1</span>
                                        -
                                        <span class="txt-color-darken listing_to">20 </span>
                                        З
                                        <span class="text-primary listing_total">2</span>
                                        Записів


                                    </div>
                                </div>

                                <div class="col-sm-8 text-right">
                                    <div class="dataTables_paginate paging_bootstrap_full">


                                        <div style="clear:both; padding-top:10px;"></div>
                                        <span>Показувати по:</span>
                                        <div class="btn-group">
                                            <button type="button" onclick="TableBuilder.setPerPageAmount('20');" class="btn btn-default btn-xs active">
                                                20
                                            </button>
                                            <button type="button" onclick="TableBuilder.setPerPageAmount('100');" class="btn btn-default btn-xs ">
                                                100
                                            </button>
                                            <button type="button" onclick="TableBuilder.setPerPageAmount('1000');" class="btn btn-default btn-xs ">
                                                1000
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
--}}
                        </form>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>