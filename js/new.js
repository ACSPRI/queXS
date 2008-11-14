function showHide(me,id)
{
	e = document.getElementById(id);
	if (me.checked == true)
	{
		e.style.display = 'inline';
	}
	else
	{
		e.style.display = 'none';
	}
		
}
