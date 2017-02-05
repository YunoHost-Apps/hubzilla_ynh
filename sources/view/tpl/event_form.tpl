<form id="event-edit-form" action="{{$post}}" method="post" class="acl-form" data-form_id="event-edit-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="event_hash" value="{{$event_hash}}" />
	<input type="hidden" name="xchan" value="{{$xchan}}" />
	<input type="hidden" name="mid" value="{{$mid}}" />
	<input type="hidden" name="type" value="{{$type}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />


	{{include file="field_input.tpl" field=$summary}}

	{{$s_dsel}}

	{{$f_dsel}}

	{{include file="field_checkbox.tpl" field=$nofinish}}

	<div id="advanced" style="display:none">

		{{include file="field_checkbox.tpl" field=$adjust}}

		{{if $catsenabled}}
		<div id="event-category-text"><b>{{$c_text}}</b></div>
		<div id="events-category-wrap">
			<input name="category" id="event-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" data-role="cat-tagsinput" />
		</div>
		{{/if}}

		<div class="form-group">
			<div id="event-desc-text"><b>{{$d_text}}</b></div>
			<textarea id="comment-edit-text-desc" class="form-control" name="desc" >{{$d_orig}}</textarea>
			<div id="comment-tools-desc" class="comment-tools" style="display: block;" >
				<div id="comment-edit-bb-desc" class="btn-toolbar">
					<div class='btn-group'>
						<button type="button" class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('none','b', 'desc');">
							<i class="fa fa-bold comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('none','i', 'desc');">
							<i class="fa fa-italic comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('none','u', 'desc');">
							<i class="fa fa-underline comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('none','quote','desc');">
							<i class="fa fa-quote-left comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('none','code', 'desc');">
							<i class="fa fa-terminal comment-icon"></i>
						</button>
					</div>
					<div class='btn-group'>
						<button type="button" class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('none','img', 'desc');">
							<i class="fa fa-camera comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('none','url', 'desc');">
							<i class="fa fa-link comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('none','video', 'desc');">
							<i class="fa fa-video-camera comment-icon"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div id="event-location-text"><b>{{$l_text}}</b></div>
			<textarea id="comment-edit-text-loc" class="form-control" name="location">{{$l_orig}}</textarea>
			<div id="comment-tools-loc" class="comment-tools" style="display: block;" >
				<div id="comment-edit-bb-loc" class="btn-toolbar">
					<div class='btn-group'>
						<button type="button" class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('none','b', 'loc');">
							<i class="fa fa-bold comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('none','i', 'loc');">
							<i class="fa fa-italic comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('none','u', 'loc');">
							<i class="fa fa-underline comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('none','quote','loc');">
							<i class="fa fa-quote-left comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('none','code', 'loc');">
							<i class="fa fa-terminal comment-icon"></i>
						</button>
					</div>
					<div class='btn-group'>
						<button type="button" class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('none','img', 'loc');">
							<i class="fa fa-camera comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('none','url', 'loc');">
							<i class="fa fa-link comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('none','video', 'loc');">
							<i class="fa fa-video-camera comment-icon"></i>
						</button>
						<button type="button" class="btn btn-default btn-xs" title="{{$mapper}}" onclick="insertbbcomment('none','map','loc');">
							<i class="fa fa-globe comment-icon"></i>
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>

	<div class="clear"></div>

	<button type="button" class="btn btn-default" onclick="openClose('advanced');">{{$advanced}}</button>
	<div class="btn-group pull-right">
		<button id="event-edit-preview-btn" class="btn btn-default" type="button" title="{{$preview}}" onclick="doEventPreview();"><i class="fa fa-eye" ></i></button>
		{{if ! $eid}}
		<button id="dbtn-acl" class="btn btn-default" type="button" data-toggle="modal" data-target="#aclModal" title="{{$perms_label}}"><i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i></button>
		{{/if}}
		<button id="event-submit" class="btn btn-primary" type="submit" name="submit">{{$submit}}</button>
	</div>
</form>

{{if ! $eid}}
	{{$acl}}
{{/if}}
