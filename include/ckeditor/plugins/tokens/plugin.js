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
      tags[0]=["{PERIODOFDAY}","Period of day (e.g. Morning)","Period of day (e.g. Morning)"];
      tags[1]=["{RESPONDENT:FIRSTNAME}","Respondent first name","Respondent first name"];
      tags[2]=["{RESPONDENT:LASTNAME}","Respondent last name","Respondent last name"];
      tags[3]=["{OPERATOR:FIRSTNAME}", "Operator first name","Operator first name"];
      tags[4]=["{OPERATOR:LASTNAME}","Operator last name","Operator last name"];
      tags[5]=["{SAMPLE:VAR}","Sample value (replace VAR)","Sample value (replace VAR)"];

      // Create style objects for all defined styles.

      editor.ui.addRichCombo( 'tokens',
         {
            label : "Insert tokens",
            title :"Insert tokens",
            voiceLabel : "Insert tokens",
            className : 'cke_format',
            multiSelect : false,

            panel :
            {
               css : [ config.contentsCss, CKEDITOR.getUrl( editor.skinPath + 'editor.css' ) ],
               voiceLabel : lang.panelVoiceLabel
            },

            init : function()
            {
               this.startGroup( "Insert Tokens" );
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
