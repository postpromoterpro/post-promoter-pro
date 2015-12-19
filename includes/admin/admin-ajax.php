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

/**
 * Retrieve calendar data for the provided start and end dates (from fullcalendar)
 * @return void  Echo's JSON
 */
function ppp_get_celendar_events() {
	$start = $_POST['start'];
	$end   = $_POST['end'];

	// Get blog posts
	$post_types  = apply_filters( 'ppp_schedule_post_types', array( 'post' ) );
	$post_status = apply_filters( 'ppp_schedule_post_status', array( 'publish', 'future' ) );

	$args  = array(
		'post_type'      => $post_types,
		'post_status'    => $post_status,
		'posts_per_page' => -1,
		'date_query'     => array(
			'inclusive' => true,
			'after'     => $start,
			'before'    => $end,
		),
	);

	$posts  = new WP_Query( $args );
	$events = array();

	if ( $posts->have_posts() ) {
		while ( $posts->have_posts() ) {
			$posts->the_post();
			$post_type = get_post_type();

			$events[] = array(
				'id'        => get_the_ID(),
				'title'     => get_the_title(),
				'start'     => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date() . ' ' . get_the_time() ) ),
				'className' => 'ppp-calendar-item-wp cal-post-' . get_the_ID(),
				'belongsTo' => get_the_ID(),
			);

			// ...and share on publish items
			$events = apply_filters( 'ppp_calendar_on_publish_event', $events, get_the_ID() );

		}
	}
	wp_reset_postdata();

	// Get previously shared items
	$args  = array(
		'post_type'      => 'wp_log',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'date_query'     => array(
			'inclusive' => true,
			'after'     => $start,
			'before'    => $end,
		),
		'tax_query'      => array(
			array(
				'taxonomy' => 'wp_log_type',
				'field'    => 'slug',
				'terms'    => 'ppp_share',
			),
		),
	);

	$shared  = new WP_Query( $args );

	if ( $shared->have_posts() ) {
		while ( $shared->have_posts() ) {
			$shared->the_post();
			$network     = get_post_meta( get_the_ID(), '_wp_log_network', true );
			$post_parent = get_post_field( 'post_parent', get_the_ID() );

			$events[]    = array(
				'id'        => get_the_ID(),
				'title'     => get_the_title(),
				'start'     => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date() . ' ' . get_the_time() ) ),
				'className' => 'ppp-calendar-item-' . $network . ' cal-post-' . $post_parent,
				'belongsTo' => $post_parent,
			);

			// ...and share on publish items
			$events = apply_filters( 'ppp_calendar_on_publish_event', $events, get_the_ID() );

		}
	}
	wp_reset_postdata();

	// Get items already scheduled but not sent
	$crons      = ppp_get_shceduled_crons();
	$start_ts   = strtotime( $start );
	$end_ts     = strtotime( $end );

	foreach ( $crons as $key => $cron ) {
		$ppp_data = $cron;
		$timestamp = $ppp_data['timestamp'];



		$name_parts = explode( '_', $ppp_data['args'][1] );
		$index      = $name_parts[1];
		$service    = isset( $name_parts[3] ) ? $name_parts[3] : 'tw';
		$builder    = 'ppp_' . $service . '_build_share_message';

		$events[] = array(
			'id'        => $key,
			'title'     => $builder( $ppp_data['args'][0], $ppp_data['args'][1], false, false ),
			'start'     => date_i18n( 'Y-m-d/TH:i:s', $timestamp + ( get_option( 'gmt_offset' ) * 3600 ) ),
			'end'       => date_i18n( 'Y-m-d/TH:i:s', $timestamp + ( get_option( 'gmt_offset' ) * 3600 ) ),
			'className' => 'ppp-calendar-item-' . $service . ' cal-post-' . $name_parts[2],
			'belongsTo' => $name_parts[2],
		);
	}

	echo json_encode( $events );
	die();
}
add_action( 'wp_ajax_ppp_get_calendar_events', 'ppp_get_celendar_events' );
