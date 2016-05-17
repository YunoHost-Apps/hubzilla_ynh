<?php /** @file */

require_once('include/dba/dba_driver.php');

class dba_mysqli extends dba_driver {

	function connect($server,$port,$user,$pass,$db) {
		if($port)
			$this->db = new mysqli($server,$user,$pass,$db, $port);
		else
			$this->db = new mysqli($server,$user,$pass,$db);

		if($this->db->connect_error) {
			$this->connected = false;
			$this->error = $this->db->connect_error;

			if(file_exists('dbfail.out')) {
				file_put_contents('dbfail.out', datetime_convert() . "\nConnect: " . $this->error . "\n", FILE_APPEND);
			}

			return false;
		}
		else {
			$this->connected = true;
			return true;
		}
	}

	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';
		$result = $this->db->query($sql);

		if($this->db->errno)
			$this->error = $this->db->error;


		if($this->error) {
			db_logger('dba_mysqli: ERROR: ' . printable($sql) . "\n" . $this->error, LOGGER_NORMAL, LOG_ERR);
			if(file_exists('dbfail.out')) {
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . "\n" . $this->error . "\n", FILE_APPEND);
			}
		}

		if(($result === true) || ($result === false)) {
			if($this->debug) {
				db_logger('dba_mysqli: DEBUG: ' . printable($sql) . ' returns ' . (($result) ? 'true' : 'false'), LOGGER_NORMAL,(($result) ? LOG_INFO : LOG_ERR));
			}
			return $result;
		}

		if($this->debug) {
			db_logger('dba_mysqli: DEBUG: ' . printable($sql) . ' returned ' . $result->num_rows . ' results.', LOGGER_NORMAL, LOG_INFO); 
		}

		$r = array();
		if($result->num_rows) {
			while($x = $result->fetch_array(MYSQLI_ASSOC))
				$r[] = $x;
			$result->free_result();
			if($this->debug) {
				db_logger('dba_mysqli: ' . printable(print_r($r,true)), LOGGER_NORMAL, LOG_INFO);
			}
		}
		return $r;
	}

	function escape($str) {
		if($this->db && $this->connected) {
			return @$this->db->real_escape_string($str);
		}
	}

	function close() {
		if($this->db)
			$this->db->close();
		$this->connected = false;
	}
	
	function getdriver() {
		return 'mysqli';
	}

}