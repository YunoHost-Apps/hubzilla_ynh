<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<form action="settings/featured" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		<div class="panel-group" id="settings" role="tablist">
			{{$settings_addons}}
		</div>
	</form>
</div>
