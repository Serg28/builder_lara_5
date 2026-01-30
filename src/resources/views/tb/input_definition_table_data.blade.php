<div class="loader_definition"><i class="fa fa-gear fa-4x fa-spin"></i></div>
<table class="table table-hover table-bordered">
    <thead>
    <tr>
        <td class="col_sort"></td>
        @foreach($arrayDefinitionFields as $name => $field)
            <th>{{$field['caption']}}</th>
        @endforeach
        <th style="width: 10%"></th>
    </tr>
    </thead>
    <tbody>
        @forelse($result as $data)
        <tr data-id="{{$data['id']}}">
            <td class="handle col_sort"><i class="fa fa-sort"></i></td>
            @foreach($arrayDefinitionFields as $name => $field)
                <?php
                $nameClass = "Linecore\\Cms\\Fields\\" . ucfirst($field['type']) . "Field";
                $resultObjectFild = new $nameClass($name, $field, [], [], []);
                ?>
            <td>{!! $resultObjectFild->getListValueDefinitionPopup($data) !!}</td>
            @endforeach
            <td>
                <div class="btn-group hidden-phone pull-right">
                    <a class="btn dropdown-toggle btn-default"  data-toggle="dropdown"><i class="fa fa-cog"></i> <i class="fa fa-caret-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a onclick="ForeignDefinition.edit({{$data['id']}}, {{$idUpdate}}, '{{$attributes}}')"><i class="fa fa-pencil"></i> {{__cms('Редактировать')}}</a></li>
                        <li><a onclick="ForeignDefinition.delete({{$data['id']}}, {{$idUpdate}}, '{{$attributes}}')"><i class="fa red fa-times"></i> {{__cms('Удалить')}}</a></li>
                    </ul>
                </div>

            </td>
        </tr>
        @empty
            <tr><td colspan="{{count ($arrayDefinitionFields) +1 }}"> {{__cms('Пока пусто')}} </td></tr>
        @endforelse
    </tbody>
</table>
