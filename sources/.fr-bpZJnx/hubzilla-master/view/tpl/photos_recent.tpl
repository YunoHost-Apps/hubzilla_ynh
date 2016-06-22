<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);"><i class="fa fa-compress"></i></button>
			{{if $can_post}}
			<button class="btn btn-xs btn-success" title="{{$usage}}" onclick="openClose('photo-upload-form');"><i class="fa fa-arrow-circle-o-up"></i>&nbsp;{{$upload.0}}</button>
			{{/if}}
		</div>
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
	{{$upload_form}}
	<div class="section-content-wrapper-np">
		<div id="photo-album-contents-{{$album_id}}">
			{{foreach $photos as $photo}}
				{{include file="photo_top.tpl"}}
			{{/foreach}}
			<div id="page-end"></div>
		</div>
	</div>
</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos('photo-album-contents-{{$album_id}}'); });</script>
<div id="page-spinner"></div>
