<form id="profile-jot-form" action="{{$action}}" method="post" class="acl-form" data-form_id="profile-jot-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
	{{$mimeselect}}
	{{$layoutselect}}
	{{if $id_select}}
	<div class="channel-id-select-div">
		<span class="channel-id-select-desc">{{$id_seltext}}</span> {{$id_select}}
	</div>
	{{/if}}
	<div id="profile-jot-wrapper">
		{{if $parent}}
		<input type="hidden" name="parent" value="{{$parent}}" />
		{{/if}}
		<input type="hidden" name="obj_type" value="{{$ptyp}}" />
		<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
		<input type="hidden" name="return" value="{{$return_path}}" />
		<input type="hidden" name="location" id="jot-location" value="{{$defloc}}" />
		<input type="hidden" name="expire" id="jot-expire" value="{{$defexpire}}" />
		<input type="hidden" name="created" id="jot-created" value="{{$defpublish}}" />
		<input type="hidden" name="media_str" id="jot-media" value="" />
		<input type="hidden" name="source" id="jot-source" value="{{$source}}" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="post_id" value="{{$post_id}}" />
		<input type="hidden" name="webpage" value="{{$webpage}}" />
		<input type="hidden" name="preview" id="jot-preview" value="0" />
		<input type="hidden" id="jot-consensus" name="consensus" value="{{if $consensus}}{{$consensus}}{{else}}0{{/if}}" />
		<input type="hidden" id="jot-nocomment" name="nocomment" value="{{if $nocomment}}{{$nocomment}}{{else}}0{{/if}}" />

		{{if $webpage}}
		<div id="jot-pagetitle-wrap" class="jothidden">
			<input name="pagetitle" id="jot-pagetitle" type="text" placeholder="{{$placeholdpagetitle}}" value="{{$pagetitle}}">
		</div>
		{{/if}}
		<div id="jot-title-wrap" class="jothidden">
			<input name="title" id="jot-title" type="text" placeholder="{{$placeholdertitle}}" tabindex=1 value="{{$title}}">
		</div>
		{{if $catsenabled}}
		<div id="jot-category-wrap" class="jothidden">
			<input name="category" id="jot-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" data-role="cat-tagsinput">
		</div>
		{{/if}}
		<div id="jot-text-wrap">
			<textarea class="profile-jot-text" id="profile-jot-text" name="body" tabindex=2 placeholder="{{$share}}" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >{{$content}}</textarea>
		</div>
		{{if $attachment}}
		<div id="jot-attachment-wrap">
			<input class="jot-attachment" name="attachment" id="jot-attachment" type="text" value="{{$attachment}}" readonly="readonly" onclick="this.select();">
		</div>
		{{/if}}
		<div id="profile-jot-submit-wrapper" class="jothidden">
			<div id="profile-jot-submit-left" class="btn-toolbar pull-left">
				{{if $bbcode}}
				<div class="btn-group">
					<button id="main-editor-bold" class="btn btn-default btn-sm" title="{{$bold}}" onclick="inserteditortag('b', 'profile-jot-text'); return false;">
						<i class="fa fa-bold jot-icons"></i>
					</button>
					<button id="main-editor-italic" class="btn btn-default btn-sm" title="{{$italic}}" onclick="inserteditortag('i', 'profile-jot-text'); return false;">
						<i class="fa fa-italic jot-icons"></i>
					</button>
					<button id="main-editor-underline" class="btn btn-default btn-sm" title="{{$underline}}" onclick="inserteditortag('u', 'profile-jot-text'); return false;">
						<i class="fa fa-underline jot-icons"></i>
					</button>
					<button id="main-editor-quote" class="btn btn-default btn-sm" title="{{$quote}}" onclick="inserteditortag('quote', 'profile-jot-text'); return false;">
						<i class="fa fa-quote-left jot-icons"></i>
					</button>
					<button id="main-editor-code" class="btn btn-default btn-sm" title="{{$code}}" onclick="inserteditortag('code', 'profile-jot-text'); return false;">
						<i class="fa fa-terminal jot-icons"></i>
					</button>
				</div>
				{{/if}}
				{{if $visitor}}
				<div class="btn-group hidden-xs hidden-sm">
					{{if $writefiles}}
					<button id="wall-file-upload" class="btn btn-default btn-sm" title="{{$attach}}" >
						<i id="wall-file-upload-icon" class="fa fa-paperclip jot-icons"></i>
					</button>
					{{/if}}
					{{if $weblink}}
					<button id="profile-link-wrapper" class="btn btn-default btn-sm" title="{{$weblink}}" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);"  onclick="jotGetLink(); return false;">
						<i id="profile-link" class="fa fa-link jot-icons"></i>
					</button>
					{{/if}}
					{{if $embedPhotos}}
					<button id="embed-photo-wrapper" class="btn btn-default btn-sm" title="{{$embedPhotos}}" onclick="initializeEmbedPhotoDialog();return false;">
						<i id="embed-photo" class="fa fa-file-image-o jot-icons"></i>
					</button>
					{{/if}}
				</div>
				<div class="btn-group hidden-xs hidden-sm">
					{{if $setloc}}
					<button id="profile-location-wrapper" class="btn btn-default btn-sm" title="{{$setloc}}" onclick="jotGetLocation();return false;">
						<i id="profile-location" class="fa fa-globe jot-icons"></i>
					</button>
					{{/if}}
					{{if $clearloc}}
					<button id="profile-nolocation-wrapper" class="btn btn-default btn-sm" title="{{$clearloc}}" onclick="jotClearLocation();return false;" disabled="disabled">
						<i id="profile-nolocation" class="fa fa-circle-o jot-icons"></i>
					</button>
					{{/if}}
				{{else}}
				<div class="btn-group hidden-xs hidden-sm">
				{{/if}}
				{{if $feature_expire}}
					<button id="profile-expire-wrapper" class="btn btn-default btn-sm" title="{{$expires}}" onclick="jotGetExpiry();return false;">
						<i id="profile-expires" class="fa fa-eraser jot-icons"></i>
					</button>
				{{/if}}
				{{if $feature_future}}
					<button id="profile-future-wrapper" class="btn btn-default btn-sm" title="{{$future_txt}}" onclick="jotGetPubDate();return false;">
						<i id="profile-future" class="fa fa-clock-o jot-icons"></i>
					</button>
				{{/if}}
				{{if $feature_encrypt}}
					<button id="profile-encrypt-wrapper" class="btn btn-default btn-sm" title="{{$encrypt}}" onclick="red_encrypt('{{$cipher}}','#profile-jot-text',$('#profile-jot-text').val());return false;">
						<i id="profile-encrypt" class="fa fa-key jot-icons"></i>
					</button>
				{{/if}}
				{{if $feature_voting}}
					<button id="profile-voting-wrapper" class="btn btn-default btn-sm" title="{{$voting}}" onclick="toggleVoting();return false;">
						<i id="profile-voting" class="fa fa-square-o jot-icons"></i>
					</button>
				{{/if}}
				{{if $feature_nocomment}}
					<button id="profile-nocomment-wrapper" class="btn btn-default btn-sm" title="{{$nocommenttitle}}" onclick="toggleNoComment();return false;">
						<i id="profile-nocomment" class="fa fa-comments jot-icons"></i>
					</button>
				{{/if}}
				</div>
				{{if $writefiles || $weblink || $setloc || $clearloc || $feature_expire || $feature_encrypt || $feature_voting}}
				<div class="btn-group visible-xs visible-sm">
					<button type="button" id="more-tools" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<i id="more-tools-icon" class="fa fa-caret-down jot-icons"></i>
					</button>
					<ul class="dropdown-menu" role="menu">
						{{if $visitor}}
						{{if $writefiles}}
						<li><a id="wall-file-upload-sub" href="#" ><i class="fa fa-paperclip"></i>&nbsp;{{$attach}}</a></li>
						{{/if}}
						{{if $weblink}}
						<li><a href="#" onclick="jotGetLink(); return false;"><i class="fa fa-link"></i>&nbsp;{{$weblink}}</a></li>
						{{/if}}
						{{if $setloc}}
						<li><a href="#" onclick="jotGetLocation(); return false;"><i class="fa fa-globe"></i>&nbsp;{{$setloc}}</a></li>
						{{/if}}
						{{if $clearloc}}
						<li><a href="#" onclick="jotClearLocation(); return false;"><i class="fa fa-circle-o"></i>&nbsp;{{$clearloc}}</a></li>
						{{/if}}
						{{/if}}
						{{if $feature_expire}}
						<li><a href="#" onclick="jotGetExpiry(); return false;"><i class="fa fa-eraser"></i>&nbsp;{{$expires}}</a></li>
						{{/if}}
						{{if $feature_future}}
						<li><a href="#" onclick="jotGetPubDate();return false;"><i class="fa fa-clock-o"></i>&nbsp;{{$future_txt}}</a></li>
						{{/if}}
						{{if $feature_encrypt}}
						<li><a href="#" onclick="red_encrypt('{{$cipher}}','#profile-jot-text',$('#profile-jot-text').val());return false;"><i class="fa fa-key"></i>&nbsp;{{$encrypt}}</a></li>
						{{/if}}
						{{if $feature_voting}}
						<li><a href="#" onclick="toggleVoting(); return false;"><i id="profile-voting-sub" class="fa fa-square-o"></i>&nbsp;{{$voting}}</a></li>
						{{/if}}
						{{if $feature_nocomment}}
						<li><a href="#" onclick="toggleNoComment(); return false;"><i id="profile-nocomment-sub" class="fa fa-comments"></i>&nbsp;{{$nocommenttitlesub}}</a></li>
						{{/if}}
					</ul>
				</div>
				{{/if}}
			</div>
			<div id="profile-rotator-wrapper">
				<div id="profile-rotator"></div>
			</div>
			<div id="profile-jot-submit-right" class="btn-group pull-right">
				{{if $preview}}
				<button class="btn btn-default btn-sm" onclick="preview_post();return false;" title="{{$preview}}">
					<i class="fa fa-eye jot-icons" ></i>
				</button>
				{{/if}}
				{{if $jotnets}}
				<button id="dbtn-jotnets" class="btn btn-default btn-sm" data-toggle="modal" data-target="#jotnetsModal" type="button" title="{{$jotnets_label}}" style="{{if $lockstate == 'lock'}}display: none;{{/if}}">
					<i class="fa fa-share-alt jot-icons"></i>
				</button>
				{{/if}}
				{{if $showacl}}
				<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" type="button" data-form_id="profile-jot-form">
					<i id="jot-perms-icon" class="fa fa-{{$lockstate}} jot-icons"></i>{{if $bang}}&nbsp;<i class="fa fa-exclamation jot-icons"></i>{{/if}}
				</button>
				{{/if}}
				<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" tabindex="3" name="button-submit">{{$share}}</button>
			</div>
			<div class="clear"></div>
			{{if $jotplugins}}
			<div id="profile-jot-plugin-wrapper">
				{{$jotplugins}}
			</div>
			{{/if}}
			{{if $jotnets}}
			<div class="modal" id="jotnetsModal" tabindex="-1" role="dialog" aria-labelledby="jotnetsModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h4 class="modal-title" id="expiryModalLabel">{{$jotnets_label}}</h4>
						</div>
						<div class="modal-body">
							{{$jotnets}}
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
			{{/if}}
		</div>
		<div id="profile-jot-text-loading"></div>
		<div id="profile-jot-end" class="clear"></div>
	</div>
</form>

<div id="jot-preview-content" style="display:none;"></div>

{{if $showacl}}{{$acl}}{{/if}}

{{if $feature_expire}}
<!-- Modal for item expiry-->
<div class="modal" id="expiryModal" tabindex="-1" role="dialog" aria-labelledby="expiryModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="expiryModalLabel">{{$expires}}</h4>
			</div>
			<div class="modal-body form-group" style="width:90%">
				<div class="date">
					<input type="text" placeholder="yyyy-mm-dd HH:MM" name="start_text" id="expiration-date" class="form-control" />
				</div>
				<script>
					$(function () {
						var picker = $('#expiration-date').datetimepicker({format:'Y-m-d H:i', minDate: 0 });
					});
				</script>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$expiryModalCANCEL}}</button>
				<button id="expiry-modal-OKButton" type="button" class="btn btn-primary">{{$expiryModalOK}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
{{/if}}

{{if $feature_future}}
<div class="modal" id="createdModal" tabindex="-1" role="dialog" aria-labelledby="createdModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="createdModalLabel">{{$future_txt}}</h4>
			</div>
			<div class="modal-body form-group" style="width:90%">
				<div class="date">
					<input type="text" placeholder="yyyy-mm-dd HH:MM" name="created_text" id="created-date" class="form-control" />
				</div>
				<script>
					$(function () {
						var picker = $('#created-date').datetimepicker({format:'Y-m-d H:i', minDate: 0 });
					});
				</script>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$expiryModalCANCEL}}</button>
				<button id="created-modal-OKButton" type="button" class="btn btn-primary">{{$expiryModalOK}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
{{/if}}

{{if $embedPhotos}}
<div class="modal" id="embedPhotoModal" tabindex="-1" role="dialog" aria-labelledby="embedPhotoLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="embedPhotoModalLabel">{{$embedPhotosModalTitle}}</h4>
			</div>
			<div class="modal-body" id="embedPhotoModalBody" >
				<div id="embedPhotoModalBodyAlbumListDialog" class="hide">
					<div id="embedPhotoModalBodyAlbumList"></div>
				</div>
				<div id="embedPhotoModalBodyAlbumDialog" class="hide"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$embedPhotosModalCancel}}</button>
				<button id="embed-photo-OKButton" type="button" class="btn btn-primary">{{$embedPhotosModalOK}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
{{/if}}

{{if $content || $attachment || $expanded}}
<script>initEditor();</script>
{{/if}}
