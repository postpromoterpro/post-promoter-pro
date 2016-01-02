<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the default array of tokens for replacement
 * @param  array $tokens The array of existing tokens
 * @return array         The array of tokens with the defaults added
 */
function ppp_set_default_text_tokens( $tokens ) {
	$tokens[] = array( 'token' => 'post_title', 'description' => __( 'The title of the post being shared', 'ppp-txt' ) );
	$tokens[] = array( 'token' => 'site_title', 'description' => __( 'The site title, from Settings > General' ) );

	return $tokens;
}
add_filter( 'ppp_text_tokens', 'ppp_set_default_text_tokens', 10, 1 );

/**
 * Iterate through all tokens we have registered and run the associated filter on them
 *
 * Devs can add a token to the array, and use ppp_replace_token-[token] as the filter to execute their replacements
 * @param  string $string The raw share text
 * @param  array  $args   Array of arguments, containing things like post_id
 * @return string         The raw string, with all tokens replaced
 */
function ppp_replace_text_tokens( $string, $args = array() ) {
	$tokens = wp_list_pluck( ppp_get_text_tokens(), 'token' );
	foreach ( $tokens as $key => $token ) {
		$string = apply_filters( 'ppp_replace_token-' . $token, $string, $args );
	}

	return $string;
}
add_filter( 'ppp_share_content', 'ppp_replace_text_tokens', 10, 2 );

/**
 * Replace the Post Title token with the post title
 * @param  string $string The string to search
 * @param  array $args    Array of arguements, like post_id
 * @return string         The string with the token {post_title} replaced
 */
function ppp_post_title_token( $string, $args ) {
	if ( !isset( $args['post_id'] ) ) {
		return $string;
	}

	return preg_replace( '"\{post_title\}"', get_the_title( $args['post_id'] ), $string );
}
add_filter( 'ppp_replace_token-post_title', 'ppp_post_title_token', 10, 2 );

/**
 * Replace the Site Title token with the site title
 * @param  string $string The string to search
 * @param  array $args    Array of arguements, like post_id
 * @return string         The string with the token {site_title} replaced
 */
function ppp_site_title_token( $string, $args ) {
	return preg_replace( '"\{site_title\}"', get_bloginfo(), $string );
}
add_filter( 'ppp_replace_token-site_title', 'ppp_site_title_token', 10, 2 );

/**
 * The core option of Unique Links for anaytlics tracking
 * @param  string $share_link The link to share
 * @param  int    $post_id    The Post ID of the link
 * @param  string $name       The Name attribute from the cron
 * @return string             The String with the unique links analytics applied
 */
function ppp_generate_unique_link( $share_link, $post_id, $name ) {
	$name_parts = explode( '_', $name );
	$share_link .= strpos( $share_link, '?' ) ? '&' : '?' ;

	$query_string_var = apply_filters( 'ppp_query_string_var', 'ppp' );

	$share_link .= $query_string_var . '=' . $post_id . '-' . $name_parts[1];

	return $share_link;
}
add_filter( 'ppp_analytics-unique_links', 'ppp_generate_unique_link', 10, 3 );

/**
 * The core option of Google Analytics Tags for anaytlics tracking
 * @param  string $share_link The link to share
 * @param  int    $post_id    The Post ID of the link
 * @param  string $name       The Name attribute from the cron
 * @return string             The String with the GA Tags applied
 */
function ppp_generate_google_utm_link( $share_link, $post_id, $name ) {
	$name_parts = explode( '_', $name );

	if ( empty( $name_parts[3] ) ) {
		$name_parts[3] = '';
	}

	switch( $name_parts[3] ) {
		case 'li':
			$network = 'LinkedIn';
			break;
		case 'fb':
			$network = 'Facebook';
			break;
		case 'tw':
		default:
			$network = 'Twitter';
			break;
	}

	$utm['source']   = $network;
	$utm['medium']   = 'social';
	$utm['term']     =  ppp_get_post_slug_by_id( $post_id );
	$utm['content']  = $name_parts[1]; // The day after publishing
	$utm['campaign'] = 'PostPromoterPro';

	$utm_string  = strpos( $share_link, '?' ) ? '&' : '?' ;
	$first = true;
	foreach ( $utm as $key => $value ) {
		if ( !$first ) {
			$utm_string .= '&';
		}
		$utm_string .= 'utm_' . $key . '=' . $value;
		$first = false;
	}

	$share_link .= $utm_string;

	return $share_link;
}
add_filter( 'ppp_analytics-google_analytics', 'ppp_generate_google_utm_link', 10, 3 );
