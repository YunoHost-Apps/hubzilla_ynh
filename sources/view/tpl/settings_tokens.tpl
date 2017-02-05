<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
	<div class="section-content-tools-wrapper">
		<div class="section-content-info-wrapper">
			{{$desc}}
		</div>

		<form action="settings/tokens" id="settings-account-form" method="post" autocomplete="off" >
			<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
			{{if $atoken}}<input type="hidden" name="atoken_id" value="{{$atoken.atoken_id}}" />{{/if}}
			{{include file="field_input.tpl" field=$name}}
			{{include file="field_input.tpl" field=$token}}
			{{include file="field_input.tpl" field=$expires}}
			<div class="settings-submit-wrapper form-group">
				<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
			</div>
	</div>

	<div class="panel">
		<div class="section-subtitle-wrapper" role="tab" id="perms-tool">
			<h3>
				<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#perms-tool-collapse" aria-expanded="true" aria-controls="perms-tool-collapse">
				{{$permlbl}}
				</a>
			</h3>
		</div>
		<div id="perms-tool-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="perms-tool">
			<div class="section-content-tools-wrapper">
				<div class="section-content-warning-wrapper">
				{{$permnote}}
				</div>

				<table id="perms-tool-table" class=form-group>
					<tr>
						<td></td><td class="abook-them">{{$them}}</td><td colspan="2" class="abook-me">{{$me}}</td>
					</tr>
						{{foreach $perms as $prm}}
						{{include file="field_acheckbox.tpl" field=$prm}}
						{{/foreach}}
				</table>

				<div class="settings-submit-wrapper" >
					<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
				</div>
			</div>
		</div>
	</div>
	</form>

	<div class="descriptive-text">{{$desc2}}</div>
	<ul>
		<li>{{$url1}}<span class="zat-example">?f=&zat=<span class="token-mirror"></span></span></li>
		<li>{{$url2}}<span class="zat-example">?f=&zat=<span class="token-mirror"></span></span></li>
	</ul>

	{{if $tokens}}
	<div class="section-content-wrapper-np">
		<table id="atoken-index">
			{{foreach $tokens as $t}}
			<tr id="atoken-index-{{$t.atoken_id}}" class="atoken-index-row">
				<td width="99%"><a href="settings/tokens/{{$t.atoken_id}}">{{$t.atoken_name}}</a></td>
				<td width="1%" class="atoken-index-tool"><i class="fa fa-trash-o drop-icons" onClick="dropItem('/settings/tokens/{{$t.atoken_id}}/drop', '#atoken-index-{{$t.atoken_id}}')"></i></td>
			</tr>
			{{/foreach}}
		</table>

	</div>
	{{/if}}
</div>
