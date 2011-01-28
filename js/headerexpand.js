//Apply to all items of class header
$(document).ready(function(){
		$(".header").hover(
			//function on mouse over
			function(){
				$(".header").css("height","30%");
				$(".content").css("height","70%");
				$(".content").css("top","30%");
				$(".box:not(.important)").css("display","");
			},

			//function on mouse out
			function(){
				$(".header").css("height","5%");
				$(".content").css("height","95%");
				$(".content").css("top","5%");
				$(".box:not(.important)").css("display","none");
			}
			);

		});
