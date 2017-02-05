<?php

namespace Zotlabs\Storage;

use Sabre\DAV;

/**
 * @brief This class represents a file in DAV.
 *
 * It provides all functions to work with files in Red's cloud through DAV protocol.
 *
 * @extends \\Sabre\\DAV\\Node
 * @implements \\Sabre\\DAV\\IFile
 *
 * @link http://github.com/friendica/red
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */
class File extends DAV\Node implements DAV\IFile {

	/**
	 * The file from attach table.
	 *
	 * @var array $data
	 *  * data
	 *  * flags
	 *  * filename (string)
	 *  * filetype (string)
	 */
	private $data;
	/**
	 * @see \\Sabre\\DAV\\Auth\\Backend\\BackendInterface
	 * @var \\Zotlabs\\Storage\\BasicAuth $auth
	 */
	private $auth;
	/**
	 * @var string $name
	 */
	private $name;

	/**
	 * Sets up the node, expects a full path name.
	 *
	 * @param string $name
	 * @param array $data from attach table
	 * @param &$auth
	 */
	public function __construct($name, $data, &$auth) {
		$this->name = $name;
		$this->data = $data;
		$this->auth = $auth;

		logger(print_r($this->data, true), LOGGER_DATA);
	}

	/**
	 * @brief Returns the name of the file.
	 *
	 * @return string
	 */
	public function getName() {
		//logger(basename($this->name), LOGGER_DATA);
		return basename($this->name);
	}

	/**
	 * @brief Renames the file.
	 *
	 * @throw "\Sabre\DAV\Exception\Forbidden"
	 * @param string $newName The new name of the file.
	 * @return void
	 */
	public function setName($newName) {
		logger('old name ' . basename($this->name) . ' -> ' . $newName, LOGGER_DATA);

		if ((! $newName) || (! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage'))) {
			logger('permission denied '. $newName);
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		$newName = str_replace('/', '%2F', $newName);

		$r = q("UPDATE attach SET filename = '%s' WHERE hash = '%s' AND id = %d",
			dbesc($newName),
			dbesc($this->data['hash']),
			intval($this->data['id'])
		);

		if($this->data->is_photo) {
			$r = q("update photo set filename = '%s' where resource_id = '%s' and uid = %d",
				dbesc($newName),
				dbesc($this->data['hash']),
				intval($this->auth->owner_id)
			);
		}
		$ch = channelx_by_n($this->auth->owner_id);
		if($ch) {
			$sync = attach_export_data($ch,$this->data['hash']);
			if($sync)
				build_sync_packet($ch['channel_id'],array('file' => array($sync)));
		}
	}

	/**
	 * @brief Updates the data of the file.
	 *
	 * @param resource $data
	 * @return void
	 */
	public function put($data) {
		logger('put file: ' . basename($this->name), LOGGER_DEBUG);
		$size = 0;

		// @todo only 3 values are needed
		$c = q("SELECT * FROM channel WHERE channel_id = %d AND channel_removed = 0 LIMIT 1",
			intval($this->auth->owner_id)
		);

		$is_photo = false;
		$album = '';

		$r = q("SELECT flags, folder, os_storage, filename, is_photo FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);
		if ($r) {
			if (intval($r[0]['os_storage'])) {
				$d = q("select folder, content from attach where hash = '%s' and uid = %d limit 1",
					dbesc($this->data['hash']),
					intval($c[0]['channel_id'])
				);
				if($d) {
					if($d[0]['folder']) {
						$f1 = q("select * from attach where is_dir = 1 and hash = '%s' and uid = %d limit 1",
							dbesc($d[0]['folder']),
							intval($c[0]['channel_id'])
						);
						if($f1) {
							$album = $f1[0]['filename'];
							$direct = $f1[0];
						}
					}
					$fname = dbunescbin($d[0]['content']);
					if(strpos($fname,'store') === false)
						$f = 'store/' . $this->auth->owner_nick . '/' . $fname ;
					else
						$f = $fname;

					// @todo check return value and set $size directly
					@file_put_contents($f, $data);
					$size = @filesize($f);
					logger('filename: ' . $f . ' size: ' . $size, LOGGER_DEBUG);
				}
				$gis = @getimagesize($f);
				logger('getimagesize: ' . print_r($gis,true), LOGGER_DATA);
				if(($gis) && ($gis[2] === IMAGETYPE_GIF || $gis[2] === IMAGETYPE_JPEG || $gis[2] === IMAGETYPE_PNG)) {
					$is_photo = 1;
				}
			}
			else {
				// this shouldn't happen any more
				$r = q("UPDATE attach SET content = '%s' WHERE hash = '%s' AND uid = %d",
					dbescbin(stream_get_contents($data)),
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				$r = q("SELECT length(content) AS fsize FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				if ($r) {
					$size = $r[0]['fsize'];
				}
			}
		}

		// returns now()
		$edited = datetime_convert();

		$d = q("UPDATE attach SET filesize = '%s', is_photo = %d, edited = '%s' WHERE hash = '%s' AND uid = %d",
			dbesc($size),
			intval($is_photo),
			dbesc($edited),
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);

		if($is_photo) {
			require_once('include/photos.php');
			$args = array( 'resource_id' => $this->data['hash'], 'album' => $album, 'os_path' => $f, 'filename' => $r[0]['filename'], 'getimagesize' => $gis, 'directory' => $direct );
			$p = photo_upload($c[0],\App::get_observer(),$args);
		}

		// update the folder's lastmodified timestamp
		$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d",
			dbesc($edited),
			dbesc($r[0]['folder']),
			intval($c[0]['channel_id'])
		);

		// @todo do we really want to remove the whole file if an update fails
		// because of maxfilesize or quota?
		// There is an Exception "InsufficientStorage" or "PaymentRequired" for
		// our service class from SabreDAV we could use.

		$maxfilesize = get_config('system', 'maxfilesize');
		if (($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'], $this->data['hash']);
			return;
		}

		$limit = engr_units_to_bytes(service_class_fetch($c[0]['channel_id'], 'attach_upload_limit'));
		if ($limit !== false) {
			$x = q("select sum(filesize) as total from attach where aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if (($x) && ($x[0]['total'] + $size > $limit)) {
				logger('service class limit exceeded for ' . $c[0]['channel_name'] . ' total usage is ' . $x[0]['total'] . ' limit is ' . userReadableSize($limit));
				attach_delete($c[0]['channel_id'], $this->data['hash']);
				return;
			}
		}

		$sync = attach_export_data($c[0],$this->data['hash']);

		if($sync)
			build_sync_packet($c[0]['channel_id'],array('file' => array($sync)));

	}

	/**
	 * @brief Returns the raw data.
	 *
	 * @return string
	 */
	public function get() {
		logger('get file ' . basename($this->name), LOGGER_DEBUG);
		logger('os_path: ' . $this->os_path, LOGGER_DATA);

		$r = q("SELECT content, flags, os_storage, filename, filetype FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);
		if ($r) {
			// @todo this should be a global definition
			$unsafe_types = array('text/html', 'text/css', 'application/javascript');

			if (in_array($r[0]['filetype'], $unsafe_types)) {
				header('Content-disposition: attachment; filename="' . $r[0]['filename'] . '"');
				header('Content-type: text/plain');
			}

			if (intval($r[0]['os_storage'])) {
				$x = dbunescbin($r[0]['content']);
				if(strpos($x,'store') === false)
					$f = 'store/' . $this->auth->owner_nick . '/' . (($this->os_path) ? $this->os_path . '/' : '') . $x;
				else
					$f = $x;
				return fopen($f, 'rb');
			}
			return dbunescbin($r[0]['content']);
		}
	}

	/**
	 * @brief Returns the ETag for a file.
	 *
	 * An ETag is a unique identifier representing the current version of the file.
	 * If the file changes, the ETag MUST change.
	 * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined.
	 *
	 * @return null|string
	 */
	public function getETag() {
		$ret = null;
		if ($this->data['hash']) {
			$ret = '"' . $this->data['hash'] . '"';
		}
		return $ret;
	}

	/**
	 * @brief Returns the mime-type for a file.
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {
		// @todo this should be a global definition.
		$unsafe_types = array('text/html', 'text/css', 'application/javascript');
		if (in_array($this->data['filetype'], $unsafe_types)) {
			return 'text/plain';
		}
		return $this->data['filetype'];
	}

	/**
	 * @brief Returns the size of the node, in bytes.
	 *
	 * @return int
	 *  filesize in bytes
	 */
	public function getSize() {
		return $this->data['filesize'];
	}

	/**
	 * @brief Returns the last modification time for the file, as a unix
	 *        timestamp.
	 *
	 * @return int last modification time in UNIX timestamp
	 */
	public function getLastModified() {
		return datetime_convert('UTC', 'UTC', $this->data['edited'], 'U');
	}

	/**
	 * @brief Delete the file.
	 *
	 * This method checks the permissions and then calls attach_delete() function
	 * to actually remove the file.
	 *
	 * @throw "\Sabre\DAV\Exception\Forbidden"
	 */
	public function delete() {
		logger('delete file ' . basename($this->name), LOGGER_DEBUG);

		if((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if($this->auth->owner_id !== $this->auth->channel_id) {
			if (($this->auth->observer !== $this->data['creator']) || intval($this->data['is_dir'])) {
				throw new DAV\Exception\Forbidden('Permission denied.');
			}
		}

		if(get_pconfig($this->auth->owner_id,'system','os_delete_prohibit') && \App::$module == 'dav') {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		attach_delete($this->auth->owner_id, $this->data['hash']);

		$ch = channelx_by_n($this->auth->owner_id);
		if($ch) {
			$sync = attach_export_data($ch, $this->data['hash'], true);
			if($sync)
				build_sync_packet($ch['channel_id'], array('file' => array($sync)));
		}
	}
}
