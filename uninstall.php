<?php

/*
File used when the user deletes the plugin.
Drops the table created for the keys and deletes custom posts created by the plugin.
*/

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) die();

global $wpdb;
define('SH9BKTABLE', $wpdb->prefix . "sh9_betakeys");

// drop the table.
$query = $wpdb->prepare("DROP table " . SH9BKTABLE);
$wpdb->query($query);
$wpdb->flush();

// and delete the custom posts created.
$allposts = get_posts('numberposts=-1&post_type=sh9_betakey&post_status=any');

foreach( $allposts as $postinfo) {
  wp_delete_post($postinfo->ID, true);
}

// done.