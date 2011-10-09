function headerexpand()
{
	$(".header").css("height","38%");
	$(".content").css("height","60%");
	$(".content").css("top","40%");
	$(".box:not(.important)").css("display","");
//	$(".item_2_half_height").removeClass("item_2_half_height").addClass("item_2_full_height");
	$(".item_3_half_height").removeClass("item_3_half_height").addClass("item_3_full_height");
}

function headercontract()
{
	$(".header").css("height","13%");
	$(".content").css("height","80%");
	$(".content").css("top","15%");
	$(".box:not(.important)").css("display","none");
//	$(".item_2_full_height").removeClass("item_2_full_height").addClass("item_2_half_height");
	$(".item_3_full_height").removeClass("item_3_full_height").addClass("item_3_half_height");
	
}
