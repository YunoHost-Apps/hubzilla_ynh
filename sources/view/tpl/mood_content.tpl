
<div id="mood-content" class="generic-content-wrapper">
    <div class="section-title-wrapper">
    <h2>{{$title}}</h2>
    </div>
    <div class="section-content-wrapper">

	    <div id="mood-desc">{{$desc}}</div>

		<br />
		<br />


		<form action="mood" method="get">
		<br />
		<br />

		<input id="mood-parent" type="hidden" value="{{$parent}}" name="parent" />

		<div class="form-group field custom">
			<select name="verb" id="mood-verb-select" class="form-control" >
			{{foreach $verbs as $v}}
				<option value="{{$v.0}}">{{$v.1}}</option>
			{{/foreach}}
			</select>
		</div>
		<br />
		<br />

		<input type="submit" name="submit" value="{{$submit}}" />
		</form>
	</div>
</div>
