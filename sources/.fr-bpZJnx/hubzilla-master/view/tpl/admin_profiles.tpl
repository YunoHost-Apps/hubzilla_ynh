<div class="generic-content-wrapper">
<div class="section-title-wrapper"><a title="{{$new}}" class="btn btn-primary btn-xs pull-right" href="admin/profs/new"><i class="fa fa-plus-circle"></i>&nbsp;{{$new}}</a><h2>{{$title}}</h2>
<div class="clear"></div>
</div>

<div class="section-content-tools-wrapper">

<div class="section-content-info-wrapper">{{$all_desc}}
<br /><br />
{{$all}}
</div>

<form action="admin/profs" method="post">

{{include file="field_textarea.tpl" field=$basic}}
{{include file="field_textarea.tpl" field=$advanced}}

<input type="submit" name="submit" value="{{$submit}}" />

</form>



{{if $cust_fields}}
<br /><br />
<div><strong>{{$cust_field_desc}}</strong></div>
<br />

<table width="100%">
{{foreach $cust_fields as $field}}
<tr><td>{{$field.field_name}}</td><td>{{$field.field_desc}}</td><td><a class="btn btn-danger btn-xs" href="admin/profs/drop/{{$field.id}}" title="{{$drop}}"><i class="fa fa-trash-o"></i>&nbsp;{{$drop}}</a> <a class="btn btn-xs" title="{{$edit}}" href="admin/profs/{{$field.id}}" ><i class="fa fa-pencil"></i></a></td></tr>
{{/foreach}}
</table>
{{/if}}

</div>

</div>
