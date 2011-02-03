//Apply to all items of class header
$(document).ready(function(){
		$(".header").hover(
			//function on mouse over
			headerexpand,

			//function on mouse out
			headercontract
			);

		});
