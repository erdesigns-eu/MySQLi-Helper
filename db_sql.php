<?php

/**********************************************************************************/
/*																				  */
/*				dbSQL.php [ SQL Select, Insert, Update, Delete - Class ]		  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 18/01/2020 20:30								  		  */
/*				Version	: 1.2												  	  */
/*																				  */
/**********************************************************************************/

class dbSQL {
	private $conn;
	private $dbname;

	// dbSQL class constructor
	public function __construct ($server, $username, $password, $dbname) {
		$this->conn = new mysqli($server, $username, $password, $dbname);
		$this->conn->set_charset('utf8');
		$this->dbname = $dbname;
	}

	public function __destruct () {
		$this->conn->close();
	}

	// Clean string ready for insert/update in sql query
	private function clean_string ($str) {
		return $this->conn->real_escape_string($str);
	}

	// Check if data is serialized
	private function is_serialized ($data, $strict = false) {
	    if (!is_string($data)) {
	        return false;
	    }
	    $data = trim($data);
	    if ('N;' == $data) {
	        return true;
	    }
	    if (mb_strlen($data) < 4) {
	        return false;
	    }
	    if (':' !== $data[1]) {
	        return false;
	    }
	    if ($strict) {
	        $lastc = mb_substr($data, -1);
	        if (';' !== $lastc && '}' !== $lastc) {
	            return false;
	        }
	    } else {
	        $semicolon = strpos($data, ';');
	        $brace     = strpos($data, '}');
	        if (false === $semicolon && false === $brace) {
	            return false;
	        }
	        if (false !== $semicolon && $semicolon < 3) {
	            return false;
	        }
	        if (false !== $brace && $brace < 4) {
	            return false;
	        }
	    }
	    $token = $data[0];
	    switch ($token) {
	        case 's':
	            if ($strict) {
	                if ('"' !== mb_substr($data, -2, 1)) {
	                    return false;
	                }
	            } elseif (false === strpos($data, '"')) {
	                return false;
	            }
	        case 'a':
	        case 'O':
	            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
	        case 'b':
	        case 'i':
	        case 'd':
	            $end = $strict ? '$' : '';
	            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
	    }
	    return false;
	}

	// Check if data is JSON
	private function is_json ($str) {
		if (is_string($str)) {
		    json_decode($str);
		    return (json_last_error() == JSON_ERROR_NONE);
		} else {
		    return false;
		}
	}

	// Convert assoc array to string for insert sql query
	private function insert_data_str ($data) {
		if (!is_array($data) || empty($data)) {
			return false;
		}
		$fields = "";
		$values = "";
		foreach ($data as $key => $value) {
			if (!is_string($key) && !is_integer($key)) {
				continue;
			}
			$_key    = $this->clean_string($key);
			$fields .= empty($fields) ? "`{$_key}`" : ", `{$_key}`";
			if (!is_string($value) && !is_integer($value) && !is_bool($value)) {
				$_value  = $this->clean_string(serialize($value));
				$values .= empty($values) ? "'{$_value}'" : ", '{$_value}'";
			} else {
				$_value  = $this->clean_string($value);
				$values .= empty($values) ? "'{$_value}'" : ", '{$_value}'";
			}
		}
		return "({$fields}) VALUES ({$values})";
	}

	// Convert assoc array to key = val string for update sql query
	private function update_data_str ($data) {
		if (!is_array($data) || empty($data)) {
			return false;
		}
		$str = "";
		foreach ($data as $key => $value) {
			if (!is_string($key) && !is_integer($key)) {
				continue;
			}
			$_key = $this->clean_string($key);
			if (!is_string($value) && !is_integer($value) && !is_bool($value)) {
				$_value = $this->clean_string(serialize($value));
				$str   .= empty($str) ? "`{$_key}` = '{$_value}'" : ", `{$_key}` = '{$_value}'";
			} else {
				$_value = $this->clean_string($value);
				$str   .= empty($str) ? "`{$_key}` = '{$_value}'" : ", `{$_key}` = '{$_value}'";
			}
		}
		return $str;
	}

	// Convert assoc array to key = val string for update sql query conditions
	private function update_condition_str ($data) {
		if (!is_array($data) || empty($data)) {
			return false;
		}
		$str = "";
		foreach ($data as $key => $value) {
			if (!is_string($key) && !is_integer($key)) {
				continue;
			}
			$_key = $this->clean_string($key);
			if (!is_string($value) && !is_integer($value) && !is_bool($value)) {
				$_value = $this->clean_string(serialize($value));
				$str   .= empty($str) ? "`{$_key}` = '{$_value}'" : "AND `{$_key}` = '{$_value}'";
			} else {
				$_value = $this->clean_string($value);
				$str   .= empty($str) ? "`{$_key}` = '{$_value}'" : "AND `{$_key}` = '{$_value}'";
			}
		}
		return $str;
	}

	// Convert array to fields string
	private function fields_str ($fields) {
		if (!is_array($fields) || empty($fields)) {
			return false;
		}
		$str = "";
		foreach ($fields as $field) {
			if (!is_string($field) && !is_integer($field)) {
				continue;
			}
			if ($field === '*') {
				$str .= empty($str) ? "*" : ", *";
			} else {
				$_field = $this->clean_string($field);
				$str .= empty($str) ? "`{$_field}`" : ", `{$_field}`";
			}
		}
		return $str;
	}

	// Create SQL query
	private function create_query ($method, $table, $fields = [], $data = [], $conditions = []) {
		switch ($method) {
			case 'INSERT'	: return sprintf("INSERT INTO `{$table}` %s", $this->insert_data_str($data));
			case 'UPDATE'	: return sprintf("UPDATE `{$table}` SET %s WHERE %s", $this->update_data_str($data), $this->update_condition_str($conditions));
			case 'DELETE'	: return sprintf("DELETE FROM `{$table}` WHERE %s", $this->update_condition_str($conditions));
			case 'SELECT'	: return sprintf("SELECT %s FROM `{$table}`", $this->fields_str($fields));
			case 'DISTINCT'	: return sprintf("SELECT DISTINCT %s FROM `{$table}`", $this->fields_str($fields));
		}
	}

	// Insert query
	function sql_insert ($table, $data) {
		return $this->conn->query($this->create_query('INSERT', $table, [], $data));
	}

	// Update query
	function sql_update ($table, $data, $conditions = []) {
		return $this->conn->query($this->create_query('UPDATE', $table, [], $data, $conditions));
	}

	// Delete query
	function sql_delete ($table, $conditions) {
		return $this->conn->query($this->create_query('DELETE', $table, [], [], $conditions));
	}

	// Search query
	function sql_search ($table, $data) {
		$res = explode('|', $data);
		if (count($res) > 1) {
			$fields = explode(',', $res[1]);
			$search = $this->clean_string($res[0]);
		} else {
			$fields = $this->sql_select_array_query(sprintf("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = '%s'", $table));
			$search = $this->clean_string($res[0]);
		}
		$sql = sprintf("SELECT * FROM `%s` WHERE ", $table);
		$end = end(array_keys($fields));
		foreach ($fields as $key => $field) {
			$field = is_array($field) ? $this->clean_string($field['COLUMN_NAME']) : $this->clean_string($field);
			$sql .= $key === $end ? sprintf("`%s` LIKE '%s'", $field, "%{$search}%") : sprintf("`%s` LIKE '%s' OR ", $field, "%{$search}%");
		}
		return $this->sql_select_array_query($sql);		
	}

	// Select query, return array
	function sql_select_array ($table, $fields, $distinct = false) {
		$method = $distinct === true ? 'DISTINCT' : 'SELECT';
		$result = $this->conn->query($this->create_query($method, $table, $fields));
		if ($result == false || $result->num_rows == 0) {
	    	return [];
	    } else {
			$data = [];
			while($row = $result->fetch_assoc()) {
				foreach ($row as $key => $value) {
					if ($this->is_serialized($value)) {
						$row[$key] = unserialize($value);
					}
					if ($this->is_json($value)) {
						$row[$key] = json_decode($value, true);
					}
				}
				array_push($data, $row);
			}
			return $data;
	    }
	}

	// Select query - custom query, return array
	function sql_select_array_query ($query) {
		$result = $this->conn->query($query);
		if ($result == false || $result->num_rows == 0) {
	    	return [];
	    } else {
			$data = [];
			while($row = $result->fetch_assoc()) {
				foreach ($row as $key => $value) {
					if ($this->is_serialized($value)) {
						$row[$key] = unserialize($value);
					}
					if ($this->is_json($value)) {
						$row[$key] = json_decode($value, true);
					}
				}
				array_push($data, $row);
			}
			return $data;
	    }
	}

	// Backup SQL DB - get text output
	function sql_backup ($tables = '*') {
		if ($tables === '*') {
		    $tables = [];
		    $result = $this->conn->query("SHOW TABLES");
			while ($row = $result->fetch_assoc()) {
				array_push($tables, $row);
			}
		} else {
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}
		$datestr = date("D M d, Y G:i");
		$output  = "-- ERDesigns SQL Dump\r\n-- version 1.0\r\n-- {$_SERVER['HTTP_HOST']}\r\n--\r\n-- Date: {$datestr}\r\n\r\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET AUTOCOMMIT = 0;\r\nSTART TRANSACTION;\r\nSET time_zone = \"+00:00\";\r\n\r\n--\r\n-- Database: '{$dbname}'\r\n--\r\n\r\n-- --------------------------------------------------------\r\n\r\n";
		foreach ($tables as $table) {
		    $result 	 = $this->conn->query("SELECT * FROM '{$table}'");
		    $numcolumns  = $result->field_count;
		    $output 	.= "--\r\n-- Structure for table '$table'\r\n--\r\n\r\n";
		    $output 	.= "DROP TABLE $table;";
		    $result2 	 = $this->conn->query("SHOW CREATE TABLE '{$table}'");
		    $row2 		 = $result2->fetch_row();
		    $output 	.= "\r\n\r\n{$row2[1]};\r\n\r\n";
		    for ($i = 0; $i < $numcolumns; $i++) {
		      while ($row = $result->fetch_row()) {
		        $output .= "INSERT INTO '{$table}' VALUES (";
		        for ($j = 0; $j < $numcolumns; $j++) {
		          $row[$j] = addslashes($row[$j]);
		          if (isset($row[$j])) { 
		          	$output .= '\''.$row[$j].'\'' ; 
		          } else { 
		          	$output .= '\'\''; 
		          }
		          if ($j < ($numcolumns-1)) { 
		          	$output.= ','; 
		          }
		        }
		        $output .= ");\r\n";
		      }
		    }
		    $output .= "\r\n";
		}
		return $output; 
	}

	// Download SQL Backup as file
	function download_sql_backup ($filename, $tables = '*') {
		$file = backupSQL($tables);
		ob_get_clean();
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-Disposition: attachment; filename=\"{$filename}.sql\"");
		header('Content-Length: '.mb_strlen($file, '8bit'));
		echo $file; 
	}

	// Drop tables in database
	function drop_tables () {
		$db 	= $this->dbname;
		$result = $this->conn->query("SELECT CONCAT(\"DROP TABLE `\",table_name,\"`;\") as 'drop' FROM information_schema.tables WHERE table_schema = '{$db}'");
		if ($result == false || $result->num_rows == 0) {
			return false;
		}
		$sql = "";
		while($row = $result->fetch_assoc()) {
			$sql .= $row['drop'];
		}
		return $this->conn->query($sql);
	}

	// Get last error
	function sql_last_error () {
		return $this->conn->error;
	}

}

?>