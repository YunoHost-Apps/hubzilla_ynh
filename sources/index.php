<?php

namespace Zotlabs\Web;

/**
 * @file index.php
 *
 * @brief The main entry point to the application.
 */

require_once('Zotlabs/Web/WebServer.php');

$server = new WebServer();
$server->run();

