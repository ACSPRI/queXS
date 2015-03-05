/*
 * addrow.js - an example JavaScript program for adding a row of input fields
 * to an HTML form
 *
 * This program is placed into the public domain.
 *
 * The orginal author is Dwayne C. Litzenberger.
 *
 * THIS SOFTWARE IS PROVIDED AS-IS WITHOUT WARRANTY
 * OF ANY KIND, NOT EVEN THE IMPLIED WARRANTY OF
 * MERCHANTABILITY. THE AUTHOR OF THIS SOFTWARE,
 * ASSUMES _NO_ RESPONSIBILITY FOR ANY CONSEQUENCE
 * RESULTING FROM THE USE, MODIFICATION, OR
 * REDISTRIBUTION OF THIS SOFTWARE.
 *
 * Home page: http://www.dlitz.net/software/addrow/
 *
 * History:
 *  Version 2 - 2006-01-14  dlitz@dlitz.net
 *   - Add support for MSIE 6
 *   - Separate HTML and JavaScript into separate files
 *   - Tested on:
 *      + Konqueror 3.4.2
 *      + Microsoft Internet Explorer 5.00
 *      + Microsoft Internet Explorer 6.0
 *      + Mozilla Firefox 1.0.4 (via browsershots.org)
 *      + Mozilla Firefox 1.5
 *      + Opera 8.51
 *      + Safari 2.0 (via browsershots.org)
 *  Version 1
 *   - Initial release
 * 
 */

function addRow() {
    /* Declare variables */
    var elements, templateRow, rowCount, row, className, newRow, element;
    var i, s, t;
    
    /* Get and count all "tr" elements with class="row".    The last one will
     * be serve as a template. */
    if (!document.getElementsByTagName)
        return false; /* DOM not supported */
    elements = document.getElementsByTagName("tr");
    templateRow = null;
    rowCount = 0;
    for (i = 0; i < elements.length; i++) {
        row = elements.item(i);
        
        /* Get the "class" attribute of the row. */
        className = null;
        if (row.getAttribute)
            className = row.getAttribute('class')
        if (className == null && row.attributes) {    // MSIE 5
            /* getAttribute('class') always returns null on MSIE 5, and
             * row.attributes doesn't work on Firefox 1.0.    Go figure. */
            className = row.attributes['class'];
            if (className && typeof(className) == 'object' && className.value) {
                // MSIE 6
                className = className.value;
            }
        } 
        
        /* This is not one of the rows we're looking for.    Move along. */
        if (className != "row_to_clone")
            continue;
        
        /* This *is* a row we're looking for. */
        templateRow = row;
        rowCount++;
    }
    if (templateRow == null)
        return false; /* Couldn't find a template row. */
    
    /* Make a copy of the template row */
    newRow = templateRow.cloneNode(true);

    newRow.id = 'row-' + rowCount.toString();

    /* Change the form variables e.g. price[x] -> price[rowCount] */
    elements = newRow.getElementsByTagName("input");
    for (i = 0; i < elements.length; i++) {
        element = elements.item(i);
        s = null;
        s = element.getAttribute("name");
        if (s == null)
            continue;
        t = s.split("[");
        if (t.length < 2)
            continue;
        s = t[0] + "[" + rowCount.toString() + "]";
        element.setAttribute("name", s);
        //element.value = "";   /*   <-- this row deletes cloned tag values */
    }
    
    elements = newRow.getElementsByTagName("select");
    for (i = 0; i < elements.length; i++) {
        element = elements.item(i);
        s = null;
        s = element.getAttribute("name");
        if (s == null)
            continue;
        t = s.split("[");
        if (t.length < 2)
            continue;
        s = t[0] + "[" + rowCount.toString() + "]";
        element.setAttribute("name", s);
        element.value = "";
    }

    /* Add the newly-created row to the table */
    templateRow.parentNode.appendChild(newRow);
	
	/* init clockpicker for added row*/
	$('.clockpicker').clockpicker({
    autoclose: true
	});
    return true;
}

/* set ts=8 sw=4 sts=4 expandtab: */