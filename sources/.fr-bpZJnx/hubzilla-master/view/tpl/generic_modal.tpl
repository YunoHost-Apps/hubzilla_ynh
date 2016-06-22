<div class="modal" id="generic-modal-{{$id}}" tabindex="-1" role="dialog" aria-labelledby="generic-modal-{{$id}}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="generic-modal-title-{{$id}}">{{$title}}</h4>
      </div>
      <div class="modal-body" id="generic-modal-body-{{$id}}"></div>
      <div class="modal-footer">
        <button id="generic-modal-cancel-{{$id}}" type="button" class="btn btn-default" data-dismiss="modal">{{$cancel}}</button>
        <button id="generic-modal-ok-{{$id}}" type="button" class="btn btn-primary">{{$ok}}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->