<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button id="webpage-create-btn" class="btn btn-xs btn-success" onclick="openClose('menu-creator');"><i class="icon-edit"></i>&nbsp;{{$hintnew}}</button>
		</div>
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>

	{{$create}}

	{{if $menus }}
	<div id="menulist-content-wrapper" class="section-content-wrapper-np">
		<table id="menu-list-table">
			<tr>
				<th width="1%"></th>
				<th width="1%">{{$nametitle}}</th>
				<th width="93%">{{$desctitle}}</th>
				<th width="1%"></th>
				<th width="1%"></th>
				<th width="1%"></th>
				<th width="1%" class="hidden-xs">{{$created}}</th>
				<th width="1%" class="hidden-xs">{{$edited}}</th>
			</tr>
			{{foreach $menus as $m }}
			<tr id="menu-list-item-{{$m.menu_id}}">
				<td>{{if $m.bookmark}}<i class="icon-bookmark menu-list-tool" title="{{$bmark}}" ></i>{{/if}}</td>
				<td><a href="mitem/{{$m.menu_id}}{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintcontent}}">{{$m.menu_name}}</a></td>
				<td>{{$m.menu_desc}}</td>
				<td class="menu-list-tool"><a href="menu/{{$m.menu_id}}{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintedit}}"><i class="icon-pencil"></i></a></td>
				<td class="menu-list-tool"><a href="rpost?attachment={{$m.element}}" title="{{$share}}"><i class="icon-share"></i></a></td>
				<td class="menu-list-tool"><a href="#" title="{{$hintdrop}}"  onclick="dropItem('menu/{{$m.menu_id}}/drop{{if $sys}}?f=&sys=1{{/if}}', '#menu-list-item-{{$m.menu_id}}'); return false;"><i class="icon-trash drop-icons"></i></a></td>
				<td class="hidden-xs">{{$m.menu_created}}</td>
				<td class="hidden-xs">{{$m.menu_edited}}</td>
			</tr>
			{{/foreach}}
		</table>
	</div>
	{{/if}}
</div>
