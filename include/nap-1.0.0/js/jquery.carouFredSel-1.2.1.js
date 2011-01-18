/*	
 *	jQuery carouFredSel 1.2.0
 *	www.frebsite.nl
 *	Copyright (c) 2010 Fred Heusschen
 *	Licensed under the MIT license.
 *	http://www.opensource.org/licenses/mit-license.php
 */


(function($) {
	$.fn.carouFredSel = function(options) {
		return this.each(function() {
			var opts 			= $.extend(true, {}, $.fn.carouFredSel.defaults, options),
				$ul 			= $(this),
				$items 			= $("li", $ul),
				totalItems		= $items.length,
				nextItem		= opts.visibleItems,
				prevItem		= totalItems-1,
				itemWidth		= $items.outerWidth(),
				itemHeight		= $items.outerHeight(),
				autoInterval	= null,
				direction		= (opts.direction == "up" || opts.direction == "right") ? "next" : "prev";


			if (opts.visibleItems >= totalItems) {
				try { console.log('carouFredSel: Not enough items: terminating'); } catch(err) {}
				return;
			}


			if (opts.scroll.items == 0) 	opts.scroll.items 		= opts.visibleItems;
					
			opts.auto 		= $.extend({}, 	opts.scroll,	opts.auto);
			opts.buttons 	= $.extend({}, 	opts.scroll,	opts.buttons);
			opts.next 		= $.extend({}, 	opts.buttons,	opts.next);
			opts.prev 		= $.extend({}, 	opts.buttons,	opts.prev);

			if (!opts.auto.pauseDuration)	opts.auto.pauseDuration	= 2500;

			opts.buttons = null;
			opts.scroll  = null;


			if (opts.direction == "right" ||
				opts.direction == "left"
			) {
				var cs1 = {
					width	: itemWidth * opts.visibleItems * 2
				}
				var cs2 = {
					width	: itemWidth * opts.visibleItems,
					height	: $ul.outerHeight() || itemHeight
				}
			} else {
				var cs1 = {
					height	: itemHeight * opts.visibleItems * 2
				}
				var cs2 = {
					height	: itemHeight * opts.visibleItems,
					width	: $ul.outerWidth() || itemWidth
				}
			}

			$ul.css(cs1).css({
				position	: "absolute"
			}).wrap('<div class="caroufredsel_wrapper" />').parent().css(cs2).css({ 
				position	: "relative",
				overflow	: "hidden"
			});


			$items.filter(":gt("+(opts.visibleItems-1)+")").remove();
			$ul
				.bind("pause", function() {
					if (autoInterval != null) {
						clearTimeout(autoInterval);
					}
				})
				.bind("play", function(e, d) {
					if (opts.autoPlay) {
						if (d == null	||
							d == '' 	||
							typeof(d)	|| 'undefined'
						) {
							d = direction;
						}

						autoInterval = setTimeout(function() {
							$ul.trigger(d, opts.auto);
						}, opts.auto.pauseDuration);
					}
				})
				.bind("next", function(e, sliderObj) {
					if ($ul.is(":animated")) return;


						 if (typeof(sliderObj) == 'undefined')	sliderObj = opts.next;
						 if (typeof(sliderObj) == 'object') 	numItems  = sliderObj.items;
					else if (typeof(sliderObj) == 'number') {
						numItems  = sliderObj;
						sliderObj = opts.next;
					}
					if (!numItems || typeof(numItems) != 'number') return;


					var oldItems = $("li", $ul);
					for (var a = 0; a < numItems; a++) {
						$ul.append($($items[nextItem]).clone(true));
						if (++nextItem >= totalItems) nextItem = 0;
						if (++prevItem >= totalItems) prevItem = 0;
					}
					var newItems = $("li:gt("+(numItems-1)+")", $ul);


					if (opts.direction == "right" ||
						opts.direction == "left"
					) {
						var pos = 'left',
							siz = itemWidth;
					} else {
						var pos = 'top',
							siz = itemHeight;
					}
					var ani = {},
						cal = {};

					ani[pos] = $ul.offset()[pos]-oldItems.offset()[pos] || -(siz * numItems);
					cal[pos] = 0;


					if (sliderObj.onBefore) {
						sliderObj.onBefore(oldItems, newItems, "next");
					}

					$ul
						.data("numItems", 	numItems)
						.data("sliderObj", 	sliderObj)
						.data("oldItems", 	oldItems)
						.data("newItems", 	newItems)
						.animate(ani, { 
							duration: sliderObj.speed,
							easing	: sliderObj.effect,
							complete: function() {
								if ($ul.data("sliderObj").onAfter) {
									$ul.data("sliderObj").onAfter($ul.data("oldItems"), $ul.data("newItems"), "next");
								}
								$ul.css(cal).find("li:lt("+$ul.data("numItems")+")").remove();
							}
						});

					//	auto-play
					$ul.trigger("pause").trigger("play", "next");
				})
				.bind("prev", function(e, sliderObj) {
					if ($ul.is(":animated")) return;


						 if (typeof(sliderObj) == 'undefined')	sliderObj = opts.prev;
						 if (typeof(sliderObj) == 'object') 	numItems  = sliderObj.items;
					else if (typeof(sliderObj) == 'number') {
						numItems  = sliderObj;
						sliderObj = opts.prev;
					}
					if (!numItems || typeof(numItems) != 'number') return;


					var oldItems = $("li", $ul);
					for (var a = 0; a < numItems; a++) {	
						$ul.prepend($($items[prevItem]).clone(true));
						if (--prevItem < 0) prevItem = totalItems-1;
						if (--nextItem < 0) nextItem = totalItems-1;
					}
					var newItems = $("li:lt("+opts.visibleItems+")", $ul);


					if (opts.direction == "right" ||
						opts.direction == "left"
					) {
						var pos = 'left',
							siz = itemWidth;
					} else {
						var pos = 'top',
							siz = itemHeight;
					}

					var css = {},
						ani = {};

					css[pos] = $ul.offset()[pos]-oldItems.offset()[pos] || -(siz * numItems);
					ani[pos] = 0;

					if (sliderObj.onBefore) {
						sliderObj.onBefore(oldItems, newItems, "prev");
					}

					$ul
						.data("sliderObj", 	sliderObj)
						.data("oldItems", 	oldItems)
						.data("newItems", 	newItems)
						.css(css)
						.animate(ani, { 
							duration: sliderObj.speed,
							easing	: sliderObj.effect,
							complete: function() {
								if ($ul.data("sliderObj").onAfter) {
									$ul.data("sliderObj").onAfter($ul.data("oldItems"), $ul.data("newItems"), "next");
								}
								$ul.find("li:gt("+(opts.visibleItems-1)+")").remove();
							}
						});

					//	auto-play
					$ul.trigger("pause").trigger("play", "prev");					
				})
				.bind("slideTo", function(e, n) {
					if (typeof(n) == 'string') {
						if (n.charAt(1) == '=') {
							a = n.substr(2).split(' ').join('');
								 if (n.charAt(0) == '+')		$ul.trigger("next", a);	
							else if (n.charAt(0) == '-')		$ul.trigger("prev", a);
							else try { console.log('carouFredSel: Not a valid string.'); } catch(err) {}
							return;

						} else n = parseInt(n);
					}
					if (typeof(n) == 'object') {
						a = -1;
						$items.each(function(m) {
							if (n == this || n == $(this)) a = m;
						});
						if (a == -1) {
							try { console.log('carouFredSel: Not a valid object.'); } catch(err) {}
							return;
						}
						n = a;
					}
					if (typeof(n) != 'number') {
						try { console.log('carouFredSel: Not a valid number.'); } catch(err) {}
						return;
					}

					var c = prevItem,
						t = totalItems;

					if (++c >= t) c = 0;

					if (n < 0) n += t;
					var a = n - c;
					if (a == 0) return;

						 if (a < t/2 && a > 0)			$ul.trigger("next", a);				//	vooruit binnen reeks
					else if (a < -(t/2))				$ul.trigger("next", t+a);			//	vooruit van eind naar begin
					else if (a > -(t/2) && a < 0)		$ul.trigger("prev", -a);			//	achteruit binnen reeks
					else								$ul.trigger("prev", t-Math.abs(a));	//	achteruit van begin naar eind
				});


			if (opts.auto.pauseOnHover && opts.autoPlay) {
				$ul.hover(
					function() { $ul.trigger("pause"); },
					function() { $ul.trigger("play", direction); }
				);
			}

			//	via prev- en/of next-buttons
			if (opts.next.button != null) {
				opts.next.button.click(function() {
					$ul.trigger("next"/* , opts.next */);
					return false;
				});
				if (opts.next.pauseOnHover && opts.autoPlay) {
					opts.next.button.hover(
						function() { $ul.trigger("pause"); },
						function() { $ul.trigger("play", direction); }
					);
				}
			}
			if (opts.prev.button != null) {
				opts.prev.button.click(function() {
					$ul.trigger("prev"/* , opts.prev */);
					return false;
				});
				if (opts.prev.pauseOnHover && opts.autoPlay) {
					opts.prev.button.hover(
						function() { $ul.trigger("pause"); },
						function() { $ul.trigger("play", direction); }
					);
				}
			}

			//	via auto-play
			$ul.trigger("play", direction);
		});
	}

	$.fn.carouFredSel.defaults = {
		visibleItems		: 4,
		autoPlay			: true,
		direction			: "right",
		scroll : {
			items				: 0,
			effect				: 'swing',
			speed				: 500,							
			pauseOnHover		: false,
			onBefore			: null,
			onAfter				: null
		}
	}
/*
//	Config for execution, do not uncomment
		
		//	auto takes over from 'scroll'
		auto : {
			pauseDuration		: 2500,

			items				: 0,
			effect				: 'swing',
			speed				: 500,
			pauseOnHover		: false,
			onBefore			: null,
			onAfter				: null
		},
		
		//	buttons takes over from 'scroll'
		buttons : {
			items				: 0,
			effect				: 'swing',
			speed				: 500,
			pauseOnHover		: false,
			onBefore			: null,
			onAfter				: null
		},
		
		//	prev takes over from 'buttons'
		prev : {
			button 				: null,

			items				: 0,
			effect				: 'swing',
			speed				: 500,
			pauseOnHover		: false,
			onBefore			: null,
			onAfter				: null
		},
		
		//	next takes over from 'buttons'
		next : {
			button 				: null,

			items				: 0,
			effect				: 'swing',
			speed				: 500,
			pauseOnHover		: false,
			onBefore			: null,
			onAfter				: null
		}
*/								
	
})(jQuery);