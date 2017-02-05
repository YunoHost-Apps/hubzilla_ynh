<div class="generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/security" method="post">

	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>


	{{include file="field_checkbox.tpl" field=$block_public}}
	{{include file="field_checkbox.tpl" field=$transport_security}}
	{{include file="field_checkbox.tpl" field=$content_security}}
	{{include file="field_checkbox.tpl" field=$embed_sslonly}}

	{{include file="field_textarea.tpl" field=$allowed_email}}
	{{include file="field_textarea.tpl" field=$not_allowed_email}}	

	{{include file="field_textarea.tpl" field=$whitelisted_sites}}
	{{include file="field_textarea.tpl" field=$blacklisted_sites}}

	{{include file="field_textarea.tpl" field=$whitelisted_channels}}
	{{include file="field_textarea.tpl" field=$blacklisted_channels}}

	{{include file="field_textarea.tpl" field=$embed_allow}}
	{{include file="field_textarea.tpl" field=$embed_deny}}


	<div class="admin-submit-wrapper" >
		<input type="submit" name="submit" class="admin-submit" value="{{$submit}}" />
	</div>

	</form>

</div>
