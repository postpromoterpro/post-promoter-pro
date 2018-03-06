<?php

function ppp_add_shares_calendar_data( $items, $week_single_date ) {

	$items[] = array(
		'type' => 'share',
		'content' => 'Testing',
	);

	return $items;
}
add_filter( 'ef_calendar_posts_for_week', 'ppp_add_shares_calendar_data', 10, 2 );

function ppp_add_shares_to_ef_calendar( $output, $calendar_object, $num, $post, $week_single_date ) {
	if ( ! is_array( $post ) ) {
		return $output;
	}

	if ( $post['type'] === 'share' ) {
		$output = $post['content'];
	}
	return $output;
}
add_filter( 'pre_ef_calendar_single_date_item_html', 'ppp_add_shares_to_ef_calendar', 10, 5 );