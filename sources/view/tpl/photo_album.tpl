<div class="{{if !$no_fullscreen_btn}}generic-content-wrapper{{/if}}">
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $order}}
			<a class="btn btn-default btn-xs" href="{{$order.1}}" title="{{$order.0}}"><i class="fa fa-sort"></i></a>
			{{/if}}
			<div class="btn-group btn-group">
				{{if $album_edit.1}}
				<i class="fa fa-pencil btn btn-default btn-xs" title="{{$album_edit.0}}" onclick="openClose('photo-album-edit-wrapper'); closeMenu('photo-upload-form');"></i>
				{{/if}}
				{{if $can_post}}
				<button class="btn btn-xs btn-success btn-xs" title="{{$usage}}" onclick="openClose('photo-upload-form'); closeMenu('photo-album-edit-wrapper');"><i class="fa fa-arrow-circle-o-up"></i>&nbsp;{{$upload.0}}</button>
				{{/if}}
			</div>
			{{if !$no_fullscreen_btn}}
			<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);"><i class="fa fa-compress"></i></button>
			{{/if}}
		</div>
		<h2>{{$album}}</h2>
		<div class="clear"></div>
	</div>
	{{$upload_form}}
	{{$album_edit.1}}
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
