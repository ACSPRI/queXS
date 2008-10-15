function LinkUp(element) 
{
	var number = document.getElementById(element).selectedIndex;
	location.href = document.getElementById(element).options[number].value;
}

function openParent(get)
{
	window.opener.top.location.href = 'index.php?' + get;
	top.close();
}


function openParentNote(get)
{
	window.opener.top.location.href = 'index.php?note=' + document.getElementById('note').value + '&' + get;
	top.close();
}

function openParentObject(oid,get)
{
	var a = window.opener.top.document.getElementById(oid);
	if (a)
	{
		var clone = a.cloneNode(true);
		var pnode = a.parentNode;
		clone.data = get;
		pnode.removeChild(a);
		pnode.appendChild(clone);
	}

}

function toggleRec(text,link,classes)
{
	var a = window.opener.document.getElementById('reclink');
	if (a)
	{
		a.innerHTML = text;
		a.href = "javascript:poptastic('" + link + "');";
		a.className = classes;
	}
}
