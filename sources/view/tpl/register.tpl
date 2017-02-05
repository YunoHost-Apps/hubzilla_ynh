<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<div class="section-content-wrapper">
		<form action="register" method="post" id="register-form">
			{{if $reg_is}}
			<div class="section-content-warning-wrapper">
				<div id="register-desc" class="descriptive-paragraph">{{$reg_is}}</div>
				<div id="register-sites" class="descriptive-paragraph">{{$other_sites}}</div>
			</div>
			{{/if}}

			{{if $registertext}}
			<div id="register-text" class="descriptive-paragraph">{{$registertext}}</div>
			{{/if}}

			{{if $invitations}}
			<div class="section-content-info-wrapper">
				<div id="register-invite-desc" class="descriptive-paragraph">{{$invite_desc}}</div>
			</div>
			{{include file="field_input.tpl" field=$invite_code}}
			{{/if}}

			{{include file="field_input.tpl" field=$email}}

			{{include file="field_password.tpl" field=$pass1}}

			{{include file="field_password.tpl" field=$pass2}}

			{{if $auto_create}}
				{{if $default_role}}
				<input type="hidden" name="permissions_role" value="{{$default_role}}" />
				{{else}}
				<div class="section-content-info-wrapper">
					{{$help_role}}
				</div>
				{{include file="field_select_grouped.tpl" field=$role}}
				{{/if}}

				{{include file="field_input.tpl" field=$name}}
				<div id="name-spinner"></div>

				{{include file="field_input.tpl" field=$nickname}}
				<div id="nick-spinner"></div>
			{{/if}}

			{{if $enable_tos}}
			{{include file="field_checkbox.tpl" field=$tos}}
			{{else}}
			<input type="hidden" name="tos" value="1" />
			{{/if}}

			<button class="btn btn-primary" type="submit" name="submit" id="newchannel-submit-button" value="{{$submit}}">{{$submit}}</button>
			<div id="register-submit-end" class="register-field-end"></div>
		</form>
			<br />
			<div class="descriptive-text">{{$verify_note}}</div>

	</div>
</div>
