// $Id: saved.js 9330 2010-10-24 22:23:56Z c_schmitz $

$(document).ready(function(){
          handle=$('.tabsinner').tabs(
          {
               show: loadHTMLEditor
          });
});

/**
* This function loads each FCKeditor only when the tab is clicked and only if it is not already loaded
*/
function loadHTMLEditor(event, ui) 
{ 
   if (typeof ui.panel.selector != 'undefined')
   {
       sSelector=ui.panel.selector;
   }
   else
   {
       sSelector='#'+ui.panel.id;
   }
   if ($(sSelector+' iframe').size()==0)
   {
        sCKEditorInstanceName='oFCKeditor_'+$(sSelector+' textarea').attr('id').replace(/-/i, "_");
        eval("if (typeof "+sCKEditorInstanceName+" != 'undefined')"+sCKEditorInstanceName+".ReplaceTextarea();");
   }
}

function fillin(tofield, fromfield)
{
    if (confirm(sReplaceTextConfirmation)) 
    {
        if (document.getElementById(tofield).readOnly == false)
        {   
            $('#'+tofield).val($('#'+fromfield).val());    
        }
        updateCKeditor(tofield,$('#'+fromfield).val());

    }
}