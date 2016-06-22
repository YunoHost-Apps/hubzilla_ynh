<div class="group-delete-wrapper button" id="group-delete-wrapper-{{$id}}" >
	<a href="group/drop/{{$id}}?t={{$form_security_token}}" 
		onclick="return confirmDelete();" 
		id="group-delete-icon-{{$id}}" 
		class="group-delete-icon btn btn-default" title="{{$delete}}" ><i class="fa fa-trash-o drop-icons"></i></a>
</div>
<div class="group-delete-end"></div>
