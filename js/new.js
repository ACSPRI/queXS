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

function show(me,id)
{
	e = document.getElementById(id);
	e.style.display = 'inline';
		
}
function hide(me,id)
{
	e = document.getElementById(id);
	e.style.display = 'none';
}
