{{$contact_block}}

	{{if $connect}}
	<div class="connect-btn-wrapper"><a href="{{$connect_url}}" class="btn btn-block btn-success btn-sm"><i class="fa fa-plus"></i> {{$connect}}</a></div>
	{{/if}}


{{$rating}}

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
	

	{{if $marital}}<dl class="marital"><dt class="marital-label"><span class="heart"><i class="fa fa-heart"></i>&nbsp;</span>{{$marital}}</dt><dd class="marital-text">{{$profile.marital}}</dd></dl>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url">{{$profile.homepage}}</dd></dl>{{/if}}

	{{if $diaspora}}
	{{include file="diaspora_vcard.tpl"}}
	{{/if}}

<div id="clear"></div>

{{$chanmenu}}



