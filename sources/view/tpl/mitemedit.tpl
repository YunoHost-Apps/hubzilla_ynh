{{if $header}}
<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$header}}</h2>
	</div>
{{/if}}
	<div id="menu-element-creator" class="section-content-tools-wrapper" style="display: {{$display}};">
		<form id="mitemedit" action="mitem/{{$menu_id}}{{if $mitem_id}}/{{$mitem_id}}{{/if}}{{if $sys}}?f=&sys=1{{/if}}" method="post" >
			<input type="hidden" name="menu_id" value="{{$menu_id}}" />
			{{if $mitem_id}}
			<input type="hidden" name="mitem_id" value="{{$mitem_id}}" />
			{{/if}}
			{{include file="field_input.tpl" field=$mitem_desc}}
			{{include file="field_input.tpl" field=$mitem_link}}
			{{if $menu_names}}
			<datalist id="menu-names">
				{{foreach $menu_names as $menu_name}}
				<option value="{{$menu_name}}">
				{{/foreach}}
			</datalist>
			{{/if}}
			{{include file="field_input.tpl" field=$mitem_order}}
			{{include file="field_checkbox.tpl" field=$usezid}}
			{{include file="field_checkbox.tpl" field=$newwin}}
			<div class="pull-right form-group">
				<div class="btn-group">
					<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
						<i id="jot-perms-icon" class="icon-{{$lockstate}}"></i>
					</button>
					{{if $submit_more}}
					<button class="btn btn-primary btn-sm" type="submit" name="submit-more" value="{{$submit_more}}">{{$submit_more}}&nbsp;<i class="icon-caret-right"></i></button>
					{{/if}}
					<button class="btn btn-primary btn-sm" type="submit" name="submit" value="{{$submit}}">{{$submit}}</button>
				</div>
				{{$aclselect}}
			</div>
			<div class="clear"></div>
		</form>
	</div>
{{if $header}}
</div>
{{/if}}
