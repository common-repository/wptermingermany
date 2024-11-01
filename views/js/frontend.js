
	var wptg_fullcalendar_localOptions = {
		buttonText: {
			today: 'Heute',
			month: 'Monat',
			day: 'Tag',
			week: 'Woche'
		},
		titleFormat: {
		    month: 'MMMM yyyy',                            
		    week: "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}",
		    day: 'dddd,<br /> MMM d, yyyy'                  
		},
		monthNames: ['Januar','Februar','M�rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
		monthNamesShort: ['Jan','Feb','M�r','Apr','Mai','Jun','Jul','Aug','Sept','Okt','Nov','Dez'],
		dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
		dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		allDayText: 'ganztags',
		firstDay: 1,
		axisFormat: 'H:mm',
		timeFormat: {
			agenda: 'H:mm{ - H:mm}'
		},
		timeFormat: 'H(:mm)'
	};
	
	function wptg_switchCalendar(key, direction, el)
	{
		
		jQuery.ajax( {
			url: wptg.ajax_url,
			data: {
				key: key,
				action: 'wptg',
				c: 'Event',
				a: 'switchCalendar',
				direction: direction
			},
			success: function(data) {
				
				el.html(data);
				
			}
		} );
				
	} // function wptg_switchCalendar(key, direction, el)

	function wptg_switchCalendarPrev(el)
	{
		
		wptg_switchCalendar(jQuery(el).parents('.wptg_head').find('.wptg_monthname').attr("id"), 'prev', jQuery(el).parents('.wptg_month_widget'));
		
		return false;
		
	} // function wptg_switchCalendarPrev(el)
	
	function wptg_switchCalendarNext(el)
	{
		
		wptg_switchCalendar(jQuery(el).parents('.wptg_head').find('.wptg_monthname').attr("id"), 'next', jQuery(el).parents('.wptg_month_widget'));
		
		return false;
		
	} // function wptg_switchCalendarPrev(el)
	 
	//alert("K" + wptg.ajax_url);