<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="dropdown pull-right" id="profile-edit-links">
			<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<i class="fa fa-caret-down"></i>&nbsp;{{$tools_label}}
			</button>
			<ul class="dropdown-menu">
				<li class="nav-item">
					<a class="nav-link" href="profile_photo" id="profile-photo_upload-link" title="{{$profpic}}"><i class="fa fa-user"></i>&nbsp;{{$profpic}}</a>
				</li>
				{{if $is_default}}
				<li class="nav-item">
					<a href="cover_photo" id="cover-photo_upload-link" title="{{$coverpic}}"><i class="fa fa-picture-o"></i>&nbsp;{{$coverpic}}</a>
				</li>
				{{/if}}
				{{if ! $is_default}}
				<li class="nav-item">
					<a href="profperm/{{$profile_id}}" id="profile-edit-visibility-link" title="{{$editvis}}"><i class="fa fa-pencil"></i>&nbsp;{{$editvis}}</a>
				</li>
				{{/if}}
				<li class="nav-item">
					<a href="thing" id="profile-edit-thing-link" title="{{$addthing}}"><i class="fa fa-plus-circle"></i>&nbsp;{{$addthing}}</a>
				</li>
				<li class="divider"></li>
				<li class="nav-item">
					<a href="profile/{{$profile_id}}/view" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a>
				</li>

				{{if $profile_clone_link}}
				<li class="divider"></li>
				<li class="nav-item">
					<a href="{{$profile_clone_link}}" id="profile-edit-clone-link" title="{{$cr_prof}}">{{$cl_prof}}</a>
				</li>
				{{/if}}
				{{if $exportable}}
				<li class="divider"></li>
				<li class="nav-item">
					<a href="profiles/export/{{$profile_id}}">{{$lbl_export}}</a>
				</li>
				<li class="nav-item">
					<a href="#" onClick="openClose('profile-upload-form'); return false;">{{$lbl_import}}</a>
				</li>
				{{/if}}
				{{if ! $is_default}}
				<li class="divider"></li>
				<li class="nav-item">
					<a href="{{$profile_drop_link}}" id="profile-edit-drop-link" title="{{$del_prof}}" onclick="return confirmDelete();"><i class="fa fa-trash-o"></i>&nbsp;{{$del_prof}}</a>
				</li>
				{{/if}}
			<ul>
		</div>
		<h2>{{$banner}}</h2>
		<div class="clear"></div>
	</div>
	<div class="section-content-tools-wrapper" id="profile-upload-form">
		<label id="profile-upload-choose-label" for="profile-upload-choose" >{{$lbl_import}}</label>
		<input id="profile-upload-choose" type="file" name="userfile">
	</div>

		<form id="profile-edit-form" name="form1" action="profiles/{{$profile_id}}" enctype="multipart/form-data" method="post" >
			<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

			{{if $is_default}}
			<div class="section-content-info-wrapper">{{$default}}</div>
			{{/if}}

			<div class="panel-group" id="profile-edit-wrapper" role="tablist" aria-multiselectable="true">
				<div class="panel">
					<div class="section-subtitle-wrapper" role="tab" id="personal">
						<h3>
							<a data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#personal-collapse" aria-expanded="true" aria-controls="personal-collapse">
								{{$personal}}
							</a>
						</h3>
					</div>
					<div id="personal-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="personal">
						<div class="section-content-tools-wrapper">
							{{include file="field_input.tpl" field=$profile_name}}

							{{include file="field_input.tpl" field=$name}}

							{{if $fields.pdesc}}
							{{include file="field_input.tpl" field=$pdesc}}
							{{/if}}

							{{if $fields.gender}}
							<div id="profile-edit-gender-wrapper" class="form-group field select" >
							<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}}</label>
							{{if $advanced}}
							{{$gender}}
							{{else}}
							{{$gender_min}}
							{{/if}}
							</div>
							<div class="clear"></div>
							{{/if}}

							{{if $fields.dob}}
							{{$dob}}
							{{/if}}

							{{include file="field_checkbox.tpl" field=$hide_friends}}

							<div class="form-group" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				{{if $fields.address || $fields.locality || $fields.postal_code || $fields.region || $fields.country_name || $fields.hometown}}
				<div class="panel">
					<div class="section-subtitle-wrapper" role="tab" id="location">
						<h3>
							<a data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#location-collapse" aria-expanded="true" aria-controls="location-collapse">
								{{$location}}
							</a>
						</h3>
					</div>
					<div id="location-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="location">
						<div class="section-content-tools-wrapper">
							{{if $fields.address}}
							{{include file="field_input.tpl" field=$address}}
							{{/if}}

							{{if $fields.locality}}
							{{include file="field_input.tpl" field=$locality}}
							{{/if}}

							{{if $fields.postal_code}}
							{{include file="field_input.tpl" field=$postal_code}}
							{{/if}}

							{{if $fields.region}}
							{{include file="field_input.tpl" field=$region}}
							{{/if}}

							{{if $fields.country_name}}
							{{include file="field_input.tpl" field=$country_name}}
							{{/if}}

							{{if $fields.hometown}}
							{{include file="field_input.tpl" field=$hometown}}
							{{/if}}

							<div class="form-group" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				{{/if}}
				{{if $fields.marital || $fields.sexual}}
				<div class="panel">
					<div class="section-subtitle-wrapper" role="tab" id="relation">
						<h3>
							<a data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#relation-collapse" aria-expanded="true" aria-controls="relation-collapse">
								{{$relation}}
							</a>
						</h3>
					</div>
					<div id="relation-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="relation">
						<div class="section-content-tools-wrapper">
							{{if $fields.marital }}
							<div id="profile-edit-marital-wrapper" class="form-group field" >
							<label id="profile-edit-marital-label" for="profile-edit-marital" ><span class="heart"><i class="fa fa-heart"></i>&nbsp;</span>{{$lbl_marital}}</label>
							{{if $advanced}}
							{{$marital}}
							{{else}}
							{{$marital_min}}
							{{/if}}
							</div>
							<div class="clear"></div>

							{{if $fields.partner}}
							{{include file="field_input.tpl" field=$with}}
							{{/if}}

							{{if $fields.howlong}}
							{{include file="field_input.tpl" field=$howlong}}
							{{/if}}
							{{/if}}

							{{if $fields.sexual}}
							<div id="profile-edit-sexual-wrapper" class="form-group field" >
							<label id="profile-edit-sexual-label" for="sexual-select" >{{$lbl_sexual}}</label>
							{{$sexual}}
							</div>
							<div class="clear"></div>
							{{/if}}

							<div class="form-group" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				{{/if}}
				{{if $fields.keywords || $fields.politic || $fields.religion || $fields.about || $fields.contact || $fields.homepage || $fields.interest || $fields.likes || $fields.dislikes || $fields.channels || $fields.music || $fields.book || $fields.tv || $fields.film || $fields.romance || $fields.employment || $fields.education || $extra_fields}}
				<div class="panel">
					<div class="section-subtitle-wrapper" role="tab" id="miscellaneous">
						<h3>
							<a data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#miscellaneous-collapse" aria-expanded="true" aria-controls="miscellaneous-collapse">
								{{$miscellaneous}}
							</a>
						</h3>
					</div>
					<div id="miscellaneous-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="miscellaneous">
						<div class="section-content-tools-wrapper">
							{{if $fields.homepage}}
							{{include file="field_input.tpl" field=$homepage}}
							{{/if}}

							{{if $fields.keywords}}
							{{include file="field_input.tpl" field=$keywords}}
							{{/if}}

							{{if $fields.politic}}
							{{include file="field_input.tpl" field=$politic}}
							{{/if}}

							{{if $fields.religion}}
							{{include file="field_input.tpl" field=$religion}}
							{{/if}}

							{{if $fields.about}}
							{{include file="field_textarea.tpl" field=$about}}
							{{/if}}

							{{if $fields.contact}}
							{{include file="field_textarea.tpl" field=$contact}}
							{{/if}}

							{{if $fields.interest}}
							{{include file="field_textarea.tpl" field=$interest}}
							{{/if}}

							{{if $fields.likes}}
							{{include file="field_textarea.tpl" field=$likes}}
							{{/if}}

							{{if $fields.dislikes}}
							{{include file="field_textarea.tpl" field=$dislikes}}
							{{/if}}

							{{if $fields.channels}}
							{{include file="field_textarea.tpl" field=$channels}}
							{{/if}}

							{{if $fields.music}}
							{{include file="field_textarea.tpl" field=$music}}
							{{/if}}

							{{if $fields.book}}
							{{include file="field_textarea.tpl" field=$book}}
							{{/if}}

							{{if $fields.tv}}
							{{include file="field_textarea.tpl" field=$tv}}
							{{/if}}

							{{if $fields.film}}
							{{include file="field_textarea.tpl" field=$film}}
							{{/if}}

							{{if $fields.romance}}
							{{include file="field_textarea.tpl" field=$romance}}
							{{/if}}

							{{if $fields.employment}}
							{{include file="field_textarea.tpl" field=$work}}
							{{/if}}

							{{if $fields.education}}
							{{include file="field_textarea.tpl" field=$education}}
							{{/if}}

							{{if $extra_fields}}
							{{foreach $extra_fields as $field }}
							{{include file="field_input.tpl" field=$field}}
							{{/foreach}}
							{{/if}}
							<div class="form-group" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				{{/if}}
			</div>
		</form>
</div>

