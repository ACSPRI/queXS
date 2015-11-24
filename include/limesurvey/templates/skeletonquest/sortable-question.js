$(document).ready(function(){
    $(".question-wrapper.ranking").each(function(){
        if (!$(this).hasClass('dragDropRanking')){
            qnum =$(this).attr('id').substring(8);
            dragDropRank(qnum)
        }
    });
})

function dragDropRank(qID, choiceText, rankText) {
	
	if(!choiceText) {
		choiceText = $('#question'+qID+' td.label label').text();
	}
	if(!rankText) {
		rankText = $('#question'+qID+' td.output tr:first td:eq(1) strong').text();
	}
	// Find the number of LimeSurvey ranking inputs ("Maximum answers")
	var maxAnswer = $('#question'+qID+' td.output input.text').length;
    alerttxt=$("#question"+qID+" .em_num_answers").text();
	//Add a class to the question
	$('#question'+qID+'').addClass('dragDropRanking');
	
	// Hide the original question in LimeSurvey (so that we can replace with drag and drop)
	$('#question'+qID+' .rank').hide();

	// Turn off display of question (to override error checking built into LimeSurvey ranking question)
	//$('#display'+qID).val('off');

	// Add connected sortables elements to the question
	var htmlCode = '<table class="dragDropTable"> \
		<tbody> \
			<tr> \
				<td> \
					<span class="dragDropHeader choicesLabel">'+choiceText+'</span><br /> \
					<div class="ui-state-highlight dragDropChoices"> \
						<ul id="sortable1'+qID+'" class="connectedSortable'+qID+' dragDropChoiceList"> \
							<li>Choices</li> \
						</ul> \
					</div> \
				</td> \
				<td> \
					<span class="dragDropHeader rankingLabel">'+rankText+'</span><br /> \
					<div class="ui-state-highlight dragDropRanks"> \
						<ol id="sortable2'+qID+'" class="connectedSortable'+qID+' dragDropRankList"> \
							<li>Ranks</li> \
						</ol> \
					</div> \
				</td> \
			</tr> \
		</tbody> \
					</table>';

	$(htmlCode).insertAfter('#question'+qID+' table.rank');

	// Remove placeholder list items (have to have one item so that LimeSurvey doesn"t remove the <ul>)
	$('#sortable1'+qID+' li, #sortable2'+qID+' li').remove();

	// Load any previously-set values
	loadDragDropRank(qID);
	

	// Set up the connected sortable			
	$('#sortable1'+qID+', #sortable2'+qID+'').sortable({			
		connectWith: '.connectedSortable'+qID+'',
		placeholder: 'ui-sortable-placeholder',
		helper: 'clone',
		revert: 50,
		receive: function(event, ui) {
			if($('#sortable2'+qID+' li').length > maxAnswer) {
                alert(alerttxt);
				$(ui.sender).sortable('cancel');
			}
		},
		stop: function(event, ui) {
			$('#sortable1'+qID+'').sortable('refresh');
			$('#sortable2'+qID+'').sortable('refresh');
			updateDragDropRank(qID);
		}
	}).disableSelection();
	// Get the list of choices from the LimeSurvey question and copy them as items into the sortable choices list	
	$('#CHOICES_' + qID).children().each(function(index, Element) { 
		var liCode = '<li class="" id="choice'+qID+'_' + $(this).attr("value") + '">' + this.text + '</li>'
		$(liCode).appendTo('#sortable1'+qID+''); 
	});
	$('#sortable1'+qID+', #sortable2'+qID+'').css('min-height',$('#sortable1'+qID).height()+'px');
	
	// Allow users to double click to move to selections from list to list
	$('#sortable1'+qID+' li').live('dblclick doubletap', function() {
		if($('#sortable2'+qID+' li').length == maxAnswer) {
            alert(alerttxt);
			return false;
		}
		else {
			$(this).appendTo('#sortable2'+qID+'');
			$('#sortable1'+qID+'').sortable('refresh');
			$('#sortable2'+qID+'').sortable('refresh');
			updateDragDropRank(qID);
		}
	});
	$('#sortable2'+qID+' li').live('dblclick doubletap', function() {
		$(this).appendTo('#sortable1'+qID+'');
		$('#sortable2'+qID+'').sortable('refresh');
		$('#sortable1'+qID+'').sortable('refresh');
		updateDragDropRank(qID);
	});
}	

// This function copies the drag and drop sortable data into the LimeSurvey ranking list.
function updateDragDropRank(qID)
{
	// Reload the LimeSurvey choices select element
	var rankees = [];
	
	$('#sortable2'+qID+' li').each(function(index) {
		$(this).attr('value', index+1);
		// Get value of ranked item
		var liID = $(this).attr("id");
		liIDArray = liID.split('_');	
		// Save to an array 
		rankees[rankees.length] = { "r_name":$(this).text(),"r_value":liIDArray[1] };
	});	
	$('#question'+qID+' input[name^="RANK_"]').each(function(index) {
		if (rankees.length > index) {
			$(this).val(rankees[index].r_name);
			//alert (rankees[index].r_value);
			$(this).next('input').attr('value', rankees[index].r_value);
		} else {
			$(this).val('');
			$(this).next().val('');
		}
  	});
	
	// Reload the LimeSurvey choices select element (we need this for "Minimum answers" code)
	$('#question'+qID+' td.label select option').remove();
	$('#sortable1'+qID+' li').each(function(index) {
		var liText = $(this).text();
		var liID = $(this).attr('id');
		liIDArray = liID.split('_');
		var optionCode = '<option value="'+liIDArray[1]+'">'+liText+'</option>';
		$(optionCode).appendTo('#question'+qID+' td.label select');
	});
	// Hack for IE6
	if ($.browser.msie && $.browser.version.substr(0,1)<7) {
		$('#question'+qID+' select').show(); 	
		$('#question'+qID+' select').hide(); 
	}
//	 Call change function
	$('#question'+qID+' input').each(function(index) {
        checkconditions($(this).val(), $(this).attr('name'))
//		$(this).trigger('change');
  	});
    
}

// This function is called on page load to see if there are any items already ranked (from a previous visit
// or cached due to a page change.  If so, the already-ranked items are loaded into the sortable list
function loadDragDropRank(qID)
{
	var rankees = [];
	
	// Loop through each item in the built-in LimeSurvey ranking list looking for non-empty values
	$('#question'+qID+' input[name^="RANK_"]').each(function(index) {
		// Check to see if the current item has a value
		if ($(this).val()) {	
			// Item has a value - save to the array 
			// Use this element to contain the name and the next for the value (numeric)
			rankees[rankees.length] = { "r_name":$(this).val(),"r_value":$(this).next().val() };
		}
	});

	// Now that we have a list of all the pre-ranked items, populate the sortable list
	// Note that the items *won"t* appear in the main list because LimeSurvey has removed them.
	$.each(rankees, function(index, value) { 
		// Create the items in the sortable
		var liCode = '<li class="ui-state-default" id="choice'+qID+'_' + value.r_value + '">' + value.r_name + '</li>';
		$(liCode).appendTo('#sortable2'+qID+'');

	});
}

function dragDropRankImages(qID, choiceText, rankText) {
	if(!choiceText) {
		choiceText = $('#question'+qID+' td.label label').text();
	}
	if(!rankText) {
		rankText = $('#question'+qID+' td.output tr:first td:eq(1) strong').text();
	}
    dragDropRank(qID, choiceText, rankText);
	$('.connectedSortable'+qID+' li').each(function(i) {
		// Remove any text in the sortable choice or rank items
		$(this).text('');
		// Move the images into the appropriate sortable list item
		var liID = $(this).attr('id');
		liIDArray = liID.split('_');
		$('#question'+qID+' img#'+liIDArray[1]+'').appendTo(this); 
	});
	$('#sortable1'+qID+', #sortable2'+qID+'').css('min-height',$('#sortable1'+qID).height()+'px');
}
function dragDropRankAddImages(qID, choiceText, rankText) {
	if(!choiceText) {
		choiceText = $('#question'+qID+' td.label label').text();
	}
	if(!rankText) {
		rankText = $('#question'+qID+' td.output tr:first td:eq(1) strong').text();
	}
    dragDropRank(qID, choiceText, rankText);
	$('.connectedSortable'+qID+' li').each(function(i) {
		// Remove any text in the sortable choice or rank items
		$(this).html('<span class="img-sortable"></span><span class="text-sortable">'+$(this).text()+'</span>');
		// Move the images into the appropriate sortable list item
		var liID = $(this).attr('id');
		var imgContainer = $(this).find(".img-sortable");
		liIDArray = liID.split('_');
		$('#question'+qID+' img#'+liIDArray[1]+'').appendTo(imgContainer); 
	});
	$('#sortable1'+qID+', #sortable2'+qID+'').css('min-height',$('#sortable1'+qID).height()+'px');
}

//based on blog post that I saw here: http://www.sanraul.com/2010/08/01/implementing-doubletap-on-iphones-and-ipads/
(function($){
    $.fn.doubletap = function(fn) {
        return fn ? this.bind('doubletap', fn) : this.trigger('doubletap');
    };

    $.attrFn.doubletap = true;

    $.event.special.doubletap = {
        setup: function(data, namespaces){
            $(this).bind('touchend', $.event.special.doubletap.handler);
        },

        teardown: function(namespaces){
            $(this).unbind('touchend', $.event.special.doubletap.handler);
        },

        handler: function(event){
            var action;

            clearTimeout(action);

            var now       = new Date().getTime();
            //the first time this will make delta a negative number
            var lastTouch = $(this).data('lastTouch') || now + 1;
            var delta     = now - lastTouch;
            var delay     = delay == null? 500 : delay;

            if(delta < delay && delta > 0){
                // After we detct a doubletap, start over
                $(this).data('lastTouch', null);

                // set event type to 'doubletap'
                event.type = 'doubletap';

                // let jQuery handle the triggering of "doubletap" event handlers
                $.event.handle.apply(this, arguments);
            }else{
                $(this).data('lastTouch', now);

                action = setTimeout(function(evt){
                    // set event type to 'doubletap'
                    event.type = 'tap';

                    // let jQuery handle the triggering of "doubletap" event handlers
                    $.event.handle.apply(this, arguments);

                    clearTimeout(action); // clear the timeout
                }, delay, [event]);
            }
        }
    };
})(jQuery);


