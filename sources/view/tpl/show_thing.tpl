<h2>{{$header}}</h2>
{{if $thing}}
<div class="thing-show">
{{if $thing.obj_imgurl}}<a href="{{$thing.obj_url}}" ><img src="{{$thing.obj_imgurl}}" width="175" height="175" alt="{{$thing.obj_term}}" /></a>{{/if}}
<a href="{{$thing.obj_url}}" >{{$thing.obj_term}}</a>
</div>
{{if $canedit}}
<div class="thing-edit-links">
<a href="thing/edit/{{$thing.obj_obj}}" title="{{$edit}}" class="btn btn-default" ><i class="fa fa-pencil thing-edit-icon"></i></a>
<a href="thing/drop/{{$thing.obj_obj}}" onclick="return confirmDelete();" title="{{$delete}}" class="btn btn-default" ><i class="fa fa-trash-o drop-icons"></i></a>
</div>
<div class="thing-edit-links-end"></div>
{{/if}}

{{/if}}

