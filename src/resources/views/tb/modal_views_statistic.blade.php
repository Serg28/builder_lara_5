
<!-- Modal -->
<div class="modal fade tb-modal" id="modal_form_edit"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog " style="width: 1100px" >
        <div class="form-preloader smoke_lol"><i class="fa fa-gear fa-4x fa-spin"></i></div>

        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title" id="modal_form_edit_label">{{__cms('Статистика просмотров')}}</h4>
            </div>
            <div class="content_statistic">

            <form name="statistic" class="form-horizontal" style="margin-bottom: 5px">
			  <fieldset>
		        <div class="input-prepend input-group">
		          <span class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                  </span>
                  <input type="text" name="range" class="form-control" id="range" style="width: 200px;" />
                  <input type="hidden" name="id" value="{{$id}}">
                  <input type="hidden" name="model" value="{{$model}}">
		        </div>
			  </fieldset>
			</form>
			<div id="placeholder">
				<figure id="chart" ></figure>
			</div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script src="/packages/linecore/builder/js/xchart/js/script.js"></script>
