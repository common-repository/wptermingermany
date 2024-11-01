<?php

	class wptg_event
	{
		
		/** @var wptg_PluginController */
		var $pc;
		
		/** @var wptg_db */
		var $db;
		
		/** @var array */
		var $data;
		
		public function __construct($post_id)
		{
			
			$this->pc = &$GLOBALS['wptg_pc'];
			$this->db = &$GLOBALS['wptg_db'];
			
			$this->data = $this->db->fetchAssocField("
				SELECT
					PM.`meta_key`, PM.`meta_value`
				FROM
					`".WPTG_TBL_POSTMETA."` AS PM	
				WHERE
					PM.`post_id` = '".wptg_q($post_id)."' AND
					(
						PM.`meta_key` = 'event_from' OR 
						PM.`meta_key` = 'event_to' OR
						PM.`meta_key` = 'event_fullday'
					)
			", "meta_key", "meta_value");
			
			$this->data['post'] = $this->db->fetchRow("
				SELECT
					P.*
				FROM
					`".WPTG_TBL_POSTS."` AS P
				WHERE
					P.`id` = '".wptg_q($post_id)."'
			");

			$this->data['id'] = $post_id;
			
		} // public function __construct($post_id)

		public function __get($key)
		{
			
			return $this->data[$key];
			
		} // public function __get($key)
		
		public static function find($arFilter = array())
		{
			
			$strQueryWHERE = "";
			$strQueryHAVING = "";
			
			if (wptg_isSizedInt($arFilter['date_from']))
			{
								
				$strQueryHAVING .= " AND 
				(
					(`event_from` >= '".wptg_q($arFilter['date_from'])."') OR 
					(`event_from` < '".wptg_q($arFilter['date_from'])."' AND `event_to` >= '".wptg_q($arFilter['date_from'])."') 
				) ";
				
			}
			
			if (wptg_isSizedInt($arFilter['date_to']))
			{
				
				$strQueryHAVING .= " AND
				(
					(`event_to` <= '".wptg_q($arFilter['date_to'])."') OR
					(`event_to` > '".wptg_q($arFilter['date_to'])."' AND `event_from` <= '".wptg_q($arFilter['date_to'])."')		
				) ";		
				
			}
			
			if (wptg_isSizedArray($arFilter['cat']) && !in_array('0', $arFilter['cat']))
			{
				
				foreach ($arFilter['cat'] as $cat_id)
				{
								
					if (wptg_isSizedInt($cat_id))
					{
					
						$strQueryHAVING .= " AND FIND_IN_SET('".wptg_q($cat_id)."', cat) ";
						
					}
					
				}
				
			}
						
			$strQuery = "
				SELECT
					DISTINCT P.`ID` AS `id`,
					(SELECT PM.`meta_value` FROM `".WPTG_TBL_POSTMETA."` AS PM WHERE PM.`meta_key` = 'event_from' AND PM.`post_id` = P.`ID` LIMIT 1) AS `event_from`,
					(SELECT PM.`meta_value` FROM `".WPTG_TBL_POSTMETA."` AS PM WHERE PM.`meta_key` = 'event_to' AND PM.`post_id` = P.`ID` LIMIT 1) AS `event_to`,
					(SELECT GROUP_CONCAT(`term_taxonomy_id`) AS `cat` FROM `".WPTG_TBL_TERMRELATIONSHIP."` AS TR WHERE TR.`object_id` = P.`ID`) AS `cat` 
				FROM
					`".WPTG_TBL_POSTS."` AS P
				WHERE
					P.`post_type` = 'wptg_events' AND
					P.`post_status` = 'publish' AND					
					P.`post_parent` = '0' AND
					1 
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."	
				ORDER BY
					`event_from` ASC			
			";
	
			$arPostID = $GLOBALS['wptg_db']->fetchAssocField($strQuery, "id", "id");
			$arReturn = array();
			
			foreach ($arPostID as $post_id)
			{
				 
				$arReturn[] = new wptg_event($post_id);
				
			}
			
			return $arReturn;
			
		} // public static function find($arFilter = array())
						
	} // class wptg_event()

?>