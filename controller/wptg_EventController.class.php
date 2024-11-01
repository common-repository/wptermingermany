<?php

	/**
	 * Verarbeutet Aktionen auf den Events
	 * @author Daschmi
	 */
	class wptg_EventController
	{
		
		/** @var wptg_PluginController */
		public $pc;
		
		public function __construct()
		{
			
			$this->pc = &$GLOBALS['wptg_pc'];
			
		} // public function __construct()
		
		public function save_postdata($post_id)
		{
			
			// Anfrage validieren
			if (!isset($_POST['wptg_event_meta_box_nounce'])) return $post_id;			
			$nonce = $_POST['wptg_event_meta_box_nounce'];			
			if (!wp_verify_nonce($nonce, 'wptg_event_meta')) return $post_id;			
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;			
			if (!current_user_can('edit_post', $post_id)) return $post_id;
						
			// Werte speichern
			$fullday = sanitize_text_field($_POST['wptg']['fullday']);			
						
			if ($fullday !== '1')
			{
				 
				$from  = wptg_toTime(sanitize_text_field($_POST['wptg']['from']['date']));
				$from += intval(sanitize_text_field($_POST['wptg']['from']['hour'])) * 60 * 60;
				$from += intval(sanitize_text_field($_POST['wptg']['from']['minute'])) * 60;
								
				$to  = wptg_toTime(sanitize_text_field($_POST['wptg']['to']['date']));
				$to += intval(sanitize_text_field($_POST['wptg']['to']['hour'])) * 60 * 60;
				$to += intval(sanitize_text_field($_POST['wptg']['to']['minute'])) * 60;
				
			}
			else
			{

				$from = wptg_toTime(sanitize_text_field($_POST['wptg']['from']['date']));
				$to = wptg_toTime(sanitize_text_field($_POST['wptg']['to']['date']));
				
			}
			 
			update_post_meta($post_id, 'event_from', $from);
			update_post_meta($post_id, 'event_to', $to);
			update_post_meta($post_id, 'event_fullday', $fullday);
			
		} // public function save_postdata($post_id)
		
		/**
		 * Meta Boxen f�r die Custom Post Types im Backend
		 */
		public function register_meta_box_cb()
		{
			
			add_meta_box(
				'wptg_event_meta',
				__('Termin', 'wptg'),
				array($this, 'meta_box'),
				'',
				'side',
				'high'
			);
			
		} // public function register_meta_box_cb()
		
		public function meta_box($post)
		{
			
			wp_nonce_field('wptg_event_meta', 'wptg_event_meta_box_nounce');
			
			$this->pc->view['values'] = array();
			$this->pc->view['values']['from'] = get_post_meta($post->ID, 'event_from', true);
			$this->pc->view['values']['to'] = get_post_meta($post->ID, 'event_to', true);
			$this->pc->view['values']['fullday'] = get_post_meta($post->ID, 'event_fullday', true);
			
			$this->pc->renderOut(WPTG_PATH_VIEW.'/Termin/meta_box.phtml');
			
		} // public function meta_box($post)
		
		public function the_content($content)
		{
			
			$this->pc->view['eventUrl']  = admin_url('admin-ajax.php');
			$this->pc->view['eventUrl'] .= ((strpos($this->pc->view['eventUrl'], '?') === false)?'?':'&');
			$this->pc->view['eventUrl'] .= 'action=wptg&c=Event&a=getEventData&wptg_categories='.$_REQUEST['wptg_categories'];
			
			if ($_REQUEST['wptg_action'] == 'showDayEvents')
			{
				
				$tDay = $_REQUEST['wptg_day'];
												
				$this->pc->view['year'] = date('Y', $tDay);
				$this->pc->view['month'] = date('m', $tDay) - 1;
				$this->pc->view['date'] = date('d', $tDay);
				
				$this->pc->view['mode'] = 'agendaDay';				
				
			}
			else if ($_REQUEST['wptg_action'] == 'showMonthEvents')
			{
				
				$tDay = $_REQUEST['wptg_day'];
												
				$this->pc->view['year'] = date('Y', $tDay);
				$this->pc->view['month'] = date('m', $tDay) - 1;
				$this->pc->view['date'] = date('d', $tDay);
				
				$this->pc->view['mode'] = 'month';
				
			}
			else
			{
				
				$this->pc->view['year'] = date('Y');
				$this->pc->view['month'] = date('m') - 1;
				$this->pc->view['date'] = date('d');
				
				$this->pc->view['mode'] = 'month';
				
			}
			
			$content = $this->pc->render(WPTG_PATH_VIEW.'/Event/calendarView.phtml');
						
			return $content;
			
		} // public function the_content($content)
		
		public function getEventDataAction()
		{
			 
			//echo "Start: ".date('d.m.Y H:i:s', wptg_dayStart($_REQUEST['start']))."\r\n";
			//echo "Ende: ".date('d.m.Y H:i:s', wptg_dayStart($_REQUEST['end']))."\r\n";
			//die();
			
			$arEvents = wptg_event::find(array(
				'date_from' => wptg_dayStart($_REQUEST['start']),
				'date_to' => wptg_dayEnd($_REQUEST['end']),
				'cat' => explode(',', $_REQUEST['wptg_categories'])					
			));
			
			$arJSON = array();
			
			foreach ($arEvents as $e)
			{
				 
				$arJSON[] = array(
					'title' => $e->post['post_title'],
					'start' => date('Y-m-d H:i:s', $e->event_from),
					'end' => date('Y-m-d H:i:s', $e->event_to),
					'url' => get_permalink($e->id),
					'allDay' => (($e->event_fullday === '1')?true:false)
				);
				
			}
			 			
			die(json_encode($arJSON));			
			
		}
		 
		/**
		 * Wird per Ajax �ber das Monatskalenderwidget aufgerufen
		 */
		public function switchCalendarAction()
		{
			
			$arKey = explode(':', $_REQUEST['key']);
			
			$dt = new DateTime();
			$dt->setDate($arKey[2], $arKey[3], 1);
			
			
			if ($_REQUEST['direction'] == 'next')
			{
				
				$dt->add(new DateInterval('P1M'));
				
			}
			else if ($_REQUEST['direction'] == 'prev')
			{
				
				$dt->sub(new DateInterval('P1M'));
				
			}
			
			$GLOBALS['wptg_pc']->view['widget_head'] = false;
			$this->widgetData($dt->getTimestamp(), $arKey[1]);
			
			echo $GLOBALS['wptg_pc']->render(WPTG_PATH_VIEW.'Termin/month_widget.phtml');
			
		} // public function switchCalendarAction()
		
		public function widgetData($time_month, $widget_key)
		{
			
			$this->pc->view['time_month'] = $time_month;
			$this->pc->view['month'] = date('m', $time_month);
			$this->pc->view['year'] = date('Y', $time_month);
			$this->pc->view['widget_key'] = $widget_key;
			$this->pc->view['monthname'] = strftime('%B', $time_month);
			$this->pc->view['month_start'] = date('N', mktime(0, 0, 0, date('n', $time_month), 1, date('Y', $time_month)));
			$this->pc->view['table_body'] = array();
			 
			$widget_id = substr($widget_key, 17);
			$widget_conf = get_option('widget_wptg_monthwidget');
			$widget_conf = $widget_conf[$widget_id];

			$this->pc->view['categories'] = $widget_conf['wptg_categories'];
			
			$weekRow = 0; $dayCol = $this->pc->view['month_start'] - 1;
			
			for ($day = 1; $day <= date('t', $time_month); $day ++)
			{
			
				if($dayCol > 6)
				{
			
					$dayCol = 0; $weekRow++;
			
				}
			
				if (!isset($this->pc->view['table_body'][$weekRow]))
				{
			
					$this->pc->view['table_body'][$weekRow] = array();
			
				}
				
				$time_day = mktime(0, 0, 0, $this->pc->view['month'], $day, $this->pc->view['year'], WPSG_TIME_DST);

				$arTermine = wptg_event::find(array(
					'date_from' => wptg_dayStart($time_day),
					'date_to' => wptg_dayEnd($time_day),
					'cat' => explode(',', $widget_conf['wptg_categories'])
				));
				 
				$arClass = array();
				if (wptg_isSizedArray($arTermine)) $arClass[] = 'wptg_event';
				
				$this->pc->view['table_body'][$weekRow][$dayCol] = array(
					'class' => implode(' ', $arClass),
					'arTermine' => $arTermine,
					'day' => mktime(0, 0, 0, $this->pc->view['month'], $day, $this->pc->view['year']),
					'dayLabel' => $day
				);
			
				$dayCol++;
			
			}			
			 
		} // public function widgetData($month)
		
	} // class wptg_EventController

?>