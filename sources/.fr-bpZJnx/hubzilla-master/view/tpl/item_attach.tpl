{{if $attaches}}
{{foreach $attaches as $a}}
<li><a href="{{$a.url}}" title="{{$a.title}}"><i class="fa {{$a.icon}} attach-icons"></i>&nbsp;{{$a.label}}</a></li>
{{/foreach}}
{{/if}}
