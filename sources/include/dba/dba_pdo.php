<?php /** @file */

require_once('include/dba/dba_driver.php');

class dba_pdo extends dba_driver {


	public $driver_dbtype = null;

	function connect($server,$scheme,$port,$user,$pass,$db) {
		
		$this->driver_dbtype = $scheme;

		if(strpbrk($server,':;')) {
			$dsn = $server;
		}
		else {
			$dsn = $this->driver_dbtype . ':host=' . $server . (intval($port) ? '' : ';port=' . $port);
		}
		
		$dsn .= ';dbname=' . $db;

		try {
			$this->db = new PDO($dsn,$user,$pass);
			$this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			if(file_exists('dbfail.out')) {
				file_put_contents('dbfail.out', datetime_convert() . "\nConnect: " . $e->getMessage() . "\n", FILE_APPEND);
			}

			return false;
		}

		if($this->driver_dbtype === 'pgsql')
			$this->q("SET standard_conforming_strings = 'off'; SET backslash_quote = 'on';");

		$this->connected = true;
		return true;

	}

	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		if($this->driver_dbtype === 'pgsql') {
			if(substr(rtrim($sql),-1,1) !== ';') {
				$sql .= ';';
			}
		}

		$this->error = '';
		$select = ((stripos($sql,'select') === 0) ? true : false);

		try {
			$result = $this->db->query($sql, PDO::FETCH_ASSOC);
		}
		catch(PDOException $e) {
	
			$this->error = $e->getMessage();
			if($this->error) {
				db_logger('dba_pdo: ERROR: ' . printable($sql) . "\n" . $this->error, LOGGER_NORMAL, LOG_ERR);
				if(file_exists('dbfail.out')) {
					file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . "\n" . $this->error . "\n", FILE_APPEND);
				}
			}
		}

		if(!($select)) {
			if($this->debug) {
				db_logger('dba_pdo: DEBUG: ' . printable($sql) . ' returns ' . (($result) ? 'true' : 'false'), LOGGER_NORMAL,(($result) ? LOG_INFO : LOG_ERR));
			}
			return $result;
		}

		if($this->debug) {
			db_logger('dba_pdo: DEBUG: ' . printable($sql) . ' returned ' . count($result) . ' results.', LOGGER_NORMAL, LOG_INFO); 
		}

		$r = array();
		if($result) {
			foreach($result as $x) {
				$r[] = $x;
			}
			if($this->debug) {
				db_logger('dba_pdo: ' . printable(print_r($r,true)), LOGGER_NORMAL, LOG_INFO);
			}
		}
		return (($this->error) ? false : $r);
	}

	function escape($str) {
		if($this->db && $this->connected) {
			return substr(substr(@$this->db->quote($str),1),0,-1);
		}
	}

	function close() {
		if($this->db)
			$this->db = null;
		$this->connected = false;
	}
	
	function concat($fld,$sep) {
		if($this->driver_dbtype === 'pgsql') {
			return 'string_agg(' . $fld . ',\'' . $sep . '\')';
		}
		else {
			return 'GROUP_CONCAT(DISTINCT ' . $fld . ' SEPARATOR \'' . $sep . '\')';
		}
	}

	function quote_interval($txt) {
		if($this->driver_dbtype === 'pgsql') {
			return "'$txt'";
		}
		else {
			return $txt;
		}
	}

	// These two functions assume that postgres standard_conforming_strings is set to off;
	// which we perform during DB open.

	function escapebin($str) {
		if($this->driver_dbtype === 'pgsql') {
			return "\\\\x" . bin2hex($str);
		}
		else {
			return $this->escape($str);
		}
	}
	
	function unescapebin($str) {
		if($this->driver_dbtype === 'pgsql' && (! is_null($str))) {
			$x = '';
			while(! feof($str)) {
				$x .= fread($str,8192);
			}
			if(substr($x,0,2) === '\\x') {
				$x = hex2bin(substr($x,2));
			}
			return $x;

		}
		else {
			return $str;
		}
	}


	function getdriver() {
		return 'pdo';
	}

}