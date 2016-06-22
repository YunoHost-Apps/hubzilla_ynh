<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo z_root() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
	<header><?php if(x($page,'header')) echo $page['header']; ?></header>
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation"><?php if(x($page,'nav')) echo $page['nav']; ?></nav>
	<section><?php if(x($page,'content')) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
</body>
</html>
