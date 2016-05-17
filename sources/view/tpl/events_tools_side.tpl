<div class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="#" onclick="exportDate(); return false;"><i class="fa fa-arrow-circle-o-down"></i>&nbsp;{{$export}}</a></li>
		<li><a href="#" onclick="openClose('event-upload-form'); return false;"><i class="fa fa-arrow-circle-o-up"></i>&nbsp;{{$import}}</a></li>
	</ul>
	<div id="event-upload-form" style="display: none;">
		<form action="events" enctype="multipart/form-data" method="post" name="event-upload-form" id="event-upload-form">
			<div class="form-group">
				<input id="event-upload-choose" type="file" name="userfile" />
			</div>
			<button id="dbtn-submit" class="btn btn-primary btn-xs" type="submit" name="submit" >{{$submit}}</button>
		</form>
	</div>
</div>
