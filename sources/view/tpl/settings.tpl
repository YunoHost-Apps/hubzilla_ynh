<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $server_role != 'basic'}}<a title="{{$removechannel}}" class="btn btn-danger btn-xs pull-right" href="removeme"><i class="fa fa-trash-o"></i>&nbsp;{{$removeme}}</a>{{/if}}
		<h2>{{$ptitle}}</h2>
		<div class="clear"></div>
	</div>
	{{$nickname_block}}
	<form action="settings" id="settings-form" method="post" autocomplete="off" class="acl-form" data-form_id="settings-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}' />
		<div class="panel-group" id="settings" role="tablist" aria-multiselectable="true">
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="basic-settings">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#basic-settings-collapse" aria-expanded="true" aria-controls="basic-settings-collapse">
							{{$h_basic}}
						</a>
					</h3>
				</div>
				<div id="basic-settings-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="basic-settings">
					<div class="section-content-tools-wrapper">
						{{include file="field_input.tpl" field=$username}}
						{{include file="field_select_grouped.tpl" field=$timezone}}
						{{include file="field_input.tpl" field=$defloc}}
						{{include file="field_checkbox.tpl" field=$allowloc}}
						{{include file="field_checkbox.tpl" field=$adult}}
						{{include file="field_input.tpl" field=$photo_path}}
						{{include file="field_input.tpl" field=$attach_path}}

						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="privacy-settings">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#privacy-settings-collapse" aria-expanded="true" aria-controls="privacy-settings-collapse">
							{{$h_prv}}
						</a>
					</h3>
				</div>
				<div id="privacy-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="privacy-settings">
					<div class="section-content-tools-wrapper">
						{{if $server_role != 'basic'}}
						{{include file="field_select_grouped.tpl" field=$role}}
						{{/if}}
						<div id="advanced-perm" style="display:{{if $permissions_set && $server_role != 'basic' }}none{{else}}block{{/if}};">

							{{if $server_role != 'basic'}}
							<div class="form-group">
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#apsModal">{{$lbl_p2macro}}</button>
							</div>
							<div class="modal" id="apsModal">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<h4 class="modal-title">{{$lbl_p2macro}}</h4>
										</div>
										<div class="modal-body">
										{{foreach $permiss_arr as $permit}}
											{{include file="field_select.tpl" field=$permit}}
										{{/foreach}}
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div><!-- /.modal -->
							{{/if}}
							<div id="settings-default-perms" class="form-group" >
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#aclModal"><i id="jot-perms-icon" class="fa"></i>&nbsp;{{$permissions}}</button>
							</div>
							{{$group_select}}
							{{include file="field_checkbox.tpl" field=$hide_presence}}
							{{$profile_in_dir}}
						</div>
						<div class="settings-common-perms">
							{{$suggestme}}
							{{include file="field_checkbox.tpl" field=$blocktags}}
							{{include file="field_input.tpl" field=$expire}}
						</div>
						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="notification-settings">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#notification-settings-collapse" aria-expanded="true" aria-controls="notification-settings-collapse">
							{{$h_not}}
						</a>
					</h3>
				</div>
				<div id="notification-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="notification-settings">
					<div class="section-content-tools-wrapper">
						<div id="settings-notifications">
							<h3>{{$activity_options}}</h3>
							<div class="group">
								{{*not yet implemented *}}
								{{*include file="field_checkbox.tpl" field=$post_joingroup*}}
								{{include file="field_checkbox.tpl" field=$post_newfriend}}
								{{include file="field_checkbox.tpl" field=$post_profilechange}}
							</div>
							<h3>{{$lbl_not}}</h3>
							<div class="group">
								{{include file="field_intcheckbox.tpl" field=$notify1}}
								{{include file="field_intcheckbox.tpl" field=$notify2}}
								{{include file="field_intcheckbox.tpl" field=$notify3}}
								{{include file="field_intcheckbox.tpl" field=$notify4}}
								{{*include file="field_intcheckbox.tpl" field=$notify9*}}
								{{include file="field_intcheckbox.tpl" field=$notify5}}
								{{include file="field_intcheckbox.tpl" field=$notify6}}
								{{include file="field_intcheckbox.tpl" field=$notify7}}
								{{include file="field_intcheckbox.tpl" field=$notify8}}
							</div>
							<h3>{{$lbl_vnot}}</h3>
							<div class="group">
								{{include file="field_intcheckbox.tpl" field=$vnotify1}}
								{{include file="field_intcheckbox.tpl" field=$vnotify2}}
								{{include file="field_intcheckbox.tpl" field=$vnotify3}}
								{{include file="field_intcheckbox.tpl" field=$vnotify4}}
								{{include file="field_intcheckbox.tpl" field=$vnotify5}}
								{{include file="field_intcheckbox.tpl" field=$vnotify6}}
								{{include file="field_intcheckbox.tpl" field=$vnotify10}}
								{{include file="field_intcheckbox.tpl" field=$vnotify7}}
								{{include file="field_intcheckbox.tpl" field=$vnotify8}}
								{{include file="field_intcheckbox.tpl" field=$vnotify9}}
								{{include file="field_intcheckbox.tpl" field=$vnotify11}}
								{{include file="field_intcheckbox.tpl" field=$always_show_in_notices}}
								{{include file="field_input.tpl" field=$evdays}}
							</div>
						</div>
						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="miscellaneous-settings">
					<h3>
						<a data-toggle="collapse" data-parent="#settings" href="#miscellaneous-settings-collapse" aria-expanded="true" aria-controls="miscellaneous-settings-collapse">
							{{$lbl_misc}}
						</a>
					</h3>
				</div>
				<div id="miscellaneous-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="miscellaneous-settings">
					<div class="section-content-tools-wrapper">
						<div class="ffsapilink">
							<a type="button" class="btn btn-default" href="/ffsapi">{{$firefoxshare}}</a>
						</div>
						{{if $menus}}
						<div class="form-group channel-menu">
							<label for="channel_menu">{{$menu_desc}}</label>
							<select name="channel_menu" class="form-control">
							{{foreach $menus as $menu }}
								<option value="{{$menu.name}}" {{$menu.selected}} >{{$menu.name}} </option>
							{{/foreach}}
							</select>
						</div>
						{{/if}}
						{{include file="field_checkbox.tpl" field=$cal_first_day}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
	{{$aclselect}}
</div>
