<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $editor}}
		<div class="pull-right">
			<button id="webpage-create-btn" class="btn btn-xs btn-success acl-form-trigger" onclick="openClose('webpage-editor');" data-form_id="profile-jot-form"><i class="fa fa-pencil-square-o"></i>&nbsp;{{$create}}</button>
		</div>
		{{/if}}
		<h2>{{$listtitle}}</h2>
		<div class="clear"></div>
	</div>
	{{if $editor}}
	<div id="webpage-editor" class="section-content-tools-wrapper">
		{{$editor}}
	</div>
	{{/if}}
	{{if $pages}}
	<div id="pagelist-content-wrapper" class="section-content-wrapper-np">
		<table id="webpage-list-table">
			<tr>
				<th width="1%">{{$pagelink_txt}}</th>
				<th width="95%">{{$title_txt}}</th>
				<th width="1%"></th>
				<th width="1%"></th>
				<th width="1%"></th>
				<th width="1%"></th>
				<th width="1%" class="hidden-xs">{{$created_txt}}</th>
				<th width="1%" class="hidden-xs">{{$edited_txt}}</th>
			</tr>
			{{foreach $pages as $key => $items}}
			{{foreach $items as $item}}
			<tr id="webpage-list-item-{{$item.url}}">
				<td>
					{{if $view}}
					<a href="page/{{$channel}}/{{$item.pagetitle}}" title="{{$view}}">{{$item.pagetitle}}</a>
					{{else}}
					{{$item.pagetitle}}
					{{/if}}
				</td>
				<td>
					{{$item.title}}
				</td>
				<td class="webpage-list-tool dropdown">
					{{if $item.lockstate=='lock'}}
					<i class="fa fa-lock dropdown-toggle lockview" data-toggle="dropdown" onclick="lockview('item',{{$item.url}});" ></i>
					<ul id="panel-{{$item.url}}" class="lockview-panel dropdown-menu"></ul>
					{{/if}}
				</td>
				<td class="webpage-list-tool">
					{{if $edit}}
					<a href="{{$baseurl}}/{{$item.url}}" title="{{$edit}}"><i class="fa fa-pencil"></i></a>
					{{/if}}
				</td>
				<td class="webpage-list-tool">
					{{if $item.bb_element}}
					<a href="rpost?attachment={{$item.bb_element}}" title="{{$share}}"><i class="fa fa-share-square-o"></i></a>
					{{/if}}
				</td>
				<td class="webpage-list-tool">
					{{if $edit}}
					<a href="#" title="{{$delete}}" onclick="dropItem('item/drop/{{$item.url}}', '#webpage-list-item-{{$item.url}}'); return false;"><i class="fa fa-trash-o drop-icons"></i></a>
					{{/if}}
				</td>
				<td class="hidden-xs">
					{{$item.created}}
				</td>
				<td class="hidden-xs">
					{{$item.edited}}
				</td>
			</tr>
			{{/foreach}}
			{{/foreach}}
		</table>
	</div>
	{{/if}}
	<div class="clear"></div>
</div>
