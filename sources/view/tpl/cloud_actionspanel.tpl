<div id="files-mkdir-tools" class="section-content-tools-wrapper">
	<label for="files-mkdir">{{$folder_header}}</label>
	<form id="mkdir-form" method="post" action="file_upload" class="acl-form" data-form_id="mkdir-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
		<input type="hidden" name="folder" value="{{$folder}}" />
		<input type="hidden" name="channick" value="{{$channick}}" />
		<input type="hidden" name="return_url" value="{{$return_url}}" />
		<input id="files-mkdir" type="text" name="filename" class="form-control form-group">
		<div class="pull-right btn-group">
			<div class="btn-group">
				{{if $lockstate}}
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" type="button">
					<i class="jot-perms-icon fa fa-{{$lockstate}}"></i>
				</button>
				{{/if}}
				<button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$folder_submit}}">{{$folder_submit}}</button>
			</div>
		</div>
	</form>
	<div class="clear"></div>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper">
	{{if $quota.limit || $quota.used}}<div class="{{if $quota.warning}}section-content-danger-wrapper{{else}}section-content-info-wrapper{{/if}}">{{if $quota.warning}}<strong>{{$quota.warning}} </strong>{{/if}}{{$quota.desc}}</div>{{/if}}
	<form id="ajax-upload-files" method="post" action="file_upload" enctype="multipart/form-data" class="acl-form" data-form_id="ajax-upload-files" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
		<input type="hidden" name="directory" value="{{$path}}" />
		<input type="hidden" name="channick" value="{{$channick}}" />
		<input type="hidden" name="return_url" value="{{$return_url}}" />
		<label for="files-upload">{{$upload_header}}</label>
		<input class="form-group pull-left" id="files-upload" type="file" name="userfile">
		<div class="pull-right btn-group">
			<div class="btn-group">
				{{if $lockstate}}
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" type="button">
					<i class="jot-perms-icon fa fa-{{$lockstate}}"></i>
				</button>
				{{/if}}
				<button id="upload-submit" class="btn btn-primary btn-sm pull-right" type="submit" name="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
			</div>
		</div>
	</form>
	<div class="clear"></div>
</div>
{{$aclselect}}
