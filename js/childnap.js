//jQuery for child window where parent window has jquery-nap-1.0.0.js running
//These functions add a listener to the child window and call the parent window when movement detected

$(window).mousemove(function() {
		parent.jQuery.fn.nap.interaction();
		});
$(window).keyup(function() {
		parent.jQuery.fn.nap.interaction();
		});
$(window).mousedown(function() {
		parent.jQuery.fn.nap.interaction();
		});

$(window).scroll(function() {
		parent.jQuery.fn.nap.interaction();
		});
