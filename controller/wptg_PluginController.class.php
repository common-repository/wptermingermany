<?php

	/**
	 * Plugincontroller
	 * Wird nur einmal instanziiert und ist von jedem Kontroller �ber $this->pc erreichbar.
	 * @author Daschmi
	 */
	class wptg_PluginController
	{
		
		/** @var wptg_EventController */
		var $ec;
		
		/** @var wptg_AdminController */
		var $ac;
		
		/** @var wptg_db */
		var $db;
		
		/** @var string */
		var $prefix;
				
		/**
		 * Konstruktor
		 */
		public function __construct()
		{
			
			$this->ec = new wptg_EventController();
			$this->ac = new wptg_AdminController();
			$this->db = &$GLOBALS['wptg_db'];
			
		} // public function __construct()
		
		/**
		 * Wird einmalig beim laden des Plugins aufgerufen
		 */
		public function initPlugin($prefix)
		{
			
			$this->prefix = $prefix;
			
			if (is_admin())
			{
			
				add_action('admin_menu', array($this, 'admin_menu'));
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));				
				add_action('admin_init', array($this, 'admin_init'));
				add_action('save_post', array($this->ec, 'save_postdata'));
				add_action('init', array($this, 'init'));
				add_action('event_categorie_edit_form_fields', array($this, 'taxonomy_edit_form'), 10);
				
			}
			else
			{
												
				add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
				add_action('pre_get_posts', array($this, 'pre_get_posts'));
				add_filter('the_content', array($this, 'the_content'), 20);
												
			}
			
			add_action('wp_ajax_wptg', array($this, 'ajax'));			
			add_action('widgets_init', array($this, 'widgets_init'));
			
		} // public function initPlugin()
		
		public function ajax()
		{
			
			if ($_REQUEST['c'] == 'Event' && method_exists($this->ec, $_REQUEST['a'].'Action'))
			{
				
				$action = $_REQUEST['a'].'Action';
				$this->ec->$action();
				
			}
							
			die();
				
		} // public function ajax()
		
		public function pre_get_posts($query)
		{
			return $query;			
			
		} // public function pre_get_posts()
		
		/**
		 * Wordpress Hook "register_activation_hook"
		 */
		public function activation()
		{
			 
			require_once(WPTG_PATH_WP.'/wp-admin/includes/upgrade.php');
		 
			/**
			 * Importtabelle
			*/
			$sql = "CREATE TABLE ".WPTG_TBL_IMPORT." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				cdate datetime NOT NULL,
				filename VARCHAR(500) NOT NULL,
				event_count int(11) NOT NULL,				 
				PRIMARY KEY  id (id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
			 	
			dbDelta($sql);
			
		} // public function activation()
		
		/**
		 * Wordpress Action "admin_menu"
		 */
		public function admin_menu()
		{
			 
			add_submenu_page('edit.php?post_type=wptg_events', __('Konfiguration', 'wptg'), __('Konfiguration', 'wptg'), 'edit_posts', 'wptg-configuration', array($this->ac, 'indexAction'));
			
		} // public function admin_menu()
		
		/**
		 * Erstellt eine neue Seite im Wordpress
		 */
		public function createPage($title, $page_key, $page_id)
		{
		
			global $wpdb, $current_user;
				
			if ($page_id == -1)
			{
		
				$user_id = 0;
					
				if (function_exists("get_currentuserinfo"))
				{
					
					get_currentuserinfo();
					$user_id = $current_user->user_ID;
					
				}
		
				if ($user_id == 0 && function_exists("get_current_user_id"))
				{
					
					$user_id = get_current_user_id();
					
				}
		
				$page_id = $this->db->ImportQuery($wpdb->prefix."posts", array(
					"post_author" => $user_id,
					"post_date" => "NOW()",
					"post_title" => $title,
					"post_date_gmt" => "NOW()",
					"post_name" => strtolower($title),
					"post_status" => "publish",
					"comment_status" => "closed",
					"ping_status" => "neue-seite",
					"post_type" => "page",
					"post_content" => '',
					"ping_status" => "closed",
					"comment_status" => "closed",
					"post_excerpt" => "",
					"to_ping" => "",
					"pinged" => "",
					"post_content_filtered" => ""
				));
		
				$this->db->UpdateQuery($wpdb->prefix."posts", array(
					"post_name" => $this->clear($title, $page_id)
				), "`ID` = '".wptg_q($page_id)."'");
		
			}
				
			if ($page_id > 0)
			{
		
				$this->update_option($page_key, $page_id);
		
			}
				
		} // private function createPage($title)
		
		/**
		 * Bereinigt den URL Key bzw. das Path Segment
		 * Ist der Parameter post_id angegeben, so wird �berpr�ft das kein Post ungleich dieser ID mit diesem Segment existiert
		 */
		public function clear($value, $post_id = false)
		{
				
			$arReplace = array(
				'/�/' => 'Oe', '/�/' => 'oe',
				'/�/' => 'Ue', '/�/' => 'ue',
				'/�/' => 'Ae', '/�/' => 'ae',
				'/�/' => 'ss', '/\040/' => '-',
				'/\�/' => 'EURO',
				'/\//' => '_',
				'/\[/' => '',
				'/\]/' => '',
				'/\|/' => ''
			);
				
			$strReturn = preg_replace(array_keys($arReplace), array_values($arReplace), $value);
			$strReturn = sanitize_title($strReturn);
		
			if (is_numeric($post_id) && $post_id > 0)
			{
		
				$n = 0;
		
				while (true)
				{
						
					$n ++;
						
					$nPostsSame = $this->db->fetchOne("SELECT COUNT(*) FROM `".$this->prefix."posts` WHERE `post_name` = '".wptg_q($strReturn)."' AND `id` != '".wptg_q($post_id)."'");
						
					if ($nPostsSame > 0)
					{
		
						$strReturn .= $n;
		
					}
					else
					{
		
						break;
		
					}
						
				}
		
			}
				
			return $strReturn;
				
		} // private function clear($value)
		
		/**
		 * Fügt eine Hinweismeldung eines Backend Moduls hinzu
		 * Wird mittels writeBackendMessage ausgegeben
		 */
		public function addBackendMessage($message)
		{
				
			if (!in_array($message, (array)$_SESSION['wptg']['backendMessage'])) $_SESSION['wptg']['backendMessage'][] = $message;
				
		} // public function addBackendMessage($message)
		
		/**
		 * Fügt eine neue Fehlermeldung eines Backend Moduls hinzu
		 * @param \String $hideLink Soll die Meldung ausblendbar sein, so muss ein Key mitgegeben werden der die Meldung identifiziert
		 */
		public function addBackendError($message, $hideLinkKey = false)
		{
				
			// Wenn schon drin, dann nichts machen
			if (in_array($message, (array)$_SESSION['wptg']['backendError'])) return;
				
			if (wptg_isSizedString($hideLinkKey))
			{
		
				// Wurde die Meldung bereits ausgeblendet ?
				if ($this->get_option($hideLinkKey) === '1')
				{
						
					return false;
						
				}
		
				$message .= '<p style="float:right;">'.wptg_translate(
						__('<a href="#1#">Klicken Sie hier, um die Meldung auszublenden.</a>'),
						WPTG_URL_WP.'wp-admin/admin.php?page=wptg-Admin&noheader=1&action=clearMessage&wptg_message='.$hideLinkKey.'&wptg_redirect='.rawurlencode($_SERVER['REQUEST_URI'])
				).'</p><div class="wptg_clearer"></div><br />';
		
			}
				
			$_SESSION['wptg']['backendError'][] = $message;
				
		} // public function addBackendError($message, $hideLinkKey = false)
		 
		/**
		 * Gibt die Backend Messages aus
		 */
		public function writeBackendMessage()
		{
		
			$strOut  = '';
				
			if (!isset($_SESSION['wptg']['backendMessage']) && !isset($_SESSION['wptg']['backendError'])) return;
				
			if (is_array($_SESSION['wptg']['backendMessage']) && sizeof($_SESSION['wptg']['backendMessage']) > 0)
			{
					
				$strOut  .= '<div class="wptg_backendmessage"><div id="message" class="updated">';
		
				foreach ($_SESSION['wptg']['backendMessage'] as $m)
				{
						
					if (preg_match('/^nohspc_/', $m))
					{
						$strOut .= '<p>'.preg_replace('/^nohspc_/', '', $m).'</p>';
					}
					else
					{
						$strOut .= '<p>'.wptg_hspc($m).'</p>';
					}
						
				}
		
				$strOut .= '</div></div>';
		
				unset($_SESSION['wptg']['backendMessage']);
		
			}
		
			if (wptg_isSizedArray($_SESSION['wptg']['backendError']))
			{
		
				$strOut  .= '<div class="wptg_backendmessage"><div id="message" class="error">';
		
				foreach ($_SESSION['wptg']['backendError'] as $m)
				{
						
					if (preg_match('/^nohspc_/',$m))
					{
						$strOut .= '<p>'.preg_replace('/^nohspc_/', '', $m).'</p>';
					}
					else
					{
						$strOut .= '<p>'.wptg_hspc($m).'</p>';
					}
						
				}
		
				$strOut .= '</div></div>';
		
				unset($_SESSION['wptg']['backendError']);
		
			}
		
			return $strOut;
				
		} // public function writeBackendMessage()
				
		public function init()
		{
			
			register_taxonomy('event_categorie', 'wptg_events', array(
				'hierarchical' => true,
				'labels' => array(
					'name' => __('Kalender', 'wptg'),
					'singular_name' => __('Kalender', 'wptg'),
					'search_items' =>  __('Kalender suchen', 'wptg'),					
					'all_items' => __('Alle Kalender', 'wptg'),
					'parent_item' => __('�bergeordneter Kalender', 'wptg'),
					'parent_item_colon' => __('�bergeordneter Kalender:', 'wptg'),
					'edit_item' => __('Kalender bearbeiten', 'wptg'),
					'update_item' => __('Kalender aktualisieren', 'wptg'),
					'add_new_item' => __('Neuen Kalender anlegen', 'wptg'),
					'new_item_name' => __('Neuer Kalender', 'wptg'),
					'menu_name' => __('Kalender', 'wptg'),
				),
				'show_ui' => true,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var' => true,
				'rewrite' => array('slug' => 'event_categorie')
			));
			
			register_post_type('wptg_events',
				array(
					'labels' => array(
						'name' => __('Termine', 'wptg'),
						'singular_name' => __('Termine 2', 'wptg'),
						'name' => __('Termine', 'wptg'),
						'singular_name' => __('Termin', 'wptg'),
						'add_new_item' => __('Neuer Termin', 'wptg'),
						'edit_item' => __('Termin bearbeiten', 'wptg'),
						'new_item' => __('Neuer Termin', 'wptg')
					),
					'menu_icon' => 'dashicons-calendar',
					'public' => true,
					'publicly_queryable' => true,
					'has_archive' => true,
					'rewrite' => array('with_front' => false, 'slug' => 'wptg_events', 'pages' => false),
					'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions'),
					'register_meta_box_cb' => array($this->ec, 'register_meta_box_cb'),
					'menu_icon' => 'dashicons-calendar',
					'taxonomies' => array('event_categorie')										
				)
			);
			
//			flush_rewrite_rules();
			
		} // public function init()
		
		public function widgets_init()
		{
			
			register_widget('wptg_monthwidget');
			
		} // public function widgets_init()
		
		public function taxonomy_edit_form()
		{
			
			if ($_REQUEST['taxonomy'] != 'event_categorie') return;
			
			$term_id = $_GET['tag_ID'];
			$term = get_term_by('id', $term_id, 'taxonomy');
			$meta = get_option("taxonomy_".$term_id);
						
		} // public function taxonomy_edit_form()
		
		public function the_content($content)
		{
			
			if (wptg_isSizedInt(get_the_ID()) && get_the_ID() == $this->get_option('wptg_datePage'))
			{
			
				$content = $this->ec->the_content($content);
				
			}
			 
			return $content;
			
		} // public function the_content($content)
		
		public function getEventUrl($tDay, $categories)
		{
			
			$blog_url  = get_permalink($this->get_option('wptg_datePage'));
			$blog_url .= ((strpos($blog_url, '?') === false)?'?':'&');			
			 
			$blog_url .= 'wptg_action=showDayEvents&wptg_day='.$tDay.'&wptg_categories='.$categories;
						
			return $blog_url;
			
		} // public function getEventUrl($arTermine)
		
		public function getMonthURL($time_month, $categories)
		{
			
			$blog_url  = get_permalink($this->get_option('wptg_datePage'));
			$blog_url .= ((strpos($blog_url, '?') === false)?'?':'&');
			 
			$blog_url .= 'wptg_action=showMonthEvents&wptg_day='.$time_month.'&wptg_categories='.$categories;
			
			return $blog_url;
			
		} // public function getMonthURL($year, $month, $categories)
		
		/**
		 * Scripte und Styles im Admin Bereich hinzuf�gen
		 */
		public function admin_enqueue_scripts()
		{
	 
			wp_enqueue_style('wptg_admin_css', $this->getRessourceURL('css/admin.css'), false, '1.0.0');			
									
		} // public function admin_enqueue_scripts()
		
		public function enqueue_scripts()
		{
			
			wp_enqueue_script('jquery');
			wp_enqueue_script('wptg_frontend', $GLOBALS['wptg_pc']->getRessourceURL('js/frontend.js'));
			wp_enqueue_script('wptg_fullcalendar', WPTG_URL.'lib/fullcalendar-1.6.4/fullcalendar/fullcalendar.js', array('jquery'));
			
			wp_enqueue_style('wptg_frontend_css', $this->getRessourceURL('css/frontend.css'), false, '1.0.0');
			wp_enqueue_style('wptg_fullcalendar', WPTG_URL.'lib/fullcalendar-1.6.4/fullcalendar/fullcalendar.css', false, '1.0.0');

			wp_localize_script('wptg_frontend', 'wptg', array('ajax_url' => admin_url('admin-ajax.php')));
			
		} // public function enqueue_scripts()
		
		/**
		 * Wordpress Action "admin_init"
		 */
		public function admin_init()
		{
			
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
						
		} // public function admin_init()

		/**
		 * Generiert eine Ausgabe
		 */
		public function render($file)
		{
			
			ob_start();
						
			include $file;
						
			$content = ob_get_contents();			
			ob_end_clean();
			
			return $content;
			
		} // public function render()
		 
		/**
		 * Generiert die Ausgabe und gibt sie direkt aus
		 * @param String $file
		 */
		public function renderOut($file)
		{
			
			echo $this->render($file);
			
		} // public function renderOut($file)
		 
		public function get_option($value)
		{
			
			return get_option($value);
			
		} // public function get_option($value)

		public function redirect($url)
		{
		
			die(header('Location: '.html_entity_decode($url)));
				
		} // public function redirect($url)
		
		public function update_option($key, $value)
		{
			
			return update_option($key, $value);
			
		} // public function update_option($key, $value)
		
		/**
		 * Gitb die URL zu einer Ressource (JS/GFX/CSS/..) die unter views liegt zur�ck
		 * In der Variable path wird der Pfad ab dem views Verzeichnis �bergeben.  
		 */
		public function getRessourceURL($path)
		{
		 
			$url = WPTG_URL.'views/'.$path;		 
				
			return $url;
				
		} // public function getRessourceURL($path)
		
	} // class wptg_PluginController

?>