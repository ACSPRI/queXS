//Code based on: http://www.kuba.co.uk/blog.php?blog_id=15
CKEDITOR.plugins.add( 'tokens',
{   
   requires : ['richcombo'], //, 'styles' ],
   init : function( editor )
   {
      var config = editor.config,
         lang = editor.lang.format;

      // Gets the list of tags from the settings.
      var tags = []; //new Array();
      //this.add('value', 'drop_text', 'drop_label');

	<?php
		include_once("../../../../config.inc.php");
		include_once("../../../../lang.inc.php");
		
		$tags = array();
		$tags[] = array("{PeriodOfDay}",T_("Period of day (e.g. Morning)"));
		$tags[] = array("{Respondent:firstName}",T_("Respondent first name"));
		$tags[] = array("{Respondent:lastName}",T_("Respondent last name"));
		$tags[] = array("{Operator:firstName}",T_("Operator first name"));
		$tags[] = array("{Operator:lastName}",T_("Operator last name"));
		$tags[] = array("{Sample:var}",T_("Sample value (replace var)"));

		$tn = 0;
		foreach($tags as $t)
		{
			print "tags[$tn]=[\"{$t[0]}\",\"{$t[1]}\",\"{$t[1]}\"];\n";
			$tn++;
		}
	?>

      // Create style objects for all defined styles.

      editor.ui.addRichCombo( 'tokens',
         {
            label : "<?php echo T_("Insert tokens"); ?>",
            title :"<?php echo T_("Insert tokens"); ?>",
            voiceLabel : "<?php echo T_("Insert tokens"); ?>",
            className : 'cke_format',
            multiSelect : false,

            panel :
            {
               css : [ config.contentsCss, CKEDITOR.getUrl( editor.skinPath + 'editor.css' ) ],
               voiceLabel : lang.panelVoiceLabel
            },

            init : function()
            {
               this.startGroup( "<?php echo T_("Insert Tokens"); ?>" );
               //this.add('value', 'drop_text', 'drop_label');
               for (var this_tag in tags){
                  this.add(tags[this_tag][0], tags[this_tag][1], tags[this_tag][2]);
               }
            },

            onClick : function( value )
            {         
               editor.focus();
               editor.fire( 'saveSnapshot' );
               editor.insertHtml(value);
               editor.fire( 'saveSnapshot' );
            }
         });
   }
});
