<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$id}}">
	<i class="fa fa-thumbs-o-up item-tool btn btn-default" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false"></i>
	<i class="fa fa-thumbs-o-down item-tool btn btn-default" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false"></i>
	<div id="like-rotator-{{$id}}" class="like-rotator"></div>
</div>
