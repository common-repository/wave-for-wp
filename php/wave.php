<?php
/**
 * This file is part of the Codox Wave plugin and is released under the 
 * same license.
 * For more information please see wave-wp.php.
 * 
 * Copyright (c) 2017 Codox Inc. All rights reserved.
 */

/*
 * Logging function
 */

class CDX_Wave 
{
  public function __construct()
  {
    /*
     * Initialization, set up filters and actions
     */

    add_filter('plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2);
    
    add_action( 'activated_plugin', array($this, 'activation_redirect'));

    add_action('admin_notices', array($this, 'admin_notices'));

    // Remove trash and delete related actions and buttons from page and post list
    add_action( 'admin_head-edit.php', array($this, 'hide_delete' ));
    add_filter( 'post_row_actions', array($this, 'remove_delete_action'), 10, 2 );
    add_filter( 'page_row_actions', array($this, 'remove_delete_action'), 10, 2 );
    add_filter( 'bulk_actions-edit-post', array($this, 'remove_delete_action'), 10, 2 );
    add_filter( 'bulk_actions-edit-page', array($this, 'remove_delete_action'), 10, 2 );

    // Remove 'move to trash' menu from publish options in edit post page
    add_action('admin_head-post.php', array($this, 'hide_trash_actions'), 10, 2);

    add_action( 'before_delete_post', array($this, 'on_before_delete_post' ));

    add_action('load-post.php', array($this, 'remove_post_locked'));
    add_action('load-edit.php', array($this, 'remove_post_locked'));

    add_action('admin_print_scripts', array( $this, 'add_codox_js'));
    add_action('admin_print_styles', array( $this, 'add_codox_css'));
    
    //ckeditor coediting is disabled in the current release
    //add_action('ckeditor_external_plugins', array( $this, 'start_co_ckeditor'));

    add_filter( 'tiny_mce_before_init', array( $this, 'replace_wp_mcelink' ));
    add_filter( 'tiny_mce_before_init', array( $this, 'set_tinymce_init_callback' ));
    add_filter( 'tiny_mce_before_init', array( $this, 'add_codox_tinymce_plugin' ));
    add_action( 'after_wp_tiny_mce', array( $this, 'replace_inline_link' ));
    add_action( 'after_wp_tiny_mce', array( $this, 'insert_codox_tinymce_plugin_script' ));

    add_filter( 'wp_default_editor', create_function('', 'return "tinymce";') );

    //this filter is for demo recording only
    add_filter( 'get_sample_permalink_html', array($this, 'remove_permalink') );

    // change the set of buttons to be shown on tinymce toolbar
    // https://www.alexgeorgiou.gr/add-remove-buttons-visual-composer-tinymce-editor/
  }

  function remove_permalink( $return ) {
      $return = '';

      return $return;
  }

  /*
   * Remove 'move to trash' link from 'publish' menu in edit post page
   */
  function hide_trash_actions() 
  {
      echo "<style>
        #delete-action {
          display: none;
        }
        </style>";

  }

  /*
   * Remove 'Empty Trash' button from post list
   */
  function hide_delete()
  {
    if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) 
    {
      echo "<style>
        #delete_all {
          display: none;
        }
        </style>";
    }
  }

  /*
   * Remove the 'trash' and 'delete' actions from post list, pages list, and drop down bulk action
   */
  function remove_delete_action( $actions , $post = null) 
  {
    if( isset($actions['trash']) )
      unset( $actions['trash']);

    if( isset($actions['delete']) )
      unset( $actions['delete']);

    return $actions;
  }


  /*
   * Display admin notices
   */
  function admin_notices() {

    //Display admin notice bar on all pages except Wave settings when APIKey is missing
    $screen = get_current_screen();
    $apiKey = $this->get_wave_token();
    log_me('admin_notices apiKey: ' . $apiKey);

    if ((!$apiKey || $apiKey == '' ) && current_user_can( 'manage_options' ) && $screen->id != WAVE_SETTINGS) {
      log_me('screen is ' . json_encode($screen));

        printf( '
          <div class="notice notice-error">
            <p>
              <a href="%1$s">Complete</a> Wave integration now to enable real-time collaboration!
            </p>
          </div>', 
        admin_url( WAVE_SETTINGS_PAGE)); 
    }

    //Display admin notice when ckeditor plugin deactivated by our plugin
    if ($this->get_ckeditor_deactivated() == 'true') {
      $this->set_ckeditor_deactivated('false');
      printf( '
        <div class="notice notice-warning is-dismissible">
          <p>
            Sorry, the current <b>Wave</b> plugin does not include the support for co-editing with CKEditor. <b>CKEditor for WordPress</b> plugin has been deactivated.
          </p>
        </div>');
    }
   }

  function activation_redirect($plugin) {
    $ckeditor = 'ckeditor-for-wordpress/ckeditor_wordpress.php';
    $isWavePlugin = $plugin == cdx_get_plugin_basename();

    if( ($isWavePlugin && is_plugin_active($ckeditor)) || $plugin == $ckeditor ) {
      $this->set_ckeditor_deactivated('true');
      deactivate_plugins($ckeditor);
    }

    if ($isWavePlugin)
      exit(wp_redirect(admin_url(WAVE_SETTINGS_PAGE)));
  }

  function get_wave_option() {
    return get_option( WAVE_OPTION_NAME, array(WAVE_OPTION_TOKEN =>'', WAVE_OPTIONS_CKEDITOR_DEACTIVATED => 'asdf') );
  }

  function get_wave_token() {
    $options = $this->get_wave_option();
    if (isset ($options[WAVE_OPTION_TOKEN] ))
      return $options[WAVE_OPTION_TOKEN];

    return '';
  }

  function get_wp_username() {
    $current_user = wp_get_current_user();
    return $current_user->user_login;
  }

  function get_postID() {
    $post = get_post();
    return $post->ID;
  }

  function get_domain() {
    return home_url();
  }

  function get_ckeditor_deactivated() {
    $options = $this->get_wave_option();
    if(isset ($options[WAVE_OPTIONS_CKEDITOR_DEACTIVATED]) )
      return $options[WAVE_OPTIONS_CKEDITOR_DEACTIVATED];

    return 'false';
  }

  function set_ckeditor_deactivated($value) {
    $options = $this->get_wave_option();
    $new_options = array(WAVE_OPTIONS_CKEDITOR_DEACTIVATED => $value);
    $res = update_option(WAVE_OPTION_NAME, $new_options);
  }

  /*
   * Helper function to bootstrap wave 
   */
  function get_wave_parameters() {
    if (is_null(get_post()))
      return 0;

    return array(
      'username' => $this->get_wp_username(),
      'base' => get_current_screen()->base,
      'token' => $this->get_wave_token(),
      'postID' => $this->get_postID(),
      'domain' => $this->get_domain(),
    );
  }

  /*
   * Load all javascript files
   */
  function add_codox_js() {
    if(!$this->is_post_or_page()) return;

    $param = $this->get_wave_parameters();

    if($param == 0)
      return;

    wp_register_script( 'codox-loader-script', plugins_url( 'js/codox-bootstrap.js', dirname(__FILE__)), array( 'jquery', 'underscore'));

    wp_register_script( 'codox-script', WAVE_CLIENT_JS_URL . '?apiKey=' . $this->get_wave_token() . '&domain=' . $this->get_domain() . '&app=wordpress');

    wp_localize_script('codox-loader-script', 
      'wp_vars', 
      $param
    );    

    wp_enqueue_script('codox-loader-script');
    wp_enqueue_script('codox-script');
  }

  /*
   * Load css
   */
  function add_codox_css() {
    if(!$this->is_post_or_page()) return;

    wp_register_style('codox-style', WAVE_CLIENT_CSS_URL);

    wp_enqueue_style('codox-style');
  }

  /*
   * Load script to customize CKEditor
   */
  function start_co_ckeditor() {
    if(!$this->is_post_or_page()) return;

    remove_action('ckeditor_external_plugins', array($this, 'start_co_ckeditor'));

    printf( 
      '<script type="text/javascript" src="%s"></script>', 
      plugins_url( '../js/codox-ckeditor.js', __FILE__ ) );
  }

  /*
   * Add plugin to reconfigure MCE buttons
   */
  
  function add_codox_tinymce_plugin( $opt) {
    if(!$this->is_post_or_page()) return;

    //print_r($opt['plugins']);

    if ( isset( $opt['plugins'] ) && $opt['plugins'] ) {
      if (is_string($opt['plugins']))
        $opt['plugins'] = explode( ',', $opt['plugins'] );

      $opt['plugins'] = array_diff( $opt['plugins'] , array( 'wplink' ) );
    }
    array_push($opt['plugins'], 'codox');
    $opt['plugins'] = implode( ',', $opt['plugins'] );

    return $opt;
  }
   
  /*
   * Removes MCE default inline link editor
   */
  function replace_wp_mcelink( $opt ) {
    if(!$this->is_post_or_page()) return;

    //print_r($opt['plugins']);
    if ( isset( $opt['plugins'] ) && $opt['plugins'] ) {

      if (is_string($opt['plugins']))
        $opt['plugins'] = explode( ',', $opt['plugins'] );

      $opt['plugins'] = array_diff( $opt['plugins'] , array( 'wplink' ) );
    }
    //$opt['plugins'] .= ',wplinkc';
    array_push($opt['plugins'], 'wplinkc');
    $opt['plugins'] = implode( ',', $opt['plugins'] );
    return $opt;
  }

  /*
   * Emit event on MCE DOM ready 
   */
  function set_tinymce_init_callback( $opt) {
    if(!$this->is_post_or_page()) return;

    if(wp_script_is('codox-script', 'done'))
      log_me('codox-script has been printed');
    else
      log_me('codox-script has not been printed');

    $opt['init_instance_callback'] = 'function(editor) {
setTimeout(function(){
        var event = new CustomEvent("editorReady", {detail: "wordpress-tinymce"});
        window.dispatchEvent(event);
return true;
      }, 0)}';

    return $opt;
  }


  /*
   * Load new MCE link editor 
   */
  public function replace_inline_link(){
    if(!$this->is_post_or_page()) return;

    //load new link script
    printf( '<script type="text/javascript" src="%s"></script>', 
      plugins_url( '../js/wplink.js', __FILE__ ) );
  }

  /*
   * Load MCE button config plugin script
   */
  
  public function insert_codox_tinymce_plugin_script() {
    if(!$this->is_post_or_page()) return;

    printf( 
      '<script type="text/javascript" src="%s"></script>', 
      plugins_url( '../js/codox-tinymce.js', __FILE__ ) );

      }
  

  /*
   * Disable default post and page locking to allow multiple users open the same 
   * page for editing .
   * Based on https://wordpress.stackexchange.com/questions/120179/how-to-disable-the-post-lock-edit-lock
   */
  public function remove_post_locked() {
    if(!$this->is_post_or_page()) return;

    // log_me('wp plugin - remove_post_locked');;

    $current_post_type = get_current_screen()->post_type;   

    // Disable locking for page, post and some custom post type
    $post_types_arr = array(
      'page',
      'post',
    );

    if(in_array($current_post_type, $post_types_arr)) {
      // log_me('wp plugin - disable post lock');

      add_filter( 'show_post_locked_dialog', '__return_false');
      add_filter( 'wp_check_post_lock_window', '__return_false');
      wp_deregister_script('heartbeat');
    }
  }

  function is_post_or_page() {
    if(! function_exists('get_current_screen'))
      return false;

    $screen = get_current_screen();

    return $screen->id == 'post' || $screen->id == 'page'; 
  }

  /*
  function add_fixed_mce_plugin( $buttons ) {
       array_push( $buttons, 'separator', 'wplinkc' );
          return $buttons;
  }
   */

  // This function is not used anymore. If it is used again, the code should be moved to js file
  /* 
  function notify_smd($sid) 
  {
    $response = wp_remote_post( WAVE_NOTIFY_URL, array(
      'method'      => 'POST',
      // 'blocking'    => true,
      'headers'     => array(),
      'body'        => array(
          'type' => 'wpPostDelete',
          'user' => $this->get_wp_username(),
          'domain' => home_url(),
          'apiKey' => $this->get_wave_token(),
          'sid'  => $sid,
      ),
      'sslverify'   => false,   // temporary for development
      )
    );

    log_me('remote get result: ' . json_encode($response));
  }
  */

  /*
   * Intercept the event before a post is permanently deleted
   */
  public function on_before_delete_post( $post_id ) {
    log_me('Post ' . $post_id . ' will be deleted permanently ');

    // Cancel the delete peramanently action
    wp_die('Wave plugin has prevented you from trashing posts as somebody may be co-editing the post using Wave.');

    // we don't know whether there are tinymce or ckeditor session, so we notify both type of sessions
    // $this->notify_smd('wordpress-tinymce_' . $this->get_wave_token() . '_' . $this->get_postID());
    // $this->notify_smd('wordpress-ckeditor_' . $this->get_wave_token() . '_' . $this->get_postID());
  }

  /*
   * Add a link to wave settings in the plugin list page
   */
  function plugin_settings_link( $links, $file) {
    if ( $file === cdx_get_plugin_basename() && current_user_can( 'manage_options' ) ) {
      $url = admin_url( WAVE_SETTINGS_PAGE);
      $settings_link = '<a href="'.$url.'">' . __( 'Settings', 'wave-wp' ) . '</a>';
      array_unshift( $links, $settings_link );
    }
      
    return $links;
  }
}

$cdx_wave = new CDX_Wave();

?>
