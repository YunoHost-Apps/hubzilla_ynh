<?php /** @file */

require_once("include/network.php");

function system_down() {
http_status(503, 'Service Unavailable');
echo <<< EOT
<html>
<head><title>System Unavailable</title></head>
<body>
Apologies but this site is unavailable at the moment. Please try again later.
</body>
</html>
EOT;
}