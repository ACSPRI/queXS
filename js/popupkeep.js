var newwindow;
function poptastic(url)
{
	newwindow=window.open('','name','height=600,width=350,resizable=yes,scrollbars=yes,toolbar=no,status=no');

	if (newwindow.closed || (! newwindow.document.URL) || (newwindow.document.URL.indexOf("about") == 0))
		newwindow.location=url;
	else
		newwindow.focus();

	return false;
}

