<?php
	
	/**
	 * Pr�ft ob eine Variable ein Array mit mindestens einem Element ist
	 * @author daschmi (http://daschmi.de)
	 * @return boolean 
	 */
	function wptg_isSizedArray($param) 
	{
		
		if (is_array($param) && sizeof($param) > 0)
		{
			
			return true;
			
		}
		else
		{
			
			return false;
			
		}
		
	} // function wptg_isSizedArray($param)

	/**
	 * Pr�ft ob eine Variable ein String ist und die aus mindestens einem Zeichen besteht.
	 * @param unknown_type $value
	 * @return boolean
	 */
	function wptg_isSizedString($value)
	{
		
		if (isset($value) && is_string($value) && strlen($value) > 0)
		{
			
			return true;
			
		}
		else
		{
			
			return false;
			
		}
		
	} // function wptg_isSizedString($value)
	
	/**
	 * Pr�ft ob eine Variable gesetzt ist und vom Typ Integer
	 * @param unknown_type $value
	 */
	function wptg_isSizedInt($value)
	{
		
		if (isset($value) && is_numeric($value) && $value > 0) return true;
		else return false;
		
	} // function wptg_isSizedInt($value)
	
	function wptg_readICS($strContent) 
	{
		 	
		$ICSData = explode("BEGIN:", $strContent);
		$ICSDateMeta = array();
		$ICSReturn = array();
		
		foreach($ICSData as $k => $v) 
		{
			
			$ICSDateMeta[$k] = explode("\n", $v);
			
		}
	
		foreach($ICSDateMeta as $k => $v) 
		{
			
			foreach($v as $k2 => $v2) 
			{
				
				if ($v2 != "")
				{
					
					if ($k != 0 && $k2 == 0) 
					{
						
						$ICSReturn[$k]["BEGIN"] = $v2;
					
					} 
					else 
					{
						
						$v2Array = explode(":", $v2, 2);
						
						$ICSReturn[$k][$v2Array[0]] = $v2Array[1];
						
					}
					
				}
				
			}
			
		}
	
		return $ICSReturn;
		
	}
	 
	function wptg_getEventCategorieArray($parent = 0, $pre = '')
	{
		
		$arCategories = get_categories(array(
			'taxonomy' => 'event_categorie',
			'hide_empty' => false,
			'parent' => $parent
		));
		
		$arReturn = array();
		
		foreach ($arCategories as $k => $v)
		{
			
			$arReturn[$v->cat_ID] = $pre.$v->name;
			
			$subCat = wptg_getEventCategorieArray($v->cat_ID, '-- ');
			
			foreach ($subCat as $k2 => $v2)
			{
				
				$arReturn[$k2] = $v2;
				
			}
			 									 
		}
		
		return $arReturn;
		
	} // function wptg_getEventCategorieArray($parent = 0)
	
	function wptg_toTime($value)
	{
		
		if (wptg_isSizedInt($value)) return $value;
		
		return strtotime($value);
		
	}
	
	/**
	 * Hilfsfunktion 
	 * @param unknown_type $value
	 * @return string
	 */
	function wptg_hspc($value)
	{
		
		return htmlspecialchars($value);
		
	} // function wptg_hspc($value)
	
	function wptg_dayStart($tDate)
	{

		return mktime(0, 0, 0, date('m', $tDate), date('d', $tDate), date('Y', $tDate), WPSG_TIME_DST);
		
	}
	
	function wptg_dayEnd($tDate)
	{
		
		if (date('H:i:s', $tDate) === '00:00:00')
		{

			return $tDate;
			
		}
		else
		{
		
			return mktime(23, 59, 59, date('m', $tDate), date('d', $tDate), date('Y', $tDate), WPSG_TIME_DST);
			
		}
		
	}
	
	/**
	 * Erweiterung der Gettext Funktion um flexible Parameter
	 * Aufruf in der Form: translate(__("Es wurden #1# H�user gefunden.", "wptg"));
	 *
	 * Zus�tzlich wird der String noch durch Htmlspecialchars gejagt
	 */
	function wptg_translate($string)
	{
	
		$arg = array();
			
		for($i = 1 ; $i < func_num_args(); $i++)
		{
		 
		$arg = func_get_arg($i);
		$string = preg_replace("/#".$i."#/", $arg, $string);
		}
	
		return $string;
	
	} // function wptg_translate($string)
	
	/**
	 * Debug Funktion
	 * @author Daschmi (http://daschmi.de)
	 * @param mixed $value
	 */
	function wptg_debug($value)
	{
		 
		if (is_array($value))
		{
				
			echo '<pre style="color:red;">';
			print_r($value);
			echo '</pre>';
				
		}
		else
		{
			echo '<pre style="color:red;">'.$value.'</pre>';
		}
		
	} // function wptg_debug($value)
	
	/**
	 * Escape Funktion f�r Datenbankanfragen
	 * @author Daschmi (http://daschmi.de)
	 * @param mixed $value
	 * @return Escapter String
	 */
	function wptg_q($value)
	{
			
		if (is_array($value))
		{
				
			foreach ($value as $k => $v)
			{
	
				$value[$k] = wptg_q($v);
	
			}
				
			return $value;
				
		}
		else
		{
	
			if (is_object($value))
			{
	
				wptg_debug(print_r(debug_backtrace(), 1));
				die($GLOBALS['wptg_pc']->throwErrorCode('100_3'));
	
			}
				
			return esc_sql($value);
				
		}
	
	} // function wptg_q($value)
 	
?>