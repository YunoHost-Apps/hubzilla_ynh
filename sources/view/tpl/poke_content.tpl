
<div id="poke-content" class="generic-content-wrapper">
    <div class="section-title-wrapper">
    <h2>{{$title}}</h2>
    </div>
    <div class="section-content-wrapper">

	<div id="poke-desc">{{$desc}}</div>

<br />
<br />


<form action="poke" method="get">


<div class="form-group field input">
	<label id="poke-recip-label" for="poke-recip">{{$clabel}}</label>
	<input class="form-control" id="poke-recip" type="text" value="{{$name}}" name="pokename" autocomplete="off" />
</div>

	<input id="poke-recip-complete" type="hidden" value="{{$id}}" name="cid" />
	<input id="poke-parent" type="hidden" value="{{$parent}}" name="parent" />


{{if $poke_basic}}
<input type="hidden" name="verb" value="poke" />
{{else}}
<div class="form-group field custom">
	<label for="poke-verb-select" id="poke-verb-lbl">{{$choice}}</label>
	<select class="form-control" name="verb" id="poke-verb-select" >
	{{foreach $verbs as $v}}
	<option value="{{$v.0}}">{{$v.1}}</option>
	{{/foreach}}
	</select>
</div>
{{/if}}

{{if ! $parent}}
{{include file="field_checkbox.tpl" field=$private}}
{{/if}}

<input type="submit" name="submit" value="{{$submit}}" />
</form>


    </div>
</div>
