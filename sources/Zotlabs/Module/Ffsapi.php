<?php
namespace Zotlabs\Module;



class Ffsapi extends \Zotlabs\Web\Controller {

	function get() {
	
		$baseurl = z_root();
		$name = get_config('system','sitename');
		$description = t('Share content from Firefox to $Projectname');
		$author = 'Mike Macgirvin';
		$homepage = 'http://hubzilla.org';
		$activate = t('Activate the Firefox $Projectname provider');
	
	$s = <<< EOT
	
	<script>
	
	var baseurl = '$baseurl';
	
	var data = {
	  "origin": baseurl,
	  // currently required
	  "name": '$name',
	  "iconURL": baseurl+"/images/hz-16.png",
	  "icon32URL": baseurl+"/images/hz-32.png",
	  "icon64URL": baseurl+"/images/hz-64.png",
	
	  // at least one of these must be defined
	  // "workerURL": baseurl+"/worker.js",
	  // "sidebarURL": baseurl+"/sidebar.htm",
	  "shareURL": baseurl+"/rpost?f=&url=%{url}",
	
	  // status buttons are scheduled for Firefox 26 or 27
	  //"statusURL": baseurl+"/statusPanel.html",
	
	  // social bookmarks are available in Firefox 26
	  "markURL": baseurl+"/rbmark?f=&url=%{url}&title=%{title}",
	  // icons should be 32x32 pixels
	  // "markedIcon": baseurl+"/images/checkbox-checked-32.png",
	  // "unmarkedIcon": baseurl+"/images/checkbox-unchecked-32.png",
	  "unmarkedIcon": baseurl+"/images/hz-bookmark-32.png",
	
	  // should be available for display purposes
	  "description": "$description",
	  "author": "$author",
	  "homepageURL": "$homepage",
	
	  // optional
	  "version": "1.0"
	}
	
	function activate(node) {
	  var event = new CustomEvent("ActivateSocialFeature");
	  var jdata = JSON.stringify(data);
	  node.setAttribute("data-service", JSON.stringify(data));
	  node.dispatchEvent(event);
	}
	</script>
	
	<button onclick="activate(this)" title="$activate" class="btn btn-primary">$activate</button>
	
EOT;
	
	return $s;
	
	}
	
}
