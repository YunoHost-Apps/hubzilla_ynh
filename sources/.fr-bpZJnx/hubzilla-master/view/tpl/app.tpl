<div class="app-container">
	<div class="app-detail">
		<a href="{{$app.url}}" {{if $ap.target}}target="{{$ap.target}}" {{/if}}{{if $app.desc}}title="{{$app.desc}}{{if $app.price}} ({{$app.price}}){{/if}}"{{else}}title="{{$app.name}}"{{/if}}><img src="{{$app.photo}}" width="80" height="80" />
			<div class="app-name" style="text-align:center;">{{$app.name}}</div>
		</a>
	</div>
	{{if $app.type !== 'system'}}
	{{if $purchase}}
	<div class="app-purchase">
		<a href="{{$app.page}}" class="btn btn-default" title="{{$purchase}}" ><i class="fa fa-external"></i></a>
	</div>
	{{/if}}
	{{if $install || $update || $delete }}
	<div class="app-tools">
		<form action="{{$hosturl}}appman" method="post">
		<input type="hidden" name="papp" value="{{$app.papp}}" />
		{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-default" title="{{$install}}" ><i class="fa fa-arrow-circle-o-down" ></i></button>{{/if}}
		{{if $edit}}<input type="hidden" name="appid" value="{{$app.guid}}" /><button type="submit" name="edit" value="{{$edit}}" class="btn btn-default" title="{{$edit}}" ><i class="fa fa-pencil" ></i></button>{{/if}}
		{{if $delete}}<button type="submit" name="delete" value="{{$delete}}" class="btn btn-default" title="{{$delete}}" ><i class="fa fa-trash-o drop-icons"></i></button>{{/if}}
		</form>
	</div>
	{{/if}}
	{{/if}}
</div>

