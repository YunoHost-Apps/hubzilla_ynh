<div class="event-item-title">
	<h3><i class="fa fa-calendar"></i>&nbsp;{{$title}}</h3>
</div>
<div class="event-item-start">
	<span class="event-item-label">{{$dtstart_label}}</span>&nbsp;<span class="dtstart" title="{{$dtstart_title}}">{{$dtstart_dt}}</span>
</div>
{{if $finish}}
<div class="event-item-start">
	<span class="event-item-label">{{$dtend_label}}</span>&nbsp;<span class="dtend" title="{{$dtend_title}}">{{$dtend_dt}}</span>
</div>
{{/if}}
