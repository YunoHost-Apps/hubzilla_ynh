<form id="add-plugin-repo-form" action="{{$post}}" method="post" >

    <p class="descriptive-text">{{$desc}}</p>
    {{include file="field_input.tpl" field=$repoURL}}
    {{include file="field_input.tpl" field=$repoName}}
    <div class="btn-group pull-right">
		<button id="add-plugin-repo-submit" class="btn btn-primary" type="submit" name="submit" onclick="adminPluginsAddRepo(); return false;">{{$submit}}</button>
	</div>
</form>
