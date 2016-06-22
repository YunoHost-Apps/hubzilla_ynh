<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper{{if $item.toplevel}} {{$item.toplevel}} generic-content-wrapper{{/if}}">
	<a name="{{$item.id}}" ></a>
	<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-outside-wrapper-{{$item.id}}" >
		<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}" style="clear:both;">
			{{if $item.photo}}
			<div class="wall-photo-item" id="wall-photo-item-{{$item.id}}">
				{{$item.photo}}
			</div>
			{{/if}}
			{{if $item.event}}
			<div class="wall-event-item" id="wall-event-item-{{$item.id}}">
				{{$item.event}}
			</div>
			{{/if}}
			<div class="wall-item-head">
				<div class="wall-item-info" id="wall-item-info-{{$item.id}}" >
					<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}}" id="wall-item-photo-wrapper-{{$item.id}}">
						<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}"><img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" /></a>
					</div>
					<div class="wall-item-photo-end" style="clear:both"></div>
				</div>
				{{if $item.title}}
				<div class="wall-item-title" id="wall-item-title-{{$item.id}}" title="{{$item.title}}">
				<h3>{{if $item.title_tosource}}{{if $item.plink}}<a href="{{$item.plink.href}}" title="{{$item.title}} ({{$item.plink.title}})">{{/if}}{{/if}}{{$item.title}}{{if $item.title_tosource}}{{if $item.plink}}</a>{{/if}}{{/if}}</h3>
				</div>
				{{/if}}
				{{if $item.lock}}
				<div class="wall-item-lock dropdown">
					<i class="fa fa-lock lockview dropdown-toggle" data-toggle="dropdown" title="{{$item.lock}}" onclick="lockview('item',{{$item.id}});" ></i><ul id="panel-{{$item.id}}" class="lockview-panel dropdown-menu"></ul>&nbsp;
				</div>
				{{/if}}
				<div class="wall-item-author">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}}&nbsp;{{$item.via}}&nbsp;<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
				</div>
				<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}">
					{{if $item.verified}}<i class="fa fa-check item-verified" title="{{$item.verified}}"></i>&nbsp;{{elseif $item.forged}}<i class="fa fa-exclamation item-forged" title="{{$item.forged}}"></i>&nbsp;{{/if}}{{if $item.location}}<span class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}},&nbsp;</span>{{/if}}<span class="autotime" title="{{$item.isotime}}">{{$item.localtime}}{{if $item.editedtime}}&nbsp;{{$item.editedtime}}{{/if}}{{if $item.expiretime}}&nbsp;{{$item.expiretime}}{{/if}}</span>{{if $item.editedtime}}&nbsp;<i class="fa fa-pencil"></i>{{/if}}&nbsp;{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}
				</div>
				<div class="clear"></div>
			</div>
			<div class="{{if $item.is_photo}}wall-photo-item{{else}}wall-item-content{{/if}}" id="wall-item-content-{{$item.id}}">
				<div class="wall-item-body" id="wall-item-body-{{$item.id}}" >
					{{$item.body}}
				</div>
				<div class="clear"></div>
			</div>
			{{if $item.has_tags}}
			<div class="wall-item-tools">
				{{if $item.mentions}}
				<div class="body-tags" id="item-mentions">
					<span class="tag">{{$item.mentions}}</span>
				</div>
				{{/if}}
				{{if $item.tags}}
				<div class="body-tags" id="item-tags">
					<span class="tag">{{$item.tags}}</span>
				</div>
				{{/if}}
				{{if $item.categories}}
				<div class="body-tags" id="item-categories">
					<span class="tag">{{$item.categories}}</span>
				</div>
				{{/if}}
				{{if $item.folders}}
				<div class="body-tags" id="item-folders">
					<span class="tag">{{$item.folders}}</span>
				</div>
				{{/if}}
				<div class="clear"></div>
			</div>
			{{/if}}
			<div class="wall-item-tools">
				<div class="wall-item-tools-right btn-group pull-right">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-caret-down"></i>
					</button>
					<ul class="dropdown-menu">
						{{if $item.item_photo_menu}}
						{{$item.item_photo_menu}}
						{{/if}}
						{{if $item.drop.dropping}}
						<li role="presentation" class="divider"></li>
						<li><a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" title="{{$item.drop.delete}}" ><i class="fa fa-trash-o"></i> {{$item.drop.delete}}</a></li>
						{{/if}}
					</ul>
				</div>
				{{if $item.attachments}}
				<div class="wall-item-tools-left btn-group">
					<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="attachment-menu-{{$item.id}}"><i class="fa fa-paperclip"></i></button>
					<ul class="dropdown-menu" role="menu" aria-labelledby="attachment-menu-{{$item.id}}">{{$item.attachments}}</ul>
				</div>
				{{/if}}
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="wall-item-wrapper-end"></div>
		{{if $item.conv}}
		<div class="wall-item-conv" id="wall-item-conv-{{$item.id}}" >
			<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title}}'>{{$item.conv.title}}</a>
		</div>
		{{/if}}
		<div class="clear{{if $indent}} {{$indent}}{{/if}}"></div>
	</div>
</div>

