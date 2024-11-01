<?php

	function wptg_drawFormDatepicker($name, $label, $value, $conf)
	{
		
		$GLOBALS['wptg_pc']->view['label'] = $label;
		
		$time = wptg_toTime($value);
		
		if (wptg_isSizedString($conf['placeholder'])) $GLOBALS['wptg_pc']->view['placeholder'] = $conf['placeholder'];
		
		if (wptg_isSizedString($conf['id'])) $GLOBALS['wptg_pc']->view['id'] = $conf['id']; 
		else $GLOBALS['wptg_pc']->view['id'] = preg_replace('/\[|\]/', '', $name); 
		
		$GLOBALS['wptg_pc']->view['dateFormat'] = 'dd.mm.yy';
		 		
		if (wptg_isSizedString($conf['timepicker']) && $conf['timepicker'] == '1') 
		{
												
			$GLOBALS['wptg_pc']->view['timepicker'] = true;
			$GLOBALS['wptg_pc']->view['value_time_hour'] = (($time > 0)?date('H', $time):'');
			$GLOBALS['wptg_pc']->view['value_time_minute'] = (($time > 0)?date('i', $time):'');
			$GLOBALS['wptg_pc']->view['name_hour'] = $name.'[hour]';
			$GLOBALS['wptg_pc']->view['name_minute'] = $name.'[minute]';
			$GLOBALS['wptg_pc']->view['name'] = $name.'[date]';
			
		}
		else 
		{
			
			$GLOBALS['wptg_pc']->view['timepicker'] = false;
			$GLOBALS['wptg_pc']->view['name'] = $name;
			
		}
		
		$GLOBALS['wptg_pc']->view['value_date'] = (($time > 0)?date('d.m.Y', $time):'');
				
		return $GLOBALS['wptg_pc']->render(WPTG_PATH_VIEW.'Form/datepicker.phtml');
		
	} // function wptg_drawFormDatepicker($name, $label, $value, $conf)

	function wptg_drawFormCheckbox($name, $label, $value, $conf = array())
	{
		
		$GLOBALS['wptg_pc']->view['name'] = $name;
		$GLOBALS['wptg_pc']->view['label'] = $label;
		$GLOBALS['wptg_pc']->view['value'] = $value;
		
		if (wptg_isSizedString($conf['id'])) $GLOBALS['wptg_pc']->view['id'] = $conf['id'];
		else $GLOBALS['wptg_pc']->view['id'] = preg_replace('/\[|\]/', '', $name);
		
		return $GLOBALS['wptg_pc']->render(WPTG_PATH_VIEW.'Form/checkbox.phtml');
		
	} // function wptg_drawFormCheckbox($name, $label, $value, $conf = array())
	
	/**
	 * Zeichnet eine Selectbox für das Backend
	 */
	function wptg_drawFormSelect($name, $label, $value, $values, $conf = array())
	{
		
		$strSELECT = "";
		
		if (!wptg_isSizedString($conf['id'])) $id = $name;
		
		if (wptg_isSizedInt($conf['size']))
		{
			
			$strSELECT .= ' size="'.$conf['size'].'" ';
			
		}
		
		if (isset($conf['multi']) && $conf['multi'] === true) 
		{
			
			$strSELECT .= ' multiple="multiple" ';
			
		}
		
		$html_select  = '';
		$html_select .= '<select name="'.wptg_hspc($name).'" '.$strSELECT.' id="'.wptg_hspc($id).'">';
		
		foreach ($values as $k => $v)
		{
			
			$strSelected = "";
			
			if (wptg_isSizedArray($value) && in_array($k, $value)) $strSelected = 'selected="selected"';
			else if ($k == $value) $strSelected = 'selected="selected"';
			
			$html_select .= '<option value="'.wptg_hspc($k).'" '.$strSelected.'>'.wptg_hspc($v).'</option>';
			
		}
		
		$html_select .= '</select>';
		
		$return  = '';		
		$return .= '<div class="wptg_formfield wptg_formfield_select">';		
		$return .= '<div class="wptg_label">';		
		$return .= '<label for="'.wptg_hspc($id).'">'.$label.'</label>';
		$return .= '</div>';		
		$return .= '<div class="wptg_value">';		
		$return .= $html_select;		
		$return .= '</div><div class="wptg_clearer"></div>';
		$return .= '</div>';
		
		return $return;
		
	} // function wptg_drawFormSelect($name, $label, $value, $value, $conf = array())

?>