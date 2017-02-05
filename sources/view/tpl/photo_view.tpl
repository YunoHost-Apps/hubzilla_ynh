<div id="live-photos"></div>
<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $tools || $map || $edit}}
			<div class="btn-group">
				<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
					<i class="fa fa-caret-down"></i>&nbsp;{{$tools_label}}
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					{{if $tools}}
					<li class="nav-item">
						<a class="nav-link" href="{{$tools.profile.0}}"><i class="fa fa-user"></i>&nbsp;{{$tools.profile.1}}</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="{{$tools.cover.0}}"><i class="fa fa-picture-o"></i>&nbsp;{{$tools.cover.1}}</a>
					</li>
					{{/if}}
					{{if $map}}
					<li class="nav-item">
						<a class="nav-link" href="#" onclick="var pos = $('#photo-map').css('position'); if(pos === 'absolute') { $('#photo-map').css( { position: 'relative', left: 'auto', top: 'auto' }); } else { $('#photo-map').css( { position: 'absolute', left: '-9999px', top: '-9999px' }); } return false; " ><i class="fa fa-globe"></i>&nbsp;{{$map_text}}</a>
					</li>
					{{/if}}
					{{if $edit}}
					<li class="nav-item">
						<a class="nav-link acl-form-trigger" href="#"  title="" onclick="openClose('photo-edit'); return false;" data-form_id="photo_edit_form"><i class="fa fa-pencil"></i>&nbsp;{{$edit.edit}}</a>
					</li>
					{{/if}}
				</ul>
			</div>
			{{/if}}
			{{if $lock}}
			<div class="btn-group">
				<button id="lockview" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" title="{{$lock}}" onclick="lockview('photo',{{$id}});" ><i class="fa fa-lock"></i></button>
				<ul id="panel-{{$id}}" class="lockview-panel dropdown-menu dropdown-menu-right"></ul>
			</div>
			{{/if}}
			{{if $prevlink || $nextlink}}
			<div class="btn-group">
				{{if $prevlink}}
				<a href="{{$prevlink.0}}" class="btn btn-default btn-xs" title="{{$prevlink.1}}"><i class="fa fa-backward"></i></a>
				{{/if}}
				{{if $nextlink}}
				<a href="{{$nextlink.0}}" class="btn btn-default btn-xs" title="{{$nextlink.1}}"><i class="fa fa-forward"></i></a>
				{{/if}}
			</div>
			{{/if}}
		</div>
		<h2>{{if $desc}}{{$desc}}{{elseif $filename}}{{$filename}}{{else}}{{$unknown}}{{/if}}</h2>
		<div class="clear"></div>
	</div>
	<div id="photo-map">
	{{$map}}
	</div>
	<div id="photo-edit" class="section-content-tools-wrapper">
		<form action="photos/{{$edit.nickname}}/{{$edit.resource_id}}" method="post" id="photo_edit_form" class="acl-form" data-form_id="photo_edit_form" data-allow_cid='{{$edit.allow_cid}}' data-allow_gid='{{$edit.allow_gid}}' data-deny_cid='{{$edit.deny_cid}}' data-deny_gid='{{$edit.deny_gid}}'>
			<input type="hidden" name="item_id" value="{{$edit.item_id}}" />
			{{* album renaming is not supported atm.
			<div class="form-group">
				<label id="photo-edit-albumname-label" for="photo-edit-albumname">{{$edit.newalbum_label}}</label>
				<input id="photo-edit-albumname" class="form-control" type="text" name="albname" value="{{$edit.album}}" placeholder="{{$edit.newalbum_placeholder}}" list="dl-albums" />
				{{if $edit.albums}}
				<datalist id="dl-albums">
				{{foreach $edit.albums as $al}}
					{{if $al.text}}
					<option value="{{$al.text}}">
					{{/if}}
				{{/foreach}}
				</datalist>
				{{/if}}
			</div>
			*}}
			<div class="form-group">
				<label id="photo-edit-caption-label" for="photo-edit-caption">{{$edit.capt_label}}</label>
				<input id="photo-edit-caption" class="form-control" type="text" name="desc" value="{{$edit.caption}}" />
			</div>
			<div class="form-group">
				<label id="photo-edit-tags-label" for="photo-edit-newtag">{{$edit.tag_label}}</label>
				<input name="newtag" id="photo-edit-newtag" class="form-control" title="{{$edit.help_tags}}" type="text" />
			</div>
			<div class="form-group">
				{{include file="field_select.tpl" field=$edit.album_select}}
			</div>
			<div class="form-group">
				<label class="radio-inline" id="photo-edit-rotate-cw-label" for="photo-edit-rotate-cw"><input id="photo-edit-rotate-cw" type="radio" name="rotate" value="1" />{{$edit.rotatecw}}</label>
				<label class="radio-inline" id="photo-edit-rotate-ccw-label" for="photo-edit-rotate-ccw"><input id="photo-edit-rotate-ccw" type="radio" name="rotate" value="2" />{{$edit.rotateccw}}</label>
			</div>
			{{if $edit.adult_enabled}}
			<div class="form-group">
			{{include file="field_checkbox.tpl" field=$edit.adult}}
			</div>
			{{/if}}

			<div class="form-group pull-left">
				<button class="btn btn-danger btn-sm" id="photo-edit-delete-button" type="submit" name="delete" value="{{$edit.delete}}" onclick="return confirmDelete();" />{{$edit.delete}}</button>
			</div>
			<div class="form-group btn-group pull-right">
				{{if $edit.aclselect}}
				<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
					<i id="jot-perms-icon" class="fa fa-{{$edit.lockstate}}"></i>
				</button>
				{{/if}}
				<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" name="submit" >{{$edit.submit}}</button>
			</div>
		</form>
		{{$edit.aclselect}}
		<div id="photo-edit-end" class="clear"></div>
	</div>
	<div id="photo-view-wrapper">
		<div id="photo-photo"><a href="{{$photo.href}}" title="{{$photo.title}}" onclick="$.colorbox({href: '{{$photo.href}}'}); return false;"><img style="width: 100%;" src="{{$photo.src}}"></a></div>
		<div id="photo-photo-end" class="clear"></div>
		{{if $tags}}
		<div class="photo-item-tools-left" id="in-this-photo">
			<span id="in-this-photo-text">{{$tag_hdr}}</span>
			{{foreach $tags as $t}}
				{{$t.0}}{{if $edit}}<span id="tag-remove">&nbsp;<a href="{{$t.1}}" onclick="return confirmDelete();"><i class="fa fa-times"></i></a>&nbsp;</span>{{/if}}
			{{/foreach}}
		</div>
		{{/if}}
		<div class="photo-item-tools">
			{{if $responses.count }}
			<div class="photo-item-tools-left pull-left">
				<div class="{{if $responses.count > 1}}btn-group{{/if}}">
				{{foreach $responses as $verb=>$response}}
					{{if $response.count}}
					<div class="btn-group">
						<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="wall-item-{{$verb}}-{{$id}}">{{$response.count}} {{$response.button}}</button>
						{{if $response.list_part}}
						<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-{{$verb}}-{{$id}}">{{foreach $response.list_part as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
						{{else}}
						<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-{{$verb}}-{{$id}}">{{foreach $response.list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
						{{/if}}
						{{if $response.list_part}}
						<div class="modal" id="{{$verb}}Modal-{{$id}}">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
										<h4 class="modal-title">{{$response.title}}</h4>
									</div>
									<div class="modal-body">
										<ul>{{foreach $response.list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
									</div>
									<div class="modal-footer clear">
										<button type="button" class="btn btn-default" data-dismiss="modal">{{$modal_dismiss}}</button>
									</div>
								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div><!-- /.modal -->
						{{/if}}
					</div>
					{{/if}}
				{{/foreach}}
				</div>
			</div>
			{{/if}}
			{{if $likebuttons}}
			<div class="photo-item-tools-right btn-group pull-right">
				<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$likebuttons.id}},'like'); return false">
					<i class="fa fa-thumbs-o-up" title="{{$likebuttons.likethis}}"></i>
				</button>
				<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$likebuttons.id}},'dislike'); return false">
					<i class="fa fa-thumbs-o-down" title="{{$likebuttons.nolike}}"></i>
				</button>
			</div>
			<div id="like-rotator-{{$likebuttons.id}}" class="photo-like-rotator pull-right"></div>
			{{/if}}
			<div class="clear"></div>
		</div>
	</div>
	{{$comments}}
	{{if $commentbox}}
	<div class="wall-item-comment-wrapper{{if $comments}} wall-item-comment-wrapper-wc{{/if}}" >
		{{$commentbox}}
	</div>
	{{/if}}
	<div class="clear"></div>
</div>
{{$paginate}}

