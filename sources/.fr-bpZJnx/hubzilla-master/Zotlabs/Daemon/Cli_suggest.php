<?php /** @file */

namespace Zotlabs\Daemon;

require_once('include/socgraph.php');

class Cli_suggest {

	static public function run($argc,$argv) {

		update_suggestions();

	}
}
