var DOM1;
window.onload = function()
{
	DOM1 = (typeof document.getElementsByTagName!='undefined');
	if (typeof checkconditions!='undefined') checkconditions();
	if (typeof template_onload!='undefined') template_onload();
	prepCellAdapters();
	document['onkeypress'] = checkEnter;
}

Array.prototype.push = function()
{
	var n = this.length >>> 0;
	for (var i = 0; i < arguments.length; i++) 
	{
		this[n] = arguments[i];
		n = n + 1 >>> 0;
	}
	this.length = n;
	return n;
};

Array.prototype.pop = function() {
	var n = this.length >>> 0, value;
	if (n) {
		value = this[--n];
		delete this[n];
	}
	this.length = n;
	return value;
};


//defined in group.php & question.php & survey.php, but a static function
function inArray(needle, haystack)
{
	for (h in haystack)
	{
		if (haystack[h] == needle) 
		{
			return true;
		}
	}
	return false;
} 

//defined in group.php & survey.php, but a static function
function match_regex(testedstring,str_regexp)
{
	// Regular expression test
	if (str_regexp == '' || testedstring == '') return false;
	pattern = new RegExp(str_regexp);
	return pattern.test(testedstring)
}

function cellAdapter(src)
{
	var eChild = null, eChildren = src.getElementsByTagName('INPUT');
	var curCount = eChildren.length;
	//This cell contains multiple controls, don't know which to set.
	if (eChildren.length > 1)
	{
		//Some cells contain hidden fields
		for (i = 0; i < eChildren.length; i++)
		{
			if ( eChildren[i].type == 'radio' && eChild == null)
				eChild = eChildren[i];
			else if ( eChildren[i].type == 'radio' && eChild != null)
			{
				//A cell with multiple radio buttons -- unhandled
				return;
			}
            
		}
	}
	else eChild = eChildren[0];

	if (eChild && eChild.type == 'radio')
	{
		eChild.checked = true;
		//Make sure the change propagates to the conditions handling mechanism
		if(eChild.onclick) eChild.onclick();
		if(eChild.onchange) eChild.onchange();
	}
}

function prepCellAdapters()
{
	if (!DOM1) return;
	var formCtls = document.getElementsByTagName('INPUT');
	var ptr = null;
	var foundTD = false;
	var donefocus = false;
	for (var i = 0; i < formCtls.length; i++)
	{
		ptr = formCtls[i];
		if (ptr.type == 'radio' || ptr.type == 'checkbox')
		{
			foundTD = false;
			while (ptr && !foundTD)
			{
				if(ptr.nodeName == 'TD')
				{
					foundTD = true;
					ptr.onclick = 
						function(){
							return cellAdapter(this);
						};
					continue;
				}
				ptr = ptr.parentNode;	
			}	
		}
		if (ptr.type != 'submit' && ptr.type != 'hidden' && donefocus == false)
		{
			ptr.focus();
			donefocus = true;
		}
	}
}

function addHiddenField(theform,thename,thevalue)
{
	var myel = document.createElement('input');
	myel.type = 'hidden';
	myel.name = thename;	
	theform.appendChild(myel);
	myel.value = thevalue;
}

function checkEnter(e){ //e is event object passed from function invocation
	var evt = e || event;
	var whi = 0;
	var counter = 1;
	
	whi = evt.which;
	//alert(evt.which);

	if (whi >= 49 && whi <= 57) //keys 1-9 select appropriate box
	{
		y = document.getElementsByTagName('input');
		for (var i = 0; i < y.length; i++)
		{
			a = y[i];
			if (a.type == 'checkbox' || a.type == 'radio')
			{
				if (counter == (whi - 48))
				{
					if (a.checked && a.type == 'checkbox')
					{
						a.checked = '';
					}
					else
					{
						a.checked = 'checked';
					}
				}
				counter++;
			}

		}
	
	}

	if (whi == 13)
	{
		document.forms[0].submit();
		return false;
	}

	return document.defaultAction;

}

