/**
 * This file is part of the Codox Wave plugin and is released under the 
 * same license.
 * For more information please see wave-wp.php.
 * 
 * Copyright (c) 2017 Codox Inc. All rights reserved.
 */

(function(){
'use strict'

/*
 * Updates CKEditor  allowed button set
 */
const CKEditorToolbarGroups = [
  { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
  { name: 'colors', groups: [ 'colors' ] },
  { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
  { name: 'links', groups: [ 'links' ] },
  { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
  { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
  { name: 'insert', groups: [ 'insert' ] },
  { name: 'styles', groups: [ 'styles' ] },
  { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
  { name: 'forms', groups: [ 'forms' ] },
  '/',
  '/',
  { name: 'tools', groups: [ 'tools' ] },
  { name: 'others', groups: [ 'others' ] },
  { name: 'about', groups: [ 'about' ] }
];

const CKEditorRemoveButtons = 'Source,Save,NewPage,Preview,Print,Templates,PasteFromWord,Redo,Form,Checkbox,Radio,Button,HiddenField,BidiLtr,BidiRtl,Anchor,Flash,Iframe,About,ShowBlocks,Maximize,CreateDiv,SelectAll,CopyFormatting';


var intervalTimer = window.setInterval(function(evt){

  if (typeof CKEDITOR !== 'undefined'){
    window.clearInterval(intervalTimer);

    void 0;
    void 0;
    void 0;


    //this is invoked once when ckeditor plugin initially
    //loads, one more time when we replace it with 
    //a new instance with customized button set,
    //and everytime user sw
    CKEDITOR.on('instanceReady', function(evt) {
      void 0;

      var ckeditor = evt.editor;

      if (_.isEqual(CKEditorToolbarGroups, ckeditor.config.toolbarGroups) && CKEditorRemoveButtons === ckeditor.config.removeButtons) {
        var event = new CustomEvent("editorReady", {detail: 'wordpress-ckeditor'});
        window.dispatchEvent(event);
        return;
      }

      // the implementations follows the discussion in https://stackoverflow.com/questions/12531002/change-ckeditor-toolbar-dynamically
      ckeditor.destroy();
      CKEDITOR.config.toolbarGroups = CKEditorToolbarGroups;
      CKEDITOR.config.removeButtons = CKEditorRemoveButtons;

      CKEDITOR.config.toolbar = 'Basic';
      CKEDITOR.replace('content', CKEDITOR.config);


      jQuery('.wp-editor-tabs').remove();


    }); 
  }
}, 200);
})();
