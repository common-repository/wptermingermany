<?php

	/**
	 * Klasse die die vom Shop verwendeten Datenbankfunktionen kapselt
	 */
	class wptg_db
	{
		
		/**
		 * Fehlerbehandlung
		 */
		function handleError()
		{
			
			global $wpdb;
			
			die(wptg_debug("Query: ".$wpdb->last_query."\nFehler: ".$wpdb->last_error."\nBacktrace:\n".print_r(debug_backtrace(), 1)));
			
		} // function handleError()
		
		/**
		 * Gibt eine einzelne Zelle aus der Datenbank zur�ck
		 */
		function fetchOne($strQuery)
		{
			
			global $wpdb;

			if ($wpdb->query($strQuery) === false)
			{
				
				$this->handleError();
				
			}
			else
			{
			
				$result = $wpdb->get_var($strQuery);
 
				return $result;
				
			} 
			
		} // function fetchOne($strQuery)
		
		/**
		 * Gibt eine ganze Zeile als Ergebnis aus der Datenbank zur�ck
		 */
		function fetchRow($strQuery)
		{
			
			global $wpdb;
			 
			if ($wpdb->query($strQuery) === false)
			{
			
				$this->handleError();
			
			}
			else
			{
					
				$result = $wpdb->get_row($strQuery, ARRAY_A);
			 
				return $result;
			
			} 
			
		} // function fetchRow($strQuery)
		
		/**
		 * Gibt mehrere Zeilen aus einer Tabelle als Array von Arrays zur�ck
		 * Ist der Parameter $key ungleich zu false, so ist der Schl�ssel des Arrays die in $key �bergebene Spalte 
		 */
		function fetchAssoc($strQuery, $key = false)
		{
			
			global $wpdb;
			
			if ($wpdb->query($strQuery) === false)
			{
					
				$this->handleError();
					
			}
			else
			{
			
				$arReturn1 = $wpdb->get_results($strQuery, ARRAY_A);
				
				if ($key != false)
				{
					
					$arReturn = array();
					
					foreach ($arReturn1 as $k => $v)
					{
						
						$arReturn[$v[$key]] = $v;
						
					}
					
					return $arReturn;
					
				}
				else
				{
					
					return $arReturn1;
					
				} 
				
			}
			
		} // function fetchAssoc($strQuery)
		
		/**
		 * Liefert eine Spalte eines Querys als Array zur�ck
		 * Der Parameter strKeyField ist die Spalte f�r den Schl�ssel des Arrays (Sollte eindeutig sein)
		 * Der Parameter strValueField ist die Spalte f�r den Wert des Arrays 
		 */
		function fetchAssocField($strQuery, $strKeyField = false, $strValueField = false)
		{
		
			global $wpdb;
			
			if ($wpdb->query($strQuery) === false)
			{
					
				$this->handleError();
					
			}
			else
			{
			
				$db_rows = $wpdb->get_results($strQuery, ARRAY_A);
				$arReturn = array();			
				
				foreach ($db_rows as $row)
				{
					 
					if ($strKeyField != false && $strValueField != false)
						$arReturn[$row[$strKeyField]] = $row[$strValueField];
					else
						$arReturn[] = reset($row);
					
				} 
				
				return $arReturn;
				
			}
			
		} // function fetchAssocField($strQuery, $strField)
		
		/**
		 * Importiert die Daten aus $data als neue Zeile in die Tabelle $table
		 * $data muss dabei aus einem Schl�ssel/Wert Array bestehen
		 * Der R�ckgabewert ist die ID des eingef�gten Datensatzes
		 */
		function ImportQuery($table, $data, $checkCols = false)
		{
			
			global $wpdb;
						
			/**
			 * Wenn diese Option aktiv ist, so werden Spalten nur importiert
			 * wenn sie auch in der Zieltabelle existieren.
			 */
			if ($checkCols === true)
			{
				
				$this->fetchAssoc("SHOW COLUMNS FROM `".wptg_q($table)."` ");
				
				$arCols = array();				
				foreach ($arFields as $f) { $arCols[] = $f['Field']; }				
				foreach ($data as $k => $v) { if (!in_array($k, $arCols)) { unset($data[$k]); } }
				
			}
			
			if (!wptg_isSizedArray($data)) return false;
			
			// Query zusammenbauen
			$strQuery = "INSERT INTO `".wptg_q($table)."` SET ";
			
			foreach ($data as $k => $v)
			{
				
				if ($v != "NOW()" && $v != "NULL" && !is_array($v))
					$v = "'".$v."'";
				else if (is_array($v))
					$v = $v[0];
					
				$strQuery .= "`".$k."` = ".$v.", ";
				
			}
			
			$strQuery = substr($strQuery, 0, -2);
			
			$res = $wpdb->query($strQuery);

			if ($res === false)
			{
				 
				$this->handleError();
				
			}			
			else
			{
						
				return $wpdb->insert_id;
				
			}
			
		} // function ImportQuery($table, $data)
				
		/**
		 * Aktualisiert Zeilen in der Datenbank anhand des $where Selectse
		 */
		function UpdateQuery($table, $data, $where)
		{
			
			global $wpdb;
			
			// Query aufbauen, da wir den kompletten QueryWHERE String als String �bergeben
			$strQuery = "UPDATE `".wptg_q($table)."` SET ";
			
			foreach ($data as $k => $v)
			{
				
				if ($v != "NOW()" && $v != "NULL" && !is_array($v))
					$v = "'".$v."'";
				else if (is_array($v))
					$v = $v[0];
					
				$strQuery .= "`".$k."` = ".$v.", ";
				
			}
		 
			$strQuery = substr($strQuery, 0, -2)." WHERE ".$where;
			
			$res = $wpdb->query($strQuery);

			if ($res === false)
			{
				 
				$this->handleError();
				
			}
			
			return $res;
			
		} // function UpdateQuery($table, $data, $where)
				
		/**
		 * Gibt den n�chsten AUTO_INCREMENT Wert einer Tabelle zur�ck
		 */
		function getNextAutoincrementValue($table)
		{
			
			$result = $this->fetchRow("SHOW TABLE STATUS LIKE '".$table."'");
			
			return $result['Auto_increment'];
			
		} // function getNextAutoincrementValue($table)
		
		/**
		 * F�hrt einen Query aus. Z.b. f�r Delete Querys
		 */
		function Query($strQuery)
		{
			
			global $wpdb;
			
			$res = $wpdb->query($strQuery);
			
			if ($res === false)
			{
				
				$this->handleError();
				
			}
			 			
		} // function Query($strQuery)
		
	} // class wptg_db

?>