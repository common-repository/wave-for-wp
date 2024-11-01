/**
 * This file is part of the Codox Wave plugin and is released under the 
 * same license.
 * For more information please see wave-wp.php.
 * 
 * Copyright (c) 2017 Codox Inc. All rights reserved.
 */


(function(){
'use strict';

/*
 * tinymce plugin for overriding the buttons in tinymce
 * called post tinymce initialization 
 */

  if(typeof tinymce != "undefined") {
    tinymce.PluginManager.add( 'codox',
      function(editor, url) {

        editor.settings.toolbar1 = "formatselect,bold,italic,underline,strikethrough,blockquote,bullist,numlist,alignleft,aligncenter,alignright,alignjustify,link,unlink,undo,hr,superscript,subscript,image";
        editor.settings.toolbar2 = "fontselect,fontsizeselect,outdent,indent,pastetext,removeformat,charmap,wp_more,forecolor,table,wp_help,wp_code,emoticons";
        editor.settings.toolbar3 = '';
        editor.settings.toolbar4 = '';
        editor.settings.menubar = false;

        void 0;
      });
  }
}

)();
