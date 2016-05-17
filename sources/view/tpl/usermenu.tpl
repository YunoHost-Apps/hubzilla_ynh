{{if $wrap}}
<div id="pmenu-{{$id}}" class="pmenu{{if !$class}} widget{{else}} {{$class}}{{/if}}">
{{/if}}
	{{if $menu.menu_desc}}
	<h3 class="pmenu-title">{{$menu.menu_desc}}{{if $edit}} <a href="mitem/{{$menu.menu_id}}" title="{{$edit}}"><i class="fa fa-pencil fakelink" title="{{$edit}}"></i></a>{{/if}}</h3>
	{{/if}}
	{{if $items}}
	<ul class="pmenu-body{{if $wrap || !$class}} nav nav-pills nav-stacked{{elseif !$wrap || $class}} {{$class}}{{/if}}">
		{{foreach $items as $mitem }}
		<li id="pmenu-item-{{$mitem.mitem_id}}" class="pmenu-item{{if $mitem.submenu}} dropdown{{/if}}">
			<a href="{{if $mitem.submenu}}#{{else}}{{$mitem.mitem_link}}{{/if}}"{{if $mitem.submenu}} class="dropdown-toggle" data-toggle="dropdown"{{/if}}{{if $mitem.newwin}}target="_blank"{{/if}}>{{$mitem.mitem_desc}}{{if $mitem.submenu}}<span class="caret"></span>{{/if}}</a>
			{{if $mitem.submenu}}{{$mitem.submenu}}{{/if}}
		</li>
		{{/foreach }}
	</ul>
	{{/if}}
{{if $wrap}}
	<div class="pmenu-end"></div>
</div>
{{/if}}
