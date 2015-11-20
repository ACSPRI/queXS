//Apply to all items of class header
$(document).ready(function(){
		$("#headerexpandimage").click(headertogglemanual);

		});

function headerforcecontract()
{
	$("#headerexpandimage").attr('class',"fa fa-lg fa-fw fa-toggle-down ");
	headercontract();
}

function headertogglemanual()
{
	if ($("#headerexpandimage").attr('class') == 'fa fa-lg fa-fw fa-toggle-up')
	{
		$("#headerexpandimage").attr('class',"fa fa-lg fa-fw fa-toggle-down");
		headercontract();
	}
	else
	{
		$("#headerexpandimage").attr('class',"fa fa-lg fa-fw fa-toggle-up");
		headerexpand();
	}

}
