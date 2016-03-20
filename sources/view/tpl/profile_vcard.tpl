<div class="vcard">
	{{if ! $zcard}}
	<div id="profile-photo-wrapper"><img class="photo" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.name}}"></div>
	{{/if}}
	{{if $connect}}
	<div class="connect-btn-wrapper"><a href="{{$connect_url}}" class="btn btn-block btn-success btn-sm"><i class="icon-plus"></i> {{$connect}}</a></div>
	{{/if}}
	{{if ! $zcard}}
	{{if $editmenu.multi}}
	<div class="dropdown">
	<a class="profile-edit-side-link dropdown-toggle" data-toggle="dropdown" href="#" ><i class="icon-pencil" title="{{$editmenu.edit.1}}"></i></a>
	<ul class="dropdown-menu" role="menu">
		{{foreach $editmenu.menu.entries as $e}}
		<li>
			<a href="profiles/{{$e.id}}"><img class="dropdown-menu-img-xs" src='{{$e.photo}}'>{{$e.profile_name}}</a>
		</li>
		{{/foreach}}
		<li><a href="profile_photo" >{{$editmenu.menu.chg_photo}}</a></li>
		{{if $editmenu.menu.cr_new}}<li><a href="profiles/new" id="profile-listing-new-link">{{$editmenu.menu.cr_new}}</a></li>{{/if}}
	</ul>
	</div>
	{{elseif $editmenu}}
	<a class="profile-edit-side-link" href="{{$editmenu.edit.0}}" ><i class="icon-pencil" title="{{$editmenu.edit.1}}"></i></a>
	{{/if}}
	{{/if}}

	{{if ! $zcard}}
	<div class="fn">{{$profile.name}}{{if $profile.online}} <i class="icon-asterisk online-now" title="{{$profile.online}}"></i>{{/if}}</div>
	{{if $reddress}}<div class="reddress" oncopy="return false;">{{$profile.reddress}}</div>{{/if}}		
	{{/if}}
	{{if $pdesc}}<div class="title">{{$profile.pdesc}}</div>{{/if}}
	{{if $location}}
		<dl class="location"><dt class="location-label">{{$location}}</dt> 
		<dd class="adr">
			{{if $profile.address}}<div class="street-address">{{$profile.address}}</div>{{/if}}
			<span class="city-state-zip">
				<span class="locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
				<span class="region">{{$profile.region}}</span>
				<span class="postal-code">{{$profile.postal_code}}</span>
			</span>
			{{if $profile.country_name}}<span class="country-name">{{$profile.country_name}}</span>{{/if}}
		</dd>
		</dl>
	{{/if}}

	{{if $gender}}<dl class="mf"><dt class="gender-label">{{$gender}}</dt> <dd class="x-gender">{{$profile.gender}}</dd></dl>{{/if}}

	{{if $marital}}<dl class="marital"><dt class="marital-label"><span class="heart"><i class="icon-heart"></i>&nbsp;</span>{{$marital}}</dt><dd class="marital-text">{{$profile.marital}}</dd></dl>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url">{{$profile.homepage}}</dd></dl>{{/if}}

	{{if $diaspora}}
	{{include file="diaspora_vcard.tpl"}}
	{{/if}}
</div>
<div id="clear"></div>

{{$rating}}

{{$chanmenu}}

{{$contact_block}}


