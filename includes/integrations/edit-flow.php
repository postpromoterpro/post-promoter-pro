<?php

function ppp_add_shares_calendar_data( $items, $week_single_date ) {

	$items[] = array(
		'type' => 'share',
		'content' => 'Testing',
	);

	return $items;
}
add_filter( 'ef_calendar_posts_for_week', 'ppp_add_shares_calendar_data', 10, 2 );

function ppp_add_shares_to_ef_calendar( $output, $num, $post ) {
	if ( $post['type'] === 'share' ) {
		$output = $post['content'];
	}
	return $output;
}
add_filter( 'ef_calendar_single_date_item', 'ppp_add_shares_to_ef_calendar', 10, 3 );