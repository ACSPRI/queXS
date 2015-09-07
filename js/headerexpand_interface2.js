function headerexpand()
{
	$(".headerexpand").css("top","35%");
	$(".content").css("height","63%");
	$(".content").css("top","37%");
	$(".box:not(.important)").css("display","");

}

function headercontract()
{
	$(".headerexpand").css("top","18%");
	$(".content").css("height","80%");
	$(".content").css("top","20%");
	$(".box:not(.important)").css("display","none");

}
