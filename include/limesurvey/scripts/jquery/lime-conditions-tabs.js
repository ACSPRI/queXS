function populateCanswersSelect(evt) {
	var fname = $('#cquestions').val();

	// empty the canswers Select
	$('#canswers option').remove();
	var Keys = new Array();
	// store the indices in the Fieldnames array (to find codes and answers) where fname is found
	for (var i=0;i<Fieldnames.length;i++) {
		if (Fieldnames[i] == fname) {
			Keys[Keys.length]=i;
		}
	}
	for (var i=0;i<QFieldnames.length;i++) {
		if (QFieldnames[i] == fname) {
			$('#cqid').val(Qcqids[i]);
			if (Qtypes[i] == 'P' || Qtypes[i] == 'M')
			{
				$('#conditiontarget').tabs('enable', 0);
				$('#conditiontarget').tabs('select', 0);
				$('#conditiontarget').tabs('disable', 1);
				$('#conditiontarget').tabs('disable', 2);
				$('#conditiontarget').tabs('disable', 3);
				$('#conditiontarget').tabs('disable', 4);
				if ($('#method').val() != '==' || $('#method').val() != '!=')
				{
					$('#method').val('==');
				}
				$('#method').find('option').each( function() {
					if ($(this).val() != '==' && $(this).val() != '!=')
					{
						$(this).attr('disabled','disabled');
					}
				});
			}
			else
			{
				$('#conditiontarget').tabs('enable', 0);
				$('#conditiontarget').tabs('enable', 1);
				$('#conditiontarget').tabs('enable', 2);
				if (!isAnonymousSurvey) $('#conditiontarget').tabs('enable', 3);
				$('#conditiontarget').tabs('enable', 4);
				selectTabFromOper();
				$('#method').find('option').each( function() {
					$(this).attr('disabled','')
				});
			}
		}
	}

	for (var i=0;i<Keys.length;i++) {
		var optionSelected = false;
		// If we are at page load time, then we may know which option to select
		if (evt === null)
		{ // Let's read canswersToSelect and check if we should select the option
			var selectedOptions = $('#canswersToSelect').val().split(';');
			for (var j=0;j<selectedOptions.length;j++) {
				if (Codes[Keys[i]] == selectedOptions[j])
				{
					optionSelected = true;
				}
			}
		}
		document.getElementById('canswers').options[document.getElementById('canswers').options.length] = new Option(Answers[Keys[i]], Codes[Keys[i]],false,optionSelected);
	}
}

function selectTabFromOper() {
	var val = $('#method').val();
	if(val == 'RX') {
		$('#conditiontarget').tabs('enable', 4);
		$('#conditiontarget').tabs('select', '#REGEXP');
		$('#conditiontarget').tabs('disable', 0);
		$('#conditiontarget').tabs('disable', 1);
		$('#conditiontarget').tabs('disable', 2);
		$('#conditiontarget').tabs('disable', 3);
	}
	else {
		$('#conditiontarget').tabs('enable', 0);
		$('#conditiontarget').tabs('enable', 1);
		$('#conditiontarget').tabs('enable', 2);
		if (!isAnonymousSurvey) $('#conditiontarget').tabs('enable', 3);
		$('#conditiontarget').tabs('select', '#CANSWERSTAB');
		$('#conditiontarget').tabs('disable', 4);
	}
}

$(document).ready(function(){
	$('#conditiontarget').tabs({
		fx: {
			opacity: 'toggle',
       		     duration: 100
		}
	});

	$('#conditiontarget').bind('tabsselect', function(event, ui) {
		$('#editTargetTab').val('#' + ui.panel.id);	

	});

	$('#conditionsource').tabs({
		fx: {
			opacity: 'toggle',
       		     duration: 100
		}
	});

	$('#conditionsource').bind('tabsselect', function(event, ui) {
		$('#editSourceTab').val('#' + ui.panel.id);	

	});

	// disable RegExp tab onload (new condition)
	$('#conditiontarget').tabs('disable', 4);
	// disable TokenAttribute tab onload if survey is anonymous
	if (isAnonymousSurvey) $('#conditiontarget').tabs('disable', 3);
	if (isAnonymousSurvey) $('#conditionsource').tabs('disable', 1);

	$('#resetForm').click( function() {
		$('#canswers option').remove();
		selectTabFromOper();
		$('#method').find('option').each( function() {
			$(this).attr('disabled','')
		});
		
	});

	$('#conditiontarget').find(':input').change(
		function(evt)
		{
			$('#conditiontarget').find(':input').each(
				function(indx,elt)
				{
					if (elt.id != evt.target.id)
					{
						if ($(elt).attr('type') == 'select-multiple' || 
							$(elt).attr('type') == 'select-one' ) {
							$(elt).find('option:selected').removeAttr("selected");
						}
						else {
							$(elt).val('');	
						}
					}
					return true;
				}
			);
		}
	);

	$('#conditionsource').find(':input').change(
		function(evt)
		{
			$('#conditionsource').find(':input').each(
				function(indx,elt)
				{
					if (elt.id != evt.target.id)
					{
						if ($(elt).attr('type') == 'select-multiple' || 
							$(elt).attr('type') == 'select-one' ) {
							$(elt).find('option:selected').removeAttr("selected");
						}
						else {
							$(elt).val('');	
						}
					}
					return true;
				}
			);
			if (evt.target.id == 'csrctoken')
			{
				$('#canswers option').remove();
			}
		}
	);

	// Select the condition target Tab depending on operator
	//selectTabFromOper($('#method').val());
	$('#method').change(selectTabFromOper);

	$('#cquestions').change(populateCanswersSelect);

	$('#csrctoken').change(function() {
		$('#cqid').val(0);
	});

	// At edition time, a hidden field gives the Tab that should be selected
	if ($('#editTargetTab').val() != '') {
		$('#conditiontarget').tabs('select', $('#editTargetTab').val());
	}
	// At edition time, a hidden field gives the Tab that should be selected
	if ($('#editSourceTab').val() != '') {
		$('#conditionsource').tabs('select', $('#editSourceTab').val());
	}
	
	// At edition time, if cquestions is set, populate answers
	if ($('#cquestions').val() != '') {
		populateCanswersSelect(null);
	}
	

});


