function showHide(element,me)
{
	if (document.getElementById(element).style.display == "none" || !document.getElementById(element).style.display)
	{
		document.getElementById(element).style.display = 'inline';
		document.getElementById(me).innerHTML = 'Hide details';
	}
	else
	{
		document.getElementById(element).style.display = 'none';
		document.getElementById(me).innerHTML = 'Show details';
	}
		
}

