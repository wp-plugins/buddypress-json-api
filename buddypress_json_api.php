<?php
/*
Plugin Name: BuddyPress Json API
Plugin URI: http://aheadzen.com/
Description: JSON API additional features to be used with Buddypress of profile and profile photo update.
Author: Aheadzen Team
Author URI: http://aheadzen.com/
Text Domain: aheadzen
Version: 1.0.21
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define('BUDDYPRESS_JSON_API_HOME', dirname(__FILE__));

function buddypress_json_api_init() {
	load_plugin_textdomain('aheadzen', false, basename( dirname( __FILE__ ) ) . '/languages');
}
add_action('init', 'buddypress_json_api_init');

//if (!is_plugin_active('buddypress/bp-loader.php')) {
if(function_exists('bp_has_profile')){
    add_action('admin_notices', 'buddypress_json_api_notice');
    return;
}

if (!is_plugin_active('json-api/json-api.php')) {
    add_action('admin_notices', 'buddypress_json_api_notice_json_api');
    return;
}

add_filter('json_api_controllers', 'buddypress_json_api_add_controller');
add_filter('json_api_buddypressread_controller_path', 'buddypress_jos_api_set_controllerPath');

function buddypress_json_api_notice() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>Buddypress JSON API for Buddypress</strong> requires the BuddyPress plugin to be activated. Please <a href="http://buddypress.org">install / activate BuddyPress</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'aheadzen');
    echo '</p></div>';
}

function buddypress_json_api_notice_json_api() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>Buddypress JSON API</strong> requires the JSON API plugin to be activated. Please <a href="http://wordpress.org/plugins/json-api/">install / activate JSON API</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'aheadzen');    echo '</p></div>';
}

function buddypress_json_api_add_controller($aControllers) {
    $aControllers[] = 'BuddypressRead';
    return $aControllers;
}

function buddypress_jos_api_set_controllerPath($sDefaultPath) {
    return BUDDYPRESS_JSON_API_HOME . '/controllers/BuddypressJsonRead.php';
}