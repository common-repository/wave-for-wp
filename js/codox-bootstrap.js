
/**
 * This file is part of the Codox Wave plugin and is released under the 
 * same license.
 * For more information please see wave-wp.php.
 * 
 * Copyright (c) 2017 Codox Inc. All rights reserved.
 */


jQuery(function($){

/*
 * Codox bootstrap loader
 */
  var codox = null; 

  function isPostEditing(base) {
    var post = '/wp-admin/post.php';

    var pathname = window.location.pathname;

    var isPost = (base === 'post') && (pathname.indexOf(post) !== -1);

    if(isPost)
      void 0;
    else
      void 0;

    return isPost;
  }

  function promptError() {
    tinymce.activeEditor.windowManager.open({
      title: `Codox Wave`,
      body: [{
        type: 'container',
        html: `<p>Our apologies, real-time co-editing cannot be started due to server issues. We'll be back shortly</p>`
      }],
      buttons: [{
        text: 'OK',
        subtype: 'primary',
        onclick: function() {
          tinymce.activeEditor.windowManager.close();
        }
      }]
    });
  }

  window.addEventListener('editorReady', function(e){
    var app = e.detail;

    void 0;
    void 0;
    void 0;

    if(!isPostEditing(wp_vars.base))
      return;

    if (codox === null && typeof (Codox) !== 'undefined') {
      //create codox layer
      codox = new Codox();

      var config = {
        app: app,
        apiKey: wp_vars.token,
        // sessionId: app + '_' + wp_vars.token + '_' + wp_vars.postID,
        docId: wp_vars.postID,
        // user: wp_vars.username,
        username: wp_vars.username,
        domain: wp_vars.domain,
        editorInstance: tinymce.activeEditor,
      }; 

      codox.start(config);

      // callPhp('codox_config', config);
    }
    else {
      promptError();
    }
  });
});



