<?php

	class wptg_monthwidget extends WP_Widget 
	{
		
		var $id = "wptg_monthwidget";
		
		public function wptg_monthwidget() 
		{
			
			$this->WP_Widget('wptg_monthwidget', 'Termine - Monatsansicht', array(
	    		"description" => __("Termine - Monatsansicht", 'wptg')
	    	));
			
		} // public function wptg_monthwidget()
		
		function widget($args, $settings)
		{
			
			$GLOBALS['wptg_pc']->view['args'] = $args;
			$GLOBALS['wptg_pc']->view['settings'] = $settings;
			$GLOBALS['wptg_pc']->view['widget_head'] = true;
			
			$GLOBALS['wptg_pc']->ec->widgetData(time(), $args['widget_id']);
						
			echo $GLOBALS['wptg_pc']->render(WPTG_PATH_VIEW.'Termin/month_widget.phtml');
						
		} // function widget($args, $instance)
		
		function form($instance)
		{
			
			if (isset($instance['wptg_categories'])) { $wptg_categories = explode(',', $instance['wptg_categories']); } else { $wptg_categories = array(); }
 
			$arCategories = array('0' => __('Alle Kategorien', 'wptg')) + wptg_getEventCategorieArray();			 
			
			echo wptg_drawFormSelect('wptg_categories[]', __('Kategorieanzeige', 'wptg'), $wptg_categories, $arCategories, array('size' => 5, 'multi' => true));
			 			
			echo '<br /><div class="wptg_clearer"></div>';
			
		}
		
		function update($new_instance, $old_instance) 
		{
			
			$instance = array();
			$instance['wptg_categories'] = implode(',', $_REQUEST['wptg_categories']);
			 
			return $instance; 	
			
		} // function update($new_instance, $old_instance)
		 
	} // class My_Widget extends WP_Widget

?>