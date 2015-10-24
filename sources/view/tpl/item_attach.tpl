{{if $attaches}}
{{foreach $attaches as $a}}
<a href="{{$a.url}}" title="{{$a.title}}" class="btn btn-xs btn-default"><i class="{{$a.icon}} attach-icons"></i>&nbsp;{{$a.label}}</a>
{{/foreach}}
{{/if}}
