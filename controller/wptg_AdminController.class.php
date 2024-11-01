<?php

	class wptg_AdminController 
	{
		
		/** @var wptg_PluginController */
		public $pc;
		
		/** @var wptg_db */
		public $db;
		
		public function __construct()
		{
				
			$this->pc = &$GLOBALS['wptg_pc'];
			$this->db = &$GLOBALS['wptg_db'];
				
		} // public function __construct()
				
		public function indexAction()
		{
			
			if (!isset($_REQUEST['a'])) $_REQUEST['a'] = 'general';

			$actionName = $_REQUEST['a'].'Action';
						
			$this->pc->view['a'] = $_REQUEST['a'];
			$this->pc->view['subContent'] = $this->$actionName();
			$this->pc->renderOut(WPTG_PATH_VIEW.'Admin/index.phtml');	
			
		} // public function indexAction()
		
		public function generalAction()
		{
			
			if (isset($_REQUEST['submit']))
			{
							
				$this->pc->createPage(__('Kalenderansicht', 'wptg'), 'wptg_datePage', $_REQUEST['wptg_datePage']);
			
				$this->pc->addBackendMessage(__('Einstellungen erfolgreich gespeichert.', 'wptg'));				
				$this->pc->redirect(WPTG_URL_WP.'wp-admin/admin.php?page=wptg-configuration');
			
			}
			
			$pages = get_pages();
				
			$this->pc->view['arPages'] = array(
					'-1' => __('Neu anlegen und zuordnen', 'wpsg')
			);
				
			foreach ($pages as $k => $v)
			{
			
				$this->pc->view['arPages'][$v->ID] = $v->post_title.' (ID:'.$v->ID.')';
			
			}
			
			return $this->pc->render(WPTG_PATH_VIEW.'Admin/general.phtml');
			
		} // public function generalAction()
		
		public function calendarviewAction()
		{

			return $this->pc->render(WPTG_PATH_VIEW.'Admin/calendarview.phtml');
			
		} // public function calendarviewAction()
		
		public function importAction()
		{
			
			if (isset($_REQUEST['submit']))
			{
			
				if (isset($_FILES['file']) && file_exists($_FILES['file']['tmp_name']))
				{
					
					$ending = strtolower(preg_replace('/(.*)\./', '', $_FILES['file']['name']));				
					
					if ($ending != "ics")
					{
						
						$this->pc->addBackendError(__('Ungültiger Dateityp hochgeladen. Es sind nur .ics Dateien möglich.', 'wptg'));
						
					}
					else
					{
						
						$arImportData = wptg_readICS(file_get_contents($_FILES['file']['tmp_name']));
						$arTerminData = array();
						 
						foreach ($arImportData as $row)
						{
						
							if (trim($row['BEGIN']) == "VEVENT")
							{
								 
								$arTerminData[] = $row;
								
							}
							
						}
						 
						if (!wptg_isSizedArray($arTerminData))
						{
							
							$this->pc->addBackendError(__('Keine Termindaten gefunden.', 'wptg'));
														
						}
						else
						{
									
							//wptg_Debug($arTerminData); die();
																			
							$importID = $this->db->ImportQuery(WPTG_TBL_IMPORT, array(
								'cdate' => "NOW()",
								'filename' => wptg_q($_FILES['file']['name']),
								'event_count' => wptg_q(sizeof($arTerminData))
							));
							
							global $current_user;
							
							$nTermin = 0;
							
							foreach ($arTerminData as $termin)
							{
														
								$post_id = $this->db->ImportQuery(WPTG_TBL_POSTS, array(
									'post_author' => wptg_q($current_user->ID),
									'post_date' => "NOW()", 
									'post_content' => wptg_q($termin['DESCRIPTION']),
									'post_title' => wptg_q($termin['SUMMARY']),									
									'post_status' => wptg_q('publish'),
									'post_name' => wptg_q($termin['SUMMARY']),
									'post_type' => wptg_q('wptg_events')									
								));
								
								if (wptg_isSizedString($termin['DTSTART;VALUE=DATE']))
								{

									// Ganztags
									
									$event_from = wptg_dayStart(strtotime($termin['DTSTART;VALUE=DATE']));
									$event_to = wptg_dayStart(strtotime($termin['DTEND;VALUE=DATE']) - 1);
									$event_fullday = '1';
									
								}
								else
								{
									
									$event_from = strtotime($termin['DTSTART']);
									$event_to = strtotime($termin['DTEND']);
									$event_fullday = '0';
									
								}
								
								// Custom Post Fields speichern
								$arMeta = array(
									'event_from' => $event_from,
									'event_to' => $event_to,
									'event_fullday' => $event_fullday,
									'event_import' => $importID
								);
								
								foreach ($arMeta as $k => $v)
								{
									
									$this->db->ImportQuery(WPTG_TBL_POSTMETA, array(
										'post_id' => wptg_q($post_id),
										'meta_key' => wptg_q($k),
										'meta_value' => wptg_q($v)
									));
									
								}
								
								$nTermin ++;
								
							}
							
							if ($nTermin <= 0) $this->pc->addBackendError(__('Es konnten keine Termine angelegt werden.', 'wptg'));
							else if ($nTermin == 1) $this->pc->addBackendMessage(__('Es wurde ein Termin angelegt.', 'wptg'));
							else $this->pc->addBackendMessage(wptg_translate(__('Es wurden #1# Termine angelegt.', 'wptg'), $nTermin));
														
						}
						 
					}
										
				}
				else
				{
					
					$this->pc->addBackendError(__('Keine Datei hochgeladen.', 'wptg'));
					
				}
				
				$this->pc->redirect(WPTG_URL_WP.'wp-admin/admin.php?page=wptg-configuration&a=import');
				
			}
			else if ($_REQUEST['do'] == 'clearHistory')
			{
				
				$this->db->Query("DELETE FROM `".WPTG_TBL_IMPORT."` ");
				
				$this->pc->addBackendMessage(__('Importhistory erfolgreich gelöscht.', 'wptg'));
				
				$this->pc->redirect(WPTG_URL_WP.'wp-admin/admin.php?page=wptg-configuration&a=import');
				
			}
			else if ($_REQUEST['do'] == 'restore')
			{
				
				$arPostId = $this->db->fetchAssocField("
					SELECT
						DISTINCT PM.`post_id`
					FROM
						`".WPTG_TBL_POSTMETA."` AS PM							
					WHERE
						PM.`meta_value` = '".wptg_q($_REQUEST['import_id'])."' AND
						PM.`meta_key` = 'event_import' 
				");
				
				foreach ($arPostId as $post_id)
				{
					
					// Post löschen
					$this->db->Query("DELETE FROM `".WPTG_TBL_POSTS."` WHERE `ID` = '".wptg_q($post_id)."' "); 

					// Post META löschen
					$this->db->Query("DELETE FROM `".WPTG_TBL_POSTMETA."` WHERE `post_id` = '".wptg_q($post_id)."' ");
					
				}
				
				if (sizeof($arPostId) <= 0) $this->pc->addBackendError(__('Es wurden keine Termine gelöscht.', 'wptg'));
				else if (sizeof($arPostId) == 1) $this->pc->addBackendMessage(__('Es wurde ein Termin gelöscht.', 'wptg'));
				else $this->pc->addBackendMessage(wptg_translate(__('Es wurden #1# Termine gelöscht.', 'wptg'), sizeof($arPostId)));
				
				$this->pc->redirect(WPTG_URL_WP.'wp-admin/admin.php?page=wptg-configuration&a=import');
				
			}
			
			$this->pc->view['arImport'] = $this->db->fetchAssoc("
				SELECT
					I.*, COUNT(DISTINCT P.`ID`) AS `posts`
				FROM
					`".WPTG_TBL_IMPORT."` AS I
						LEFT JOIN `".WPTG_TBL_POSTMETA."` AS PM ON (PM.`meta_key` = 'event_import' AND PM.`meta_value` = I.`id`)
						LEFT JOIN `".WPTG_TBL_POSTS."` AS P ON (PM.`post_id` = P.`id`)	
				GROUP BY I.`id`				
			"); 
			
			return $this->pc->render(WPTG_PATH_VIEW.'Admin/import.phtml');
			
		} // public function importAction()
		
	} // class wptg_AdminController

?>