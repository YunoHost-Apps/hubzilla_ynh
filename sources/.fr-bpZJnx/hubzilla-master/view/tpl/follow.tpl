<div id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>
	<form action="follow" method="post" />
		<div class="form-group">
			<div class="input-group">
				<input class="widget-input" type="text" name="url" title="{{$hint}}" placeholder="{{$desc}}" />
				<div class="input-group-btn">
					<button class="btn btn-default btn-sm" type="submit" name="submit" value="{{$follow}}"><i class="fa fa-plus"></i></button>
				</div>
			</div>
		</div>
	</form>
	{{if $abook_usage_message}}
	<div class="usage-message" id="abook-usage-message">{{$abook_usage_message}}</div>
	{{/if}}
</div>

