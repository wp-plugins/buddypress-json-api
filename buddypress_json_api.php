<?php
/*
Plugin Name: BuddyPress Json API
Plugin URI: http://aheadzen.com/
Description: JSON API additional features to be used with Buddypress of profile and profile photo update.
Author: Aheadzen Team
Author URI: http://aheadzen.com/
Text Domain: aheadzen
Version: 1.0.41
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

add_filter('bp_has_activities','bpjsonapi_has_activities','',2);
add_filter('bp_legacy_theme_get_single_activity_content','bpjsonapi_single_activity_content','');
/*add_filter('bp_notifications_get_registered_components','bpjsonapi_notifications_get_registered_components');
add_filter('bp_notifications_get_notifications_for_user','bpjsonapi_notification_title_format','',3);
add_action( 'bp_setup_globals', 'bpjsonapi_setup_globals',999);

function bpjsonapi_notifications_get_registered_components( $component_names = array() )
{
	if ( ! is_array( $component_names ) ) {$component_names = array();}
	array_push( $component_names, 'activityshare' );
	return $component_names;
}

function bpjsonapi_setup_globals()
{
	global $bp;
	$bp->activity_share = new BP_Component;
	$bp->activity_share->notification_callback = 'bpjsonapi_notification_title_format';
	$bp->active_components['activityshare'] = '1';
}

function bpjsonapi_notification_title_format( $component_action, $item_id, $secondary_item_id ) {   
	global $bp,$wp_query;	
	$notification = 'HELLO Activity Share -- '.$component_action.' -- '.$item_id.' -- '.$secondary_item_id;	
	return $notification;

}
*/
function bpjsonapi_has_activities($activity_flag,$activity)
{
	foreach($activity->activities as $activityobj){
		$id = $activityobj->id;
		if($activityobj->type=='activityshare' && $activityobj->item_id>0){
			if($activityobj->secondary_item_id>0){
				$aid = $activityobj->secondary_item_id;
			}else{
				$aid = $activityobj->item_id;
			}
			$activitys = bp_activity_get(array('in'=>$aid));
			if($activitys){
				$activitie = $activitys['activities'][0];
				$activityobj->content .= '</a>  '.$activitie->content;
			}
		}		
	}
	return $activity_flag;
}

/* Display full activity content on "Read more" click of detail page */
function bpjsonapi_single_activity_content($activity)
{
	if($activity->type=='activityshare' && $activity->item_id>0){
		if($activity->secondary_item_id>0){
			$aid = $activity->secondary_item_id;
		}else{
			$aid = $activity->item_id;
		}
		$activitys = bp_activity_get(array('in'=>$aid));
		if($activitys){
			$activitie = $activitys['activities'][0];
			$activity->content .= ' '.$activitie->content;
		}
	}
	return $activity;
}