<?php

/**
 * Plugin Name:         Amelia Scheduled Notifications
 * Plugin URI:          https://github.com/xewl/wp-amelia-scheduled-notifications
 * Description:         Easily register the scheduled notifications of the Amelia booking plugin to WP-Cron.
 * Version:             0.0.2
 * Author:              Xewl
 * Author URI:          https://xewl.dev
 * Text Domain:         wpamsn
 * License: GPL 2.0
 * Requires at least:   5.7
 * Requires PHP:        7.2
 */

$amelia_activated = is_plugin_active('ameliabooking/ameliabooking.php');

add_filter( 'cron_schedules', 'wpamsn_add_cron_interval' );
function wpamsn_add_cron_interval( $schedules ) { 
   $schedules['wpamsn_quarter_hour'] = array(
      'interval' => 900, // 15 minutes
      'display'  => esc_html__( 'Every 15 minutes' ), );
   return $schedules;
}

	
add_action( 'wpamsn_cron_hook__scheduled_notifications', 'wpamsn_cron_exec__scheduled_notifications' );
function wpamsn_cron_exec__scheduled_notifications(){
   $url = admin_url('admin-ajax.php') . '?' . http_build_query(['action' => 'wpamelia_api', 'call' => '/notifications/scheduled/send']);
   return wp_remote_retrieve_body( wp_remote_get($url) );
}

if( $amelia_activated ) {
   if ( ! wp_next_scheduled( 'wpamsn_cron_hook__scheduled_notifications' ) ) {
      wp_schedule_event( time(), 'wpamsn_quarter_hour', 'wpamsn_cron_hook__scheduled_notifications' );
   }
} else {
   wpamsn_unschedule_cron_hooks();
   add_action( 'admin_notices', 'wpamsn_cron_exec__admin_notice__dependencies' );
}


register_deactivation_hook( __FILE__, 'wpamsn_deactivate' ); 
function wpamsn_deactivate() {
   wpamsn_unschedule_cron_hooks();
}


function wpamsn_unschedule_cron_hooks(){
   if ( $timestamp = wp_next_scheduled( 'wpamsn_cron_hook__scheduled_notifications' ) ) {
       wp_unschedule_event( $timestamp, 'wpamsn_cron_hook__scheduled_notifications' );
   }
}


function wpamsn_cron_exec__admin_notice__dependencies() {
   $class = 'notice notice-warning';
   $message = __( 'The Amelia Scheduled Notifications plugin depends on having the Amelia plugin activated. Scheduled notifications are not being sent.', 'wpamsn' );

   printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
