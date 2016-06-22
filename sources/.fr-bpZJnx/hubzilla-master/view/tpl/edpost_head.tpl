<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $delete}}
		<div class="pull-right">
			<a  href="item/drop/{{$id}}" id="delete-btn" class="btn btn-xs btn-danger" onclick="return confirmDelete();"><i class="fa fa-trash-o"></i>&nbsp;{{$delete}}</a>
		</div>
		{{/if}}
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
	<div id="webpage-editor" class="section-content-tools-wrapper">
		{{$editor}}
	</div>
</div>
