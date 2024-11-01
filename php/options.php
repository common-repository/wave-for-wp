<?php

/**
 * This file is part of the Codox Wave plugin and is released under the 
 * same license. 
 * For more information please see wave-wp.php.
 * 
 * Copyright (c) 2017 Codox Inc. All rights reserved.
 */

class CDX_WaveSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {      
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'wp_ajax_save_wave_apikey', array($this, 'save_wave_apikey' ));

        $this->get_plugin_list();
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {      
        // This page will be under "Settings"
        add_options_page(
            'Wave Settings', 
            'Wave Settings', 
            'manage_options', 
            'wave_settings', 
            array( $this, 'create_admin_page' )
        );
    }

    public function getOptions()
    {
      // Set class property
      if($this->options == null)
        $this->options = get_option( WAVE_OPTION_NAME, array(WAVE_OPTION_TOKEN =>'', WAVE_OPTIONS_CKEDITOR_DEACTIVATED => 'false') );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        log_me('create_admin_page');


        // This prints out all hidden setting fields
        settings_fields( 'codox_wave_option_group' );
        do_settings_sections( 'codox_wave_setting_admin' );      
    }

    /* 
     * print script to external scripts
     */
    public function print_prefix()
    {
      // poppers
      wp_register_script('prefix_popper', 'https://unpkg.com/popper.js');
      wp_enqueue_script('prefix_popper');


      // bootstrap
      wp_register_script('prefix_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
      wp_enqueue_script('prefix_bootstrap');

      wp_register_style('prefix_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
      wp_enqueue_style('prefix_bootstrap');


      // handlebars
      wp_register_script('prefix-handlebars', 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.min.js');
      wp_enqueue_script('prefix-handlebars');


      // ionicons
      wp_register_style('prefix_ionicons', 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css');
      wp_enqueue_style('prefix_ionicons');

    }

    /*
     * print scripts for connecting to dash-connect
     */
    public function print_script() 
    {
      $this->getOptions();
      log_me('print_script options: ' . json_encode($this->options));

      $param = array(
        'user' => wp_get_current_user()->user_login,
        'domain' => home_url(),
        'apiKey' => $this->options[WAVE_OPTION_TOKEN],
        'assetsDir' =>  plugins_url('assets', dirname(__FILE__)),
        'plugins' => $this->get_plugin_list(),
      );

      wp_register_script( 'option-script', WAVE_PLUGIN_OPTIONS_JS_URL, array( 'jquery'));
      wp_localize_script('option-script', 'wp_vars', $param);   
      wp_enqueue_script('option-script');

      wp_register_script( 'templates-script', WAVE_PLUGIN_TEMPLATES_URL, array( 'jquery'));
      wp_enqueue_script('templates-script');


      wp_register_style('option-style', WAVE_PLUGIN_OPTIONS_CSS_URL);
      wp_enqueue_style('option-style');
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {              
        register_setting(
            'codox_wave_option_group', // Option group
            WAVE_OPTION_NAME, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'codox_token_setting', // ID
            '', // Title
            array( $this, 'print_section' ), // Callback
            'codox_wave_setting_admin' // Page
        );    
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
      log_me('sanitize input is called: ' . json_encode($input));

      $new_input = array();
      if( isset( $input[WAVE_OPTION_TOKEN] ) )
          $new_input[WAVE_OPTION_TOKEN] = sanitize_text_field( $input[WAVE_OPTION_TOKEN] );

      if( isset( $input[WAVE_OPTIONS_CKEDITOR_DEACTIVATED] ) )
          $new_input[WAVE_OPTIONS_CKEDITOR_DEACTIVATED] = sanitize_text_field( $input[WAVE_OPTIONS_CKEDITOR_DEACTIVATED] );

      return $new_input;
    }  

    public function get_local_file_contents( $file_path ) {
      ob_start();
      include $file_path;
      $contents = ob_get_clean();

      log_me('file content: ', $contents);

      return $contents;
    }

    /** 
     * Print the token Section text
     */
    public function print_section()
    {
      echo($this->get_local_file_contents(__DIR__ . './../html/options.html'));

      $this->print_prefix();
      $this->print_script();        
    }

    /*
     * Get the list of installed plugin and its status whether activated or not
     */
    public function get_plugin_list() {
      if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      // get all installed plugins
      $all_wp_plugins = get_plugins();

      // get the list of active plugin
      $active_plugins = get_option('active_plugins');

      // loop through the list of all installed plugins and obtained only necessary information
      $plugins = array();
      foreach($all_wp_plugins as $key => $value) {
        $plugins[$key] = array(
          'name' => $value['Name'],
          'enabled' => false,
          'version' => $value['Version'],
        );
      }

      // loop through the active list and set the enabled value in our plugin list
      foreach($active_plugins as $p) {
        if(isset($plugins[$p])) {
          $plugins[$p]['enabled'] = true;
        }
      }

      log_me('.................................. plugins ..........................');
      log_me(print_r($plugins, true));

      return $plugins;
    }

    /*
     * Save the Wave APIKey into Wordpress option for Wave plugin
     */ 
    public function save_wave_apikey() {
      log_me('save_wave_apikey');

      // check_ajax_referer('codox-wave-ajax-nonce');
      $body = $_POST['data'];

      log_me('save_wave_apikey: ' . json_encode($body));

      $this->options[WAVE_OPTION_TOKEN] = $body['apiKey'];
      update_option(WAVE_OPTION_NAME, $this->options);
      
      wp_die(); // this is required to terminate immediately and return a proper response
    }
}

if( is_admin() )
    $wave_settings_page = new CDX_WaveSettingsPage();
?>
