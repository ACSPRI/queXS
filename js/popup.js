var newwindow;
function poptastic(url)
{
	newwindow=window.open(url,'name','height=600,width=350,resizable=yes,scrollbars=yes,toolbar=no,status=no');
	if (window.focus) {newwindow.focus()}
}
