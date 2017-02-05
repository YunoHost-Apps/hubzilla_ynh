<script>
	$(function(){
		
		$("#cnftheme").colorbox({
			width: 800,
			onLoad: function(){
				var theme = $("#id_theme :selected").val();
				$("#cnftheme").attr('href',"{{$baseurl}}/admin/themes/"+theme);
			}, 
			onComplete: function(){
				$(this).colorbox.resize(); 
				$("#colorbox form").submit(function(e){
					var url = $(this).attr('action');
					// can't get .serialize() to work...
					var data={};
					$(this).find("input").each(function(){
						data[$(this).attr('name')] = $(this).val();
					});
					$(this).find("select").each(function(){
						data[$(this).attr('name')] = $(this).children(":selected").val();
					});
					console.log(":)", url, data);
					
					$.post(url, data, function(data) {
						if(timer) clearTimeout(timer);
						NavUpdate();
						$.colorbox.close();
					})
					
					return false;
				});
				
			}
		});
	});
</script>
<div id="adminpage" class="generic-content-wrapper-styled">
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/site" method="post">
    <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_input.tpl" field=$sitename}}
	{{include file="field_select.tpl" field=$server_role}}

	{{if $z_server_role == 'pro'}}
	{{include file="field_select.tpl" field=$techlevel}}
    {{include file="field_checkbox.tpl" field=$techlock}}
	{{/if}}

	{{include file="field_textarea.tpl" field=$banner}}
	{{include file="field_textarea.tpl" field=$siteinfo}}
	{{include file="field_textarea.tpl" field=$admininfo}}
	{{include file="field_select.tpl" field=$language}}
	{{include file="field_select.tpl" field=$theme}}
    {{include file="field_select.tpl" field=$theme_mobile}}
    {{include file="field_input.tpl" field=$frontpage}}
    {{include file="field_checkbox.tpl" field=$mirror_frontpage}}
    {{include file="field_checkbox.tpl" field=$login_on_homepage}}
    {{include file="field_checkbox.tpl" field=$enable_context_help}}
	{{if $directory_server}}
		{{include file="field_select.tpl" field=$directory_server}}
	{{/if}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$registration}}</h3>
	{{include file="field_input.tpl" field=$register_text}}
	{{include file="field_select.tpl" field=$register_policy}}
	{{include file="field_checkbox.tpl" field=$invite_only}}
	{{include file="field_select.tpl" field=$access_policy}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>

	<h3>{{$upload}}</h3>
	{{include file="field_input.tpl" field=$maximagesize}}
	
	<h3>{{$corporate}}</h3>
	{{include file="field_checkbox.tpl" field=$verify_email}}
	{{include file="field_checkbox.tpl" field=$feed_contacts}}
	{{include file="field_checkbox.tpl" field=$force_publish}}
	{{include file="field_checkbox.tpl" field=$disable_discover_tab}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$advanced}}</h3>
	{{include file="field_input.tpl" field=$proxy}}
	{{include file="field_input.tpl" field=$proxyuser}}
	{{include file="field_input.tpl" field=$timeout}}
	{{include file="field_input.tpl" field=$delivery_interval}}
	{{include file="field_input.tpl" field=$delivery_batch_count}}
	{{include file="field_input.tpl" field=$poll_interval}}
	{{include file="field_input.tpl" field=$maxloadavg}}
	{{include file="field_input.tpl" field=$abandon_days}}
	{{include file="field_input.tpl" field=$default_expire_days}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	</form>
</div>
