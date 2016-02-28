<div class="generic-content-wrapper-styled">
<h2>{{$title}}</h2>

<form action="register" method="post" id="register-form">



{{if $reg_is}}
<div id="register-desc" class="descriptive-paragraph">{{$reg_is}}</div>
{{/if}}
{{if $registertext}}<div id="register-text" class="descriptive-paragraph">{{$registertext}}</div>
{{/if}}
{{if $other_sites}}<div id="register-sites" class="descriptive-paragraph">{{$other_sites}}</div>
{{/if}}

{{if $invitations}}
	<p id="register-invite-desc">{{$invite_desc}}</p>

	<label for="register-invite" id="label-register-invite" class="register-label">{{$label_invite}}</label>
	<input type="text" maxlength="72" size="32" name="invite_code" id="register-invite" class="register-input" value="{{$invite_code}}" />
	<div id="register-invite-feedback" class="register-feedback"></div>
	<div id="register-invite-end" class="register-field-end"></div>
{{/if}}

	{{if $auto_create}}

		{{if $default_role}}
		<input type="hidden" name="permissions_role" value="{{$default_role}}" />
		{{else}}
		<div id="newchannel-role-help" class="descriptive-paragraph">{{$help_role}}</div>
		{{include file="field_select_grouped.tpl" field=$role}}
		<div id="newchannel-role-end"  class="newchannel-field-end"></div>
		{{/if}}

		<div id="newchannel-name-help" class="descriptive-paragraph">{{$help_name}}</div>

		<label for="newchannel-name" id="label-newchannel-name" class="register-label" >{{$label_name}}</label>
		<input type="text" name="name" id="newchannel-name" class="register-input" value="{{$name}}" />
		<div id="name-spinner"></div>
		<div id="newchannel-name-feedback" class="register-feedback"></div>
		<div id="newchannel-name-end"  class="register-field-end"></div>


	{{/if}}




	<label for="register-email" id="label-register-email" class="register-label" >{{$label_email}}</label>
	<input type="text" maxlength="72" size="32" name="email" id="register-email" class="register-input" value="{{$email}}" />
	<div id="register-email-feedback" class="register-feedback"></div>
	<div id="register-email-end"  class="register-field-end"></div>

	<label for="register-password" id="label-register-password" class="register-label" >{{$label_pass1}}</label>
	<input type="password" maxlength="72" size="32" name="password" id="register-password" class="register-input" value="{{$pass1}}" />
	<div id="register-password-feedback" class="register-feedback"></div>
	<div id="register-password-end"  class="register-field-end"></div>

	<label for="register-password2" id="label-register-password2" class="register-label" >{{$label_pass2}}</label>
	<input type="password" maxlength="72" size="32" name="password2" id="register-password2" class="register-input" value="{{$pass2}}" />
	<div id="register-password2-feedback" class="register-feedback"></div>
	<div id="register-password2-end"  class="register-field-end"></div>

	{{if $auto_create}}
		<div id="newchannel-nick-desc" class="descriptive-paragraph">{{$nick_desc}}</div>
		<label for="newchannel-nickname" id="label-newchannel-nickname" class="register-label" >{{$label_nick}}</label>
		<input type="text" name="nickname" id="newchannel-nickname" class="register-input" value="{{$nickname}}" />
		<div id="nick-spinner"></div>
		<div id="newchannel-nickname-feedback" class="register-feedback"></div>
		<div id="newchannel-nickname-end"  class="register-field-end"></div>

	{{/if}}

	{{if $enable_tos}}
	<input type="checkbox" name="tos" id="register-tos" value="1" />
	<label for="register-tos" id="label-register-tos">{{$label_tos}}</label>
	<div id="register-tos-feedback" class="register-feedback"></div>
	<div id="register-tos-end"  class="register-field-end"></div>
	{{else}}
	<input type="hidden" name="tos" value="1" />
	{{/if}}

	<input type="submit" name="submit" class="btn btn-default" id="register-submit-button" value="{{$submit}}" />
	<div id="register-submit-end" class="register-field-end"></div>

</form>
</div>
