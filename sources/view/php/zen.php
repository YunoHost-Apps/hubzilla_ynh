<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
   <?php if(x($page,'content')) echo $page['content']; ?>
</body>
</html>
