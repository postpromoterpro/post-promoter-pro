<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ppp_check_for_schedule_conflict() {

	$date = sanitize_text_field( $_POST['date'] );
	$time = sanitize_text_field( $_POST['time'] );

	$offset = (int) -( get_option( 'gmt_offset' ) ); // Make the timestamp in the users' timezone, b/c that makes more sense

	$share_time = explode( ':', $time );

	$hours = (int) $share_time[0];
	$minutes = (int) substr( $share_time[1], 0, 2 );
	$ampm = strtolower( substr( $share_time[1], -2 ) );

	if ( $ampm == 'pm' && $hours != 12 ) {
		$hours = $hours + 12;
	}

	if ( $ampm == 'am' && $hours == 12 ) {
		$hours = 00;
	}

	$hours     = $hours + $offset;
	$date      = explode( '/', $date );
	$timestamp = mktime( $hours, $minutes, 0, $date[0], $date[1], $date[2] );

	$result = ppp_has_cron_within( $timestamp ) ? 1 : 0;

	echo $result;
	wp_die();

}
add_action( 'wp_ajax_ppp_has_schedule_conflict', 'ppp_check_for_schedule_conflict' );
