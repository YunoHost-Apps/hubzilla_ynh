<tr class="highlight">
	<td>
		<label class="mainlabel" for='me_id_{{$field.0}}'>{{$field.1}}</label><br>
		<span class='field_abook_help'>{{$field.6}}</span>
	</td>
	{{if $notself}}
	<td class="abook-them">
		{{if $field.2}}<i class="fa fa-check-square-o"></i>{{else}}<i class="fa fa-square-o"></i>{{/if}}
	</td>
	{{/if}}
	<td class="abook-me">
		{{if $self || !$field.5 || $twocol }}
		<input type="checkbox" name='{{$field.0}}' class='abook-edit-me' id='me_id_{{$field.0}}' value="{{$field.4}}" {{if $field.3}}checked="checked"{{/if}} />
		{{/if}}
		{{if $notself && $field.5}}
		<input type="hidden" name='{{$field.0}}' value="{{if $field.7}}1{{else}}0{{/if}}" />
		{{if $field.3}}<i class="fa fa-check-square-o"></i>{{else}}<i class="fa fa-square-o"></i>{{/if}}
		{{/if}}
	</td>
	<td>
		{{if $field.5}}<span class="permission-inherited">{{$inherited}}{{if $self}}{{if $field.7}} <i class="fa fa-check-square-o"></i>{{else}} <i class="fa fa-square-o"></i>{{/if}}{{/if}}</span>{{/if}}
	</td>
</tr>
