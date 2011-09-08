/*
 * LimeSurvey
 * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * $Id: lime-slider.js 9648 2011-01-07 13:06:39Z c_schmitz $
 */



// This file will auto convert slider divs to sliders
$(document).ready(function(){
	// call the init slider routine for each element of the .multinum-slider class
	$(".multinum-slider").each(function(i,e) {
		var basename = e.id.substr(10);
		
		$(this).prev('label').addClass('slider-label'); //3796 TP - add a class to the labels in slider questions to facilitate styling

		//$("#slider-"+basename).addClass('ui-slider-2');
		//$("#slider-handle-"+basename).addClass('ui-slider-handle2');
		var slider_divisor = $('#slider-param-divisor-' + basename).attr('value');
		var slider_min = $('#slider-param-min-' + basename).attr('value');
		var slider_max = $('#slider-param-max-' + basename).attr('value');
		var slider_stepping = $('#slider-param-stepping-' + basename).attr('value');
		var slider_startvalue = $('#slider-param-startvalue-' + basename).attr('value');
		var slider_onchange = $('#slider-onchange-js-' + basename).attr('value');
		var slider_prefix = $('#slider-prefix-' + basename).attr('value');
		var slider_suffix = $('#slider-suffix-' + basename).attr('value');
		var sliderparams = Array();

		sliderparams['min'] = slider_min*1; // to force numerical we multiply with 1
		sliderparams['max'] = slider_max*1; // to force numerical we multiply with 1
		// not using the stepping param because it is not smooth
		// using Math.round workaround instead
		//sliderparams['stepping'] = slider_stepping;
		//sliderparams['animate'] = true;
		if (slider_startvalue != 'NULL')
		{
			sliderparams['value']= slider_startvalue*1;
		}
		sliderparams['slide'] = function(e, ui) {
				//var thevalue = ui.value / slider_divisor;
				if ($('#slider-modifiedstate-'+basename).val() ==0) $('#slider-modifiedstate-'+basename).val('1');
				
				function updateCallout() {
					var thevalue = slider_stepping * Math.round(ui.value / slider_stepping) / slider_divisor;
					$('#slider-callout-'+basename).css('left', $(ui.handle).css('left')).text(slider_prefix + thevalue + slider_suffix);
				}
				// Delay updating the callout because it was picking up the last postion of the slider
				setTimeout(updateCallout, 10); 
			};
		sliderparams['stop'] = function(e, ui) {
				//var thevalue = ui.value / slider_divisor;
				var thevalue = slider_stepping * Math.round(ui.value / slider_stepping) / slider_divisor;
				$('#slider-callout-'+basename).css('left', $(ui.handle).css('left')).text(slider_prefix + thevalue + slider_suffix);
			};

		sliderparams['change'] = function(e, ui) {
				//var thevalue = ui.value / slider_divisor;
				var thevalue = slider_stepping * Math.round(ui.value / slider_stepping) / slider_divisor;
				$('#answer'+basename).val(thevalue);
				checkconditions( thevalue,'#answer'+basename,'text');
				eval(slider_onchange);	
			};


		$('#slider-'+basename).slider(sliderparams);

		
		if (slider_startvalue != 'NULL' && $('#slider-modifiedstate-'+basename).val() !=0)
		{
				var thevalue = slider_startvalue / slider_divisor;
                $('#slider-callout-'+basename).css('left', $('.ui-slider-handle:first').css('left')).text(slider_prefix + thevalue + slider_suffix);
		}
	})
});
