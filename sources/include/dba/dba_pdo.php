<?php /** @file */

require_once('include/dba/dba_driver.php');

class dba_pdo extends dba_driver {


	public $driver_dbtype = null;

	function connect($server,$port,$user,$pass,$db) {
		
		$this->driver_dbtype = 'mysql'; // (($dbtype == DBTYPE_POSTGRES) ? 'postgres' : 'mysql');
		$dns = $this->driver_dbtype
		. ':host=' . $server . (is_null($port) ? '' : ';port=' . $port)
		. ';dbname=' . $db;


		try {
			$this->db = new PDO($dns,$user,$pass);
			$this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			if(file_exists('dbfail.out')) {
				file_put_contents('dbfail.out', datetime_convert() . "\nConnect: " . $e->getMessage() . "\n", FILE_APPEND);
			}

			return false;
		}

		$this->connected = true;
		return true;

	}

	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';
		$select = ((stripos($sql,'select') === 0) ? true : false);

		try {
			$result = $this->db->query($sql);
		}
		catch(PDOException $e) {
	
			$this->error = $e->getMessage();
			if($this->error) {
				db_logger('dba_mysqli: ERROR: ' . printable($sql) . "\n" . $this->error, LOGGER_NORMAL, LOG_ERR);
				if(file_exists('dbfail.out')) {
					file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . "\n" . $this->error . "\n", FILE_APPEND);
				}
			}
		}

		if(!($select)) {
			if($this->debug) {
				db_logger('dba_mysqli: DEBUG: ' . printable($sql) . ' returns ' . (($result) ? 'true' : 'false'), LOGGER_NORMAL,(($result) ? LOG_INFO : LOG_ERR));
			}
			return $result;
		}

		if($this->debug) {
			db_logger('dba_mysqli: DEBUG: ' . printable($sql) . ' returned ' . count($result) . ' results.', LOGGER_NORMAL, LOG_INFO); 
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
		return $r;
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
	
	function getdriver() {
		return 'pdo';
	}

}