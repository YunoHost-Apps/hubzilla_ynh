<?php

namespace Zotlabs\Storage;

use PHPGit\Git as PHPGit;

require __DIR__ . '/../../library/PHPGit.autoload.php'; // Load PHPGit dependencies

/**
 * Wrapper class for PHPGit class for git repositories managed by Hubzilla
 *
 * @author Andrew Manning <andrewmanning@grid.reticu.li>
 */
class GitRepo {

	public $url = null;
	public $name = null;
	private $path = null;
	private $channel = null;
	public $git = null;
	private $repoBasePath = null;

	function __construct($channel = 'sys', $url = null, $clone = false, $name = null, $path = null) {

		if ($channel === 'sys' && !is_site_admin()) {
			logger('Only admin can use channel sys');
			return null;
		}

		$this->repoBasePath = __DIR__ . '/../../store/git';
		$this->channel = $channel;
		$this->git = new PHPGit();

		// Allow custom path for repo in the case of , for example
		if ($path) {
			$this->path = $path;
		} else {
			$this->path = $this->repoBasePath . "/" . $this->channel . "/" . $this->name;
		}

		if ($this->isValidGitRepoURL($url)) {
			$this->url = $url;
		}

		if ($name) {
			$this->name = $name;
		} else {
			$this->name = $this->getRepoNameFromURL($url);
		}
		if (!$this->name) {
			logger('Error creating GitRepo. No repo name found.');
			return null;
		}

		if (is_dir($this->path)) {
			// ignore the $url input if it exists
			// TODO: Check if the path is either empty or is a valid git repo and error if not
			$this->git->setRepository($this->path);
			// TODO: get repo metadata 
			return;
		}

		if ($this->url) {
			// create the folder and clone the repo at url to that folder if $clone is true
			if ($clone) {
				if (mkdir($this->path, 0770, true)) {
					$this->git->setRepository($this->path);
					if (!$this->cloneRepo()) {
						// TODO: throw error
						logger('git clone failed: ' . json_encode($this->git));
					}
				} else {
					logger('git repo path could not be created: ' . json_encode($this->git));
				}
			}
		}
	}
	
	public function initRepo() {
		if(!$this->path) return false;
		try {
			return $this->git->init($this->path);
		} catch (\PHPGit\Exception\GitException $ex) {
			return false;
		}
	}

	public function pull() {
		try {
			$success = $this->git->pull();
		} catch (\PHPGit\Exception\GitException $ex) {
			return false;
		}
		return $success;
	}

	public function getRepoPath() {
		return $this->path;
	}

	public function setRepoPath($directory) {
		if (is_dir($directory)) {
			$this->path->$directory;
			$this->git->setRepository($directory);
			return true;
		}
		return false;
	}

	public function setIdentity($user_name, $user_email) {
		// setup user for commit messages
		$this->git->config->set("user.name", $user_name, ['global' => false, 'system' => false]);
		$this->git->config->set("user.email", $user_email, ['global' => false, 'system' => false]);
	}

	public function cloneRepo() {
		if (validate_url($this->url) && $this->isValidGitRepoURL($this->url) && is_dir($this->path)) {
			return $this->git->clone($this->url, $this->path);
		}
	}

	public function probeRepo() {
		$git = $this->git;
		$repo = array();
		$repo['remote'] = $git->remote();
		$repo['branches'] = $git->branch(['all' => true]);
		$repo['logs'] = $git->log(array('limit' => 50));
		return $repo;
	}
	
	// Commit changes to the repo. Default is to stage all changes and commit everything.
	public function commit($msg, $options = array()) {
		try {
			return $this->git->commit($msg, $options);
		} catch (\PHPGit\Exception\GitException $ex) {
			return false;
		}		
	}

	public static function isValidGitRepoURL($url) {
		if (validate_url($url) && strrpos(parse_url($url, PHP_URL_PATH), '.')) {
			return true;
		} else {
			return false;
		}
	}

	public static function getRepoNameFromURL($url) {
		$urlpath = parse_url($url, PHP_URL_PATH);
		$lastslash = strrpos($urlpath, '/') + 1;
		$gitext = strrpos($urlpath, '.');
		if ($gitext) {
			return substr($urlpath, $lastslash, $gitext - $lastslash);
		} else {
			return null;
		}
	}

}
