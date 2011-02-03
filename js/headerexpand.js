function headerexpand()
{
	$(".header").css("height","30%");
	$(".content").css("height","70%");
	$(".content").css("top","30%");
	$(".box:not(.important)").css("display","");
}

function headercontract()
{
	$(".header").css("height","5%");
	$(".content").css("height","95%");
	$(".content").css("top","5%");
	$(".box:not(.important)").css("display","none");

}
