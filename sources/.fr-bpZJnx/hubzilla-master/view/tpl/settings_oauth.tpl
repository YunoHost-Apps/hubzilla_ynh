<div class="generic-content-wrapper">
<div class="section-title-wrapper">
	<h2>{{$title}}</h2>
</div>

<div class="section-content-tools-wrapper">
<form action="settings/oauth" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	<div id="profile-edit-links">
		<ul>
			<li>
				<a id="profile-edit-view-link" href="{{$baseurl}}/settings/oauth/add">{{$add}}</a>
			</li>
		</ul>
	</div>

	{{foreach $apps as $app}}
	<div class='oauthapp'>
		<img src='{{$app.icon}}' class="{{if $app.icon}} {{else}}noicon{{/if}}">
		{{if $app.clname}}<h4>{{$app.clname}}</h4>{{else}}<h4>{{$noname}}</h4>{{/if}}
		{{if $app.my}}
			{{if $app.oauth_token}}
			<div class="settings-submit-wrapper" ><button class="settings-submit"  type="submit" name="remove" value="{{$app.oauth_token}}">{{$remove}}</button></div>
			{{/if}}
		{{/if}}
		{{if $app.my}}
		<a href="{{$baseurl}}/settings/oauth/edit/{{$app.client_id}}" title="{{$edit}}"><i class="fa fa-pencil btn btn-default"></i></a>
		<a href="{{$baseurl}}/settings/oauth/delete/{{$app.client_id}}?t={{$form_security_token}}" title="{{$delete}}"><i class="fa fa-trash-o btn btn-default"></i></a>
		{{/if}}		
	</div>
	{{/foreach}}

</form>
</div>
</div>
