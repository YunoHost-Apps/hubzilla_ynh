<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Zotlabs\Lib;

/**
 * Description of ExtendedZip
 *
 * @author andrew
 */
class ExtendedZip extends \ZipArchive {
		
		// Member function to add a whole file system subtree to the archive
		public function addTree($dirname, $localname = '') {
				if ($localname)
						$this->addEmptyDir($localname);
				$this->_addTree($dirname, $localname);
		}

		// Internal function, to recurse
		protected function _addTree($dirname, $localname) {
				$dir = opendir($dirname);
				while ($filename = readdir($dir)) {
						// Discard . and ..
						if ($filename == '.' || $filename == '..')
								continue;

						// Proceed according to type
						$path = $dirname . '/' . $filename;
						$localpath = $localname ? ($localname . '/' . $filename) : $filename;
						if (is_dir($path)) {
								// Directory: add & recurse
								$this->addEmptyDir($localpath);
								$this->_addTree($path, $localpath);
						}
						else if (is_file($path)) {
								// File: just add
								$this->addFile($path, $localpath);
						}
				}
				closedir($dir);
		}

		// Helper function
		public static function zipTree($dirname, $zipFilename, $flags = 0, $localname = '') {
				$zip = new self();
				$zip->open($zipFilename, $flags);
				$zip->addTree($dirname, $localname);
				$zip->close();
		}
		
}
