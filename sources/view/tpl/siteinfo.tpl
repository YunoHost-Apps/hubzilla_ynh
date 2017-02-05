<div class="generic-content-wrapper-styled">
<h2>{{$title}}</h2>

<h3>{{$sitenametxt}}</h3>

<div>{{$sitename}}</div>

<h3>{{$headline}}</h3>

<div>{{if $site_about}}{{$site_about}}{{else}}--{{/if}}</div>

<h3>{{$admin_headline}}</h3>

<div>{{if $admin_about}}{{$admin_about}}{{else}}--{{/if}}</div>

<br><br>
<div><a href="help/TermsOfService">{{$terms}}</a></div>

<hr>

<h2>{{$prj_header}}</h2>

<div>{{$prj_name}} ({{$z_server_role}})</div>

{{if $prj_version}}
<div>{{$prj_version}}</div>
{{/if}}


<h3>{{$prj_linktxt}}</h3>

<div>{{$prj_link}}</div>

<h3>{{$prj_srctxt}}</h3>

<div>{{$prj_src}}</div>

<br><br>
<div>{{$prj_transport}} ({{$transport_link}})</div>

</div>
