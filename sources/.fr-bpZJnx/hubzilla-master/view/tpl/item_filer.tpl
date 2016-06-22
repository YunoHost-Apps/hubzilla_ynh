{{if $categories}}
<div class="filesavetags">
{{foreach $categories as $cat}}
<span class="item-category"><i class="fa fa-folder cat-icons"></i>&nbsp;{{$cat.term}}&nbsp;<a href="{{$cat.removelink}}" class="category-remove-link" title="{{$remove}}"><i class="fa fa-trash-o drop-icons"></i></a></span>
{{/foreach}}
</div>
{{/if}}

