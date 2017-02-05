<?php

namespace Zotlabs\Web;

/*
 * @brief
 *
 */
class SubModule {

	private $controller = false;

	/**
	 * @brief Submodule constructor.
	 *
	 * Initiate sub-modules. By default the submodule name is in argv(1), though this is configurable.
	 * Example: Given a URL path such as /admin/plugins, and the Admin module initiates sub-modules.
	 * This means we'll look for a class Plugins in Zotlabs/Module/Admin/Plugins.php
	 * The specific methods and calling parameters are up to the top level module controller logic.
	 *
	 * **If** you were to provide sub-module support on the photos module, you would probably use
	 * $whicharg = 2, as photos are typically called with a URL path of /photos/channel_address/submodule_name
	 * where submodule_name might be something like album or image.
	 *
	 * @param int $whicharg
	 */
	function __construct($whicharg = 1) {

		if(argc() < ($whicharg + 1))
			return;

		$filename = 'Zotlabs/Module/' . ucfirst(argv(0)) . '/'. ucfirst(argv($whicharg)) . '.php';
		$modname = '\\Zotlabs\\Module\\' . ucfirst(argv(0)) . '\\' . ucfirst(argv($whicharg));
		if(file_exists($filename)) {
			$this->controller = new $modname();
		}
	}

	/**
	 * @brief
	 *
	 * @param string $method
	 * @return boolean|mixed
	 */
	function call($method) {
		if(! $this->controller)
			return false;

		if(method_exists($this->controller, $method))
			return $this->controller->$method();

		return false;
	}

}
