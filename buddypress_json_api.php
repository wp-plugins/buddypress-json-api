<?php
/*
Plugin Name: BuddyPress Json API
Plugin URI: http://aheadzen.com/
Description: The plugin added votes option for pages, post, custom post types, comments, buddypress activity, groups, member profiles, woocommerce products etc. <br />You can control display option from <a href="options-general.php?page=voter" target="_blank"><b>Plugin Settings >></b></a>
Author: Aheadzen Team
Author URI: http://aheadzen.com/
Text Domain: aheadzen
Domain Path: /language
Version: 2.2.3
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define('BUDDYPRESS_JSON_API_FOLDER', dirname(__FILE__));

function buddypress_json_api_init() {
  load_plugin_textdomain('aheadzen', false, basename( dirname( __FILE__ ) ) . '/languages');
}
add_action('init', 'buddypress_json_api_init');

if (!is_plugin_active('buddypress/bp-loader.php')) {
    add_action('admin_notices', 'buddypress_json_api_notice_buddypress');
    return;
}

if (!is_plugin_active('json-api/json-api.php')) {
    add_action('admin_notices', 'buddypress_json_api_notice_json_api');
    return;
}

add_filter('json_api_controllers', 'addBuddypressJsonApiController');
add_filter('json_api_controller_path', 'setBuddypressJsonApiReadControllerPath');

function buddypress_json_api_notice_buddypress() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>JSON API for Buddypress</strong> requires the BuddyPress plugin to be activated. Please <a href="http://buddypress.org">install / activate BuddyPress</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'json-api-for-buddypress');
    echo '</p></div>';
}

function buddypress_json_api_notice_json_api() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>JSON API for Buddypress</strong> requires the JSON API plugin to be activated. Please <a href="http://wordpress.org/plugins/json-api/">install / activate JSON API</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'json-api-for-buddypress');    echo '</p></div>';
}

function addBuddypressJsonApiController($aControllers) {
    $aControllers[] = 'BuddypressRead';
	return $aControllers;
}

function setBuddypressJsonApiReadControllerPath($sDefaultPath) {
	return dirname(__FILE__) . '/controllers/BuddypressJsonRead.php';
}

