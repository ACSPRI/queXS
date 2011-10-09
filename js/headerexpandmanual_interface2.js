//Apply to all items of class header
$(document).ready(function(){
		$("#headerexpandimage").click(headertogglemanual);

		});

function headerforcecontract()
{
	$("#headerexpandimage").attr('src',"./images/arrow-down-2.jpg");
	headercontract();
}

function headertogglemanual()
{
	if ($("#headerexpandimage").attr('src') == './images/arrow-up-2.jpg')
	{
		$("#headerexpandimage").attr('src',"./images/arrow-down-2.jpg");
		headercontract();
	}
	else
	{
		$("#headerexpandimage").attr('src',"./images/arrow-up-2.jpg");
		headerexpand();
	}

}
