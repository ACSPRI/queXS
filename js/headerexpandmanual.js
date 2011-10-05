//Apply to all items of class header
$(document).ready(function(){
		$("#headerexpandimage").click(headertogglemanual);

		});

function headerforcecontract()
{
	$("#headerexpandimage").attr('src',"./images/arrow-down-2.png");
	headercontract();
}

function headertogglemanual()
{
	if ($("#headerexpandimage").attr('src') == './images/arrow-up-2.png')
	{
		$("#headerexpandimage").attr('src',"./images/arrow-down-2.png");
		headercontract();
	}
	else
	{
		$("#headerexpandimage").attr('src',"./images/arrow-up-2.png");
		headerexpand();
	}

}
