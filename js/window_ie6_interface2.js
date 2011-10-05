function LinkUp(element)
{
	var number = document.getElementById(element).selectedIndex;
	location.href = document.getElementById(element).options[number].value;
}

function openParent(get)
{
	parent.location.href = 'index_interface2.php?' + get;
}


function openParentNote(get)
{
	parent.location.href = 'index_interface2.php?note=' + document.getElementById('note').value + '&' + get;
}

function openParentObject(oid,get)
{
	var a = parent.document.getElementById(oid);
	if (a)
	{
		a.src = get;
	}

}

function toggleRec(text,link,classes)
{
	var a = parent.document.getElementById('reclink');
	if (a)
	{
		a.innerHTML = text;
		a.href = "javascript:poptastic('" + link + "');";
		a.className = classes;
	}
}
