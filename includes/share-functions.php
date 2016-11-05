<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if we should share this post when it's being published
 *
 * @param string $new_status The new status of the post
 * @param string $old_status The old status of the poast
 * @param  object $post      The Post Object
 *
 * @return void
 */
function ppp_share_on_publish( $new_status, $old_status, $post ) {
	// don't publish password protected posts
	if ( '' !== $post->post_password ) {
		return;
	}

	if ( $new_status == 'publish' && $old_status != 'publish' ) {
		global $ppp_options;

		$allowed_post_types = isset( $ppp_options['post_types'] ) ? $ppp_options['post_types'] : array();
		$allowed_post_types = apply_filters( 'ppp_schedule_share_post_types', $allowed_post_types );

		if ( !isset( $post->post_status ) || !array_key_exists( $post->post_type, $allowed_post_types ) ) {
			return false;
		}

		do_action( 'ppp_share_on_publish', $new_status, $old_status, $post );
	}

	return true;
}

/**
 * Create timestamps and unique identifiers for each cron.
 * @param  int $post_id
 * @return array
 */
function ppp_get_timestamps( $post_id ) {
	$times = array();
	return apply_filters( 'ppp_get_timestamps', $times, $post_id );
}

/**
 * Hook for the crons to fire and send shares
 * @param  id $post_id
 * @param  string $name
 * @return void
 */
function ppp_share_post( $post_id, $name ) {
	global $ppp_options, $ppp_social_settings, $ppp_share_settings, $ppp_twitter_oauth;

	// If we've already started to share this, don't share it again.
	// Compensates for wp-cron's race conditions
	if ( get_transient( 'ppp_sharing' . $name ) === 'true' ) {
		return;
	}

	// For 10 seconds, don't allow another share to go for this post
	set_transient( 'ppp_sharing' . $name, 'true', 10 );

	$name_parts = explode( '_', $name );
	$index      = $name_parts[1];
	$service    = isset( $name_parts[3] ) ? $name_parts[3] : 'tw';

	// If we're fired on a cron, check for stale cron runs
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		switch( $service ) {
			case 'tw':
				$post_meta = get_post_meta( $post_id, '_ppp_tweets', true );
				break;

			case 'fb':
				$post_meta = get_post_meta( $post_id, '_ppp_fb_shares', true );
				break;

			case 'li':
				$post_meta = get_post_meta( $post_id, '_ppp_li_shares', true );
				break;
		}

		$share_data = array();
		if ( isset( $post_meta[ $index ] ) ) {
			$share_data = $post_meta[ $index ];
		}

		$timestamp = ppp_generate_timestamp( $share_data['date'], $share_data['time'] );

		// If the current time is more than 60 minutes (filterable) overdue, don't fire the share
		$share_buffer = apply_filters( 'ppp_share_buffer', HOUR_IN_SECONDS );
		if ( ( current_time( 'timestamp', 1 ) - $timestamp ) > $share_buffer ) {
			return;
		}
	}

	do_action( 'ppp_share_scheduled_' . $service, $post_id, $index, $name );

}

/**
 * Get the Social Share Tokens from the API
 * @return void
 */
function ppp_set_social_tokens() {
	if ( ( defined( 'PPP_TW_CONSUMER_KEY' ) && defined( 'PPP_TW_CONSUMER_SECRET' ) ) ||
	     ( defined( 'LINKEDIN_KEY' ) && defined( 'LINKEDIN_SECRET' ) ) ||
	     ( defined( 'bitly_clientid' ) && defined( 'bitly_secret' ) ) ||
	     ( defined( 'PPP_FB_APP_ID' ) && defined( 'PPP_FB_APP_SECRET' ) )
	   ) {
		return;
	}

	$social_tokens = ppp_has_local_tokens();

	if ( empty( $social_tokens ) ) {
		define( 'PPP_LOCAL_TOKENS', false );
		$social_tokens = get_transient( 'ppp_social_tokens' );

		if ( ! $social_tokens ) {
			$license = trim( get_option( '_ppp_license_key' ) );
			$url = PPP_STORE_URL . '/?ppp-get-tokens&ppp-license-key=' . $license . '&ver=' . md5( time() . $license );
			$response = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => false ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$social_tokens = json_decode( wp_remote_retrieve_body( $response ) );

		}

	} else {
		define( 'PPP_LOCAL_TOKENS', true );
		delete_transient( 'ppp_social_tokens' );

		if ( isset( $social_tokens->options ) ) {
			foreach ( $social_tokens->options as $constant => $value ) {

				$constant = strtoupper( $constant );

				if ( defined( $constant ) ) {
					continue;
				}

				switch( $constant ) {

					case 'NO_AUTO_UPDATE':
						// Avoid the call to the API to check for software updates
						$value = is_bool( $value ) ? $value : false;
						define( 'NO_AUTO_UPDATE', $value );
						break;

				}
			}
		}
	}

	if ( false === PPP_LOCAL_TOKENS && ! isset( $social_tokens->error ) && ( isset( $social_tokens->twitter ) || isset( $social_tokens->facebook ) || isset( $social_tokens->linkedin ) ) ) {
		set_transient( 'ppp_social_tokens', $social_tokens, WEEK_IN_SECONDS );
	}

	do_action( 'ppp_set_social_token_constants', $social_tokens );
}

/**
 * Checks if the user has uploaded a social media tokens JSON file
 * @return boolean If a file exists, true, else false.
 */
function ppp_has_local_tokens() {

	$token_file   = apply_filters( 'ppp_local_social_token_path', ppp_get_upload_path() . '/ppp-social-tokens.json' );
	$local_tokens = false;

	if ( ! file_exists( $token_file ) ) {
		return $local_tokens;
	}

	$local_tokens = json_decode( file_get_contents( $token_file ) );

	// Failed to parse as JSON
	if ( false === $local_tokens ) {
		return $local_tokens;
	}

	// No social tokens found in the format we accept or it was empty
	if ( empty( $local_tokens ) || ( ! isset( $local_tokens->twitter ) || ! isset( $local_tokens->facebook ) || ! isset( $local_tokens->linkedin ) ) ) {
		return false;
	}

	return apply_filters( 'ppp_local_tokens', $local_tokens, $token_file );

}

/**
 * Generate the link for the share
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name from the cron'
 * @return string          The URL to the post, to share
 */
function ppp_generate_link( $post_id, $name, $scheduled = true ) {
	global $ppp_share_settings;
	$share_link = get_permalink( $post_id );

	if ( ppp_link_tracking_enabled() ) {
		$share_link = ppp_generate_link_tracking( $share_link, $post_id, $name );
	}

	if ( ppp_is_shortener_enabled() && $scheduled ) {
		$shortener_name = $ppp_share_settings['shortener'];
		$share_link = apply_filters( 'ppp_apply_shortener-' . $shortener_name, $share_link );
	}


	return apply_filters( 'ppp_share_link', $share_link );
}

/**
 * Given a link, determine if link tracking needs to be applied
 * @param  string $share_link The Link to share
 * @param  int    $post_id    The Post ID the link belongs to
 * @param  string $name       The Name string from the cron
 * @return string             The URL to post, with proper analytics applied if necessary
 */
function ppp_generate_link_tracking( $share_link, $post_id, $name ) {
	if ( ppp_link_tracking_enabled() ) {
		global $ppp_share_settings;
		$link_tracking_type = $ppp_share_settings['analytics'];

		// Given the setting name, devs can extend this and apply a filter of ppp_analytics-[setting value]
		// to apply their own rules for link tracking
		$share_link = apply_filters( 'ppp_analytics-' . $link_tracking_type, $share_link, $post_id, $name );
	}

	$share_link = apply_filters( 'ppp_generate_link_tracking', $share_link, $post_id, $name );

	return $share_link;
}

/**
 * Determines if the post being shared should has media attached
 * @param  int $post_id      Post ID
 * @param  string $network   The Network being shared to
 * @param  bool $use_media   If this share should use media or not
 * @return mixed             If a thumbnail is found returns the URL, otherwise returns false
 */
function ppp_post_has_media( $post_id, $network, $use_media, $attachment_id = false ) {
	if ( !$use_media || empty( $post_id ) || empty( $network ) ) {
		return false;
	}

	$thumb_id = empty( $attachment_id ) ? get_post_thumbnail_id( $post_id ) : $attachment_id;
	$thumb_url = wp_get_attachment_image_src( $thumb_id, 'ppp-' . $network . '-share-image', true );

	if ( isset( $thumb_url[0] ) && ! empty( $thumb_url[0] ) && !strpos( $thumb_url[0], 'wp-includes/images/media/default.png' ) ) {
		return $thumb_url[0];
	}

	return false;
}
