/*  This file is part of Lime Survey Template : SkeletonQuest

  Copyright (C) 2010-2013 Denis Chenu for http://sondages.pro
  Distributed under GPL 3 licence
  Distributed under MIT licence
  Inspiration by Skeleton V1.1, Copyright 2011, Dave Gamache, http://www.getskeleton.com under open-source MIT license
  Inspiration by Spip-dist http://www.spip.net/ , Collectif SPIP distributed under GPL licence
  Inspiration by THE CSS NINJA http://cssn.in/ja/023, Ryan Seddon
  Inspiration by Bootstrap http://getbootstrap.com/ under Apache License.
  Inspiration by jquery mobile http://jquerymobile.com/ under Dual licensed under the MIT or GPL Version 2 licenses.
  Tango Icon Project http://tango.freedesktop.org/ distributed under Public Domain
*/

$(document).ready(function(){
    /* Put other input inside label pre 120814 */
//    $('.multiple-opt input.text').each(function(){
//        $(this).parent('li').addClass('other');
//        $(this).appendTo($(this).parent('li').children('label'));
//    });
    hovercolumn();
    tableinput();
    addClassEmpty();
    movePrevButton();
    // Opera mini labelling touch
    operamini = window.operamini && ({}).toString.call( window.operamini ) === "[object OperaMini]";
    if(operamini){
        $('label > input:checkbox, label > input:radio, input:radio + label, input:checkbox + label').bind('click', function(){
        });
    }
})

function navbuttonsJqueryUi(){
    // Just deactivate default jquery-ui button
}
function movePrevButton(){
    if ((screen.width<440)) {
        $('#moveprevbtn').insertAfter('#movenextbtn');
        $('<br />').insertAfter('#movenextbtn');
    }
}
// Replace common alert with jquery-ui dialog
function jalert(text) {
	var $dialog = $('<div></div>')
		.html(text)
		.dialog({
			title: '',
			dialogClass: 'alerte',
			buttons: { "Ok": function() { $(this).dialog("close"); } },
			modal: true
		});
	$dialog.dialog('open');
}
// Uncomment this part to replace default alert
/*function alert(text) {
	jalert(text);
}*/
// Add empty class to input text and textarea
function addClassEmpty(){
      $('.answers-wrapper input.text[value=""]').addClass('empty');
      $('.answers-wrapper input[type=text][value=""]').addClass('empty');
      $('.answers-wrapper textarea').each(function(index) {
        if ($(this).val() == ""){
          $(this).addClass('empty');
        }
      });
    $("input.text,input[type=text],textarea").live("blur", function(){ 
      if ($(this).val() == ""){
        $(this).addClass('empty');
      }else{
        $(this).removeClass('empty');
      }
    });
}
/* Adapt column hover */
function hovercolumn(){
    $(".question-wrapper:not(.array-flexible-duel-scale) .answers-wrapper table.question").delegate('tbody td','mouseover mouseleave', function(e) {
        if (e.type == 'mouseover') {
          $(this).closest("table.question").find("col").eq($(this).index()).addClass("hover");
          $(this).closest("table.question").find("thead tr").children().eq($(this).index()).addClass("hover");
        }
        else {
          $(this).closest("table.question").find("col").eq($(this).index()).removeClass("hover");
          $(this).closest("table.question").find("thead tr").children().eq($(this).index()).removeClass("hover");
        }
    });
    $(".array-flexible-duel-scale .answers-wrapper table.question").delegate('tbody td','mouseover mouseleave', function(e) {
        if (e.type == 'mouseover') {
          $(this).closest("table.question").find("col").eq($(this).index()).addClass("hover");
          $(this).closest("table.question").find("thead tr:not(.groups)").children().eq($(this).index()).addClass("hover");
        }
        else {
          $(this).closest("table.question").find("col").eq($(this).index()).removeClass("hover");
          $(this).closest("table.question").find("thead tr:not(.groups)").children().eq($(this).index()).removeClass("hover");
        }
    });
}
function tableinput(){
    $(".question-wrapper:not(.array-flexible-duel-scale) .answers-wrapper table.question").delegate('input','focusin focusout', function(e) {
        activeindex=$(this).parents('td').index();
        if (e.type == 'focusin') {
          $(this).closest("td").addClass("focus");
          $(this).closest("tr").addClass("focus");
          $(this).closest("table.question").find("col").eq(activeindex).addClass("focus");
          $(this).closest("table.question").find("thead tr:not(.groups)").children().eq(activeindex).addClass("focus");
        }
        else {
          $(this).closest("td").removeClass("focus");
          $(this).closest("tr").removeClass("focus");
          $(this).closest("table.question").find("col").eq(activeindex).removeClass("focus");
          $(this).closest("table.question").find("thead tr:not(.groups)").children().eq(activeindex).removeClass("focus");
        }
    });
}


