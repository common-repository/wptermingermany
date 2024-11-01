<?php

	/*
	Plugin Name: wpTerminGermany
	Text Domain: wptg
	Domain Path: /lang
	Plugin URI: http://daschmi.de/
	Description: Das deutsche WordPress Event-Plugin!
	Author: Daschmi
	Version: 0.1
	Author URI: http://daschmi.de/
	*/

	date_default_timezone_set("Europe/Berlin");
	
	global $wpdb;

	$prefix = $wpdb->prefix;
	$wp_upload_dir = wp_upload_dir();
	
	// Konstanten
	define('WPSG_TIME_DST', '1');
	define('WPTG_VERSION', '9.9.9');
	define('WPTG_PATH', dirname(__FILE__).'/');
	define('WPTG_PATH_LIB', WPTG_PATH.'/lib/');
	define('WPTG_PATH_CONTROLLER', WPTG_PATH.'controller/');
	define('WPTG_PATH_MODEL', WPTG_PATH.'model/');
	define('WPTG_PATH_WP', ABSPATH);
	define('WPTG_PATH_CONTENT', WP_CONTENT_DIR.'/'); /* wp-content/ */
	define('WPTG_PATH_UPLOADS', $wp_upload_dir['basedir'].'/wptg/');
	define('WPTG_PATH_VIEW', dirname(__FILE__).'/views/');
	
	define('WPTG_URL', plugins_url().'/wptermingermany/');	
	define('WPTG_URL_WP', site_url().'/'); 
	
	// Tabellen
	define('WPTG_TBL_IMPORT', $prefix.'wptg_import');
	define('WPTG_TBL_POSTS', $prefix.'posts');
	define('WPTG_TBL_POSTMETA', $prefix.'postmeta');
	define('WPTG_TBL_TERMRELATIONSHIP', $prefix.'term_relationships');
	
	require_once(WPTG_PATH_LIB.'functions.inc.php'); 
	require_once(WPTG_PATH_LIB.'helper_functions.inc.php');
	require_once(WPTG_PATH_LIB.'wptg_db.class.php');
	require_once(WPTG_PATH_CONTROLLER.'wptg_PluginController.class.php');
	require_once(WPTG_PATH_CONTROLLER.'wptg_EventController.class.php');
	require_once(WPTG_PATH_CONTROLLER.'wptg_AdminController.class.php');
	require_once(WPTG_PATH_MODEL.'wptg_monthwidget.class.php');
	require_once(WPTG_PATH_MODEL.'wptg_event.class.php');
	
	$_GET['wptg_quotecheck'] = '\"CHECK';
	
	/** @var wptg_db */
	$GLOBALS['wptg_db'] = new wptg_db();
	
	/** @var wptg_PluginController */
	$GLOBALS['wptg_pc'] = new wptg_PluginController();
	
	if (!session_id()) { session_start(); }
	
	$GLOBALS['wptg_pc']->initPlugin($prefix);
		
	register_activation_hook(__FILE__, array($GLOBALS['wptg_pc'], 'activation'));
	
?>