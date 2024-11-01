<?php
/*
Plugin Name: Wave for WP
Plugin URI: wave.codox.io
Description: Wave enables teams to real-time co-edit posts and pages in WordPress using Tinymce. 
Author: sund 
Version: 1.1.1
Text Domain: wave-wp
*/

function log_me($message) {

  }

function cdx_get_plugin_basename() {
  return plugin_basename( __FILE__ );
}

define('WAVE_SERVER_URL', 'https://app.codox.io');
define('CDN1_URL', 'https://cdn1.codox.io');
define('CDN2_URL', 'https://cdn2.codox.io');
const WAVE_CLIENT_JS_URL = WAVE_SERVER_URL . '/plugins/wave.client.js';
const WAVE_CLIENT_CSS_URL = CDN1_URL . '/lib/css/wave.client.css';
const WAVE_PLUGIN_URL = CDN2_URL . '/wordpressplugin';
const WAVE_PLUGIN_OPTIONS_JS_URL = WAVE_PLUGIN_URL . '/js/options.js';
const WAVE_PLUGIN_OPTIONS_CSS_URL = WAVE_PLUGIN_URL . '/css/options.css';
const WAVE_PLUGIN_TEMPLATES_URL = WAVE_PLUGIN_URL . '/js/templates.js';

const WAVE_SETTINGS = 'settings_page_wave_settings';
const WAVE_SETTINGS_PAGE = 'options-general.php?page=wave_settings';
const WAVE_OPTION_NAME = 'codox_wave_option';
const WAVE_OPTION_TOKEN = 'token';
const WAVE_OPTIONS_CKEDITOR_DEACTIVATED = 'ckeditor_deactivated';

include 'php/options.php';
include 'php/wave.php';

?>
