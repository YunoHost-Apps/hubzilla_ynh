<div class="generic-content-wrapper generic-content-wrapper-styled">
<h3>{{$title}}</h3>


<div id="connections-wrapper">
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
<div id="page-end"></div>
</div>
<div id="view-contact-end"></div>
{{$paginate}}
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner"></div>
