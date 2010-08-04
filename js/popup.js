function poptastic(url, title)
{
  var elem = jQuery("#inpage");
  if (elem.length > 0) {
    elem = jQuery(elem[0]);
    if (elem.dialog("option", "title") == title) {
      if (! elem.dialog("isOpen")) {
	elem.dialog("open");
      }
      return;
    } else {
      elem.dialog("close").dialog("destroy").remove();
    }
  }
  jQuery('<iframe id="inpage" src="'+ url +'" />').dialog({
    autoOpen: true,
    title: title,
    height: 700,
    width: 650,
    modal: true,
    autoResize: false,
    resizable: true,
    overlay: {
      opacity: 0.5,
      background: "white"
    }
  }).width(620);
}

function closePopup()
{
  jQuery("#inpage").dialog("close").dialog("destroy").remove();
}
