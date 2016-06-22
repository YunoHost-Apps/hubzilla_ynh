{{if $categories}}
<div class="categorytags">
{{foreach $categories as $cat}}
<span class="item-category"><i class="fa fa-asterisk cat-icons"></i>{{if $cat.url}}<a href="{{$cat.url}}">{{$cat.term}}</a>{{else}}{{$cat.term}}{{/if}}</span>
{{/foreach}}
</div>
{{/if}}

