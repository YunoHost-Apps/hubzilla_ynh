<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<a class="btn btn-success btn-xs pull-right" href="{{$create.0}}" title="{{$create.1}}"><i class="fa fa-plus-circle"></i>&nbsp;{{$create.2}}</a>
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper-np">
		{{if $channel_usage_message}}
		<div id="channel-usage-message" class="section-content-warning-wrapper">
			{{$channel_usage_message}}
		</div>
		{{/if}}
		<div id="channels-desc" class="section-content-info-wrapper">
			{{$desc}}
		</div>
		{{foreach $all_channels as $chn}}
			{{include file="channel.tpl" channel=$chn}}
		{{/foreach}}
		{{if $delegates}}
			{{foreach $delegates as $chn}}
				{{include file="channel.tpl" channel=$chn}}
			{{/foreach}}
		{{/if}}
	</div>
</div>
