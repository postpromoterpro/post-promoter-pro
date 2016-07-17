<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule social media posts with wp_schedule_single_event
 * @param  id $post_id
 * @param  object $post
 * @return void
 */
function ppp_schedule_share( $post_id, $post ) {
	global $ppp_options;
	$allowed_post_types = isset( $ppp_options['post_types'] ) ? $ppp_options['post_types'] : array();
	$allowed_post_types = apply_filters( 'ppp_schedule_share_post_types', $allowed_post_types );

	if ( ! isset( $_POST['post_status'] ) || ! array_key_exists( $post->post_type, $allowed_post_types ) ) {
		return;
	}

	ppp_remove_scheduled_shares( $post_id );

	if( ( $_POST['post_status'] == 'publish' && $_POST['original_post_status'] != 'publish' ) || // From anything to published
		( $_POST['post_status'] == 'future' && $_POST['original_post_status'] == 'future' ) || // Updating a future post
		( $_POST['post_status'] == 'publish' && $_POST['original_post_status'] == 'publish' ) ) { // Updating an already published post
		global $ppp_options, $ppp_social_settings;

		$timestamps = ppp_get_timestamps( $post_id );

		foreach ( $timestamps as $timestamp => $name ) {
			$timestamp = substr( $timestamp, 0, strlen( $timestamp ) - 3 );
			wp_schedule_single_event( $timestamp, 'ppp_share_post_event', array( $post_id, $name ) );
		}
	}
}
// This action is for the cron event. It triggers ppp_share_post when the crons run
add_action( 'ppp_share_post_event', 'ppp_share_post', 10, 2 );

/**
 * Given a post ID remove it's scheduled shares
 * @param  int $post_id The Post ID to remove shares for
 * @return void
 */
function ppp_remove_scheduled_shares( $post_id ) {
	do_action( 'ppp_pre_remove_scheduled_shares', $post_id );

	$current_item_shares = ppp_get_shceduled_crons( $post_id );

	foreach ( $current_item_shares as $share ) {
		wp_clear_scheduled_hook( 'ppp_share_post_event', array( $post_id, $share['args'][1] ) );
	}

	do_action( 'ppp_post_remove_scheduled_shares', $post_id );
}

/**
 * Given an array of arguments, remove a share
 * @param  array $args Array containing 2 values $post_id and $name
 * @return void
 */
function ppp_remove_scheduled_share( $args ) {
	wp_clear_scheduled_hook( 'ppp_share_post_event', $args );
	return;
}

/**
 * Get all the crons hooked into 'ppp_share_post_event'
 * @return array All crons scheduled for Post Promoter Pro
 */
function ppp_get_shceduled_crons( $post_id = false ) {
	$all_crons = get_option( 'cron' );
	$ppp_crons = array();

	foreach ( $all_crons as $timestamp => $cron ) {
		if ( ! isset( $cron['ppp_share_post_event'] ) ) {
			continue;
		}

		foreach ( $cron['ppp_share_post_event'] as $key => $single_event ) {
			$name_parts = explode( '_', $single_event['args'][1] );
			if ( false !== $post_id && $post_id != $name_parts[2] ) {
				continue;
			}

			$single_event['timestamp'] = $timestamp;
			$ppp_crons[ $key ]         = $single_event;
		}

	}

	return apply_filters( 'ppp_get_scheduled_crons', $ppp_crons );
}

/**
 * Given a time, see if there are any tweets scheduled within the range of the within
 *
 * @since  2.2.3
 * @param  int $time   The timestamp to check for
 * @param  int $within The number of seconds to check, before and after a given time
 * @return bool        If there are any tweets scheduled within this timeframe
 *
 */
function ppp_has_cron_within( $time = 0, $within = 0 ) {
	if ( empty( $time ) ) {
		$time = current_time( 'timestamp' );
	}

	if ( empty( $within ) ) {
		$within = ppp_get_default_conflict_window();
	}

	$crons = ppp_get_shceduled_crons();

	if ( empty( $crons ) ) {
		return false;
	}

	$scheduled_times = wp_list_pluck( $crons, 'timestamp' );

	$found_time = false;
	foreach ( $scheduled_times as $key => $scheduled_time ) {
		$found_time = ppp_is_time_within( $scheduled_time, $time, $within );
		if ( $found_time ) {
			break;
		}
	}

	return $found_time;
}

/**
 * Check if $time is within the +/- of $target_time
 *
 * @since  2.2.3
 * @param  integer $time        The Time to check
 * @param  integer $target_time The Target time
 * @param  integer $within      The +/- in seconds
 * @return bool                 If the time is within the range of the target_time
 *
 */
function ppp_is_time_within( $time = 0, $target_time = 0, $within = 0 ) {
	$min = $target_time - $within;
	$max = $target_time + $within;

	return ( ( $time >= $min ) && ( $time <= $max ) );
}

/**
 * The default +/- on when we should warn about conflicting tweets
 * @return int The +/- to warn on
 */
function ppp_get_default_conflict_window() {
	return apply_filters( 'ppp_default_conflict_window', HOUR_IN_SECONDS / 2 );
}

/**
 * When a post is trashed, trash it's logs as well
 *
 * @since  2.3
 * @param  int $post_id Post ID
 * @return void
 */
function ppp_trash_logs( $post_id ) {
	global $wpdb;

	$sql = $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'trash' WHERE post_parent = $post_id && post_type = 'wp_log'" );
	$wpdb->query( $sql );
}
add_action( 'wp_trash_post', 'ppp_trash_logs', 10, 1 );

/**
 * When a post is deleted, delete it's logs as well
 *
 * @since  2.3
 * @param  int $post_id Post ID
 * @return void
 */
function ppp_delete_logs( $post_id ) {
	global $wpdb;

	$sql = $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_parent = %d && post_type = 'wp_log'", $post_id );
	$wpdb->query( $sql );
}
add_action( 'delete_post', 'ppp_delete_logs', 10, 1 );

/**
 * When a post is untrashed, untrash it's logs as well
 *
 * @since  2.3
 * @param  int $post_id Post ID
 * @return void
 */
function ppp_untrash_logs( $post_id ) {
	global $wpdb;
	$post_status = get_post_status( $post_id );

	$sql = $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = '$post_status' WHERE post_parent = $post_id && post_type = 'wp_log'" );
	$wpdb->query( $sql );
}
add_action( 'untrashed_post', 'ppp_untrash_logs', 10, 1 );

/**
 * Unschedule any tweets when the post is unscheduled
 *
 * @since  2.1.2
 * @param  string $old_status The old status of the post
 * @param  string $new_status The new status of the post
 * @param  object $post       The Post Object
 * @return void
 */
function ppp_unschedule_shares( $new_status, $old_status, $post ) {

	if ( ( $old_status == 'publish' || $old_status == 'future' ) && ( $new_status != 'publish' && $new_status != 'future' ) ) {
		ppp_remove_scheduled_shares( $post->ID );
	}

}
add_action( 'transition_post_status', 'ppp_unschedule_shares', 10, 3 );

/**
 * Given a date and time string (from our post meta), return an offset timestamp
 *
 * @since  2.3
 * @param  string $date The Date
 * @param  string $time The time
 * @return long         A timestamp
 */
function ppp_generate_timestamp( $date = false, $time = false ) {
	// Just in case we need this, let's set it once
	$current_time = current_time( 'timestamp' );

	if ( empty( $date ) ) {
		$date = date( 'm/d/Y', $current_time );
	}

	if ( empty( $time ) ) {
		$time = date( 'h:ia', $current_time );
	}

	$share_time = explode( ':', $time );
	$hours      = (int) $share_time[0];
	$minutes    = (int) substr( $share_time[1], 0, 2 );
	$ampm       = strtolower( substr( $share_time[1], -2 ) );

	if ( $ampm == 'pm' && $hours != 12 ) {
		$hours = $hours + 12;
	}

	if ( $ampm == 'am' && $hours == 12 ) {
		$hours = 00;
	}

	$offset    = (int) -( get_option( 'gmt_offset' ) );
	$hours     = $hours + $offset;
	$date      = explode( '/', $date );
	$timestamp = mktime( (int) $hours, (int) $minutes, 0, (int) $date[0], (int) $date[1], (int) $date[2] );

	return $timestamp;
}

