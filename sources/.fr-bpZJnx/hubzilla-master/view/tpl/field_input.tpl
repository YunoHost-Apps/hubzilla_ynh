	<div id="id_{{$field.0}}_wrapper" class='form-group field input'>
		<label for='id_{{$field.0}}' id='label_{{$field.0}}'>{{$field.1}}{{if $field.4}}<span class="required"> {{$field.4}}</span>{{/if}}</label>
		<input class="form-control" name='{{$field.0}}' id='id_{{$field.0}}' type="text" value="{{$field.2}}"{{if $field.5}} {{$field.5}}{{/if}}>
		<span id='help_{{$field.0}}' class='help-block'>{{$field.3}}</span>
		<div class="clear"></div>
	</div>
