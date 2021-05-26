<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks to see if a session is set and it's appropriate to start one, and starts it if necessary
 * @return void
 */
function ppp_maybe_start_session() {
	if( !class_exists( 'TwitterOAuth' ) ) {
		require_once ( PPP_PATH . '/includes/libs/twitter/twitteroauth.php' );
	}

	$ret = false;
	if ( ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) && ! session_id() && ! headers_sent() ) {
		$ret = session_start();
	}

	return $ret;
}

/**
 * Returns if a link tracking method is enabled
 * @return boolean True if a form of link tracking is enabled, false if not
 */
function ppp_link_tracking_enabled() {
	global $ppp_share_settings;
	$result = false;

	if ( isset( $ppp_share_settings['analytics'] ) && $ppp_share_settings['analytics'] !== 'none' ) {
		$result =  true;
	}

	return apply_filters( 'ppp_is_link_tracking_enabled', $result );
}

/**
 * Get a post slug via the ID
 * @param  int $post_id The post ID
 * @return string       The slug of the post
 */
function ppp_get_post_slug_by_id( $post_id ) {
	$post_data = get_post( $post_id, ARRAY_A );
	$slug = $post_data['post_name'];

	return $slug;
}

/**
 * Get's the array of text replacements
 * @return array The array of text replacements, each with a token and description items
 */
function ppp_get_text_tokens() {
	return apply_filters( 'ppp_text_tokens', array() );
}

/**
 * Returns the number of says to setup shares for
 * @return  int The number of days
 */
function ppp_share_days_count() {
	return apply_filters( 'ppp_share_days_count', 6 );
}

/**
 * Returns if the shortener option is chosen
 * @return boolean	True/False if the shortener has been selected
 */
function ppp_is_shortener_enabled() {
	global $ppp_share_settings;

	return ( isset( $ppp_share_settings['shortener'] ) && !empty( $ppp_share_settings['shortener'] ) && $ppp_share_settings != '-1' );
}

/**
 * Strips slashes and html_entities_decode for sending to the networks.
 */
function ppp_entities_and_slashes( $string ) {
	return stripslashes( html_entity_decode( $string, ENT_COMPAT, 'UTF-8' ) );
}

/**
 * Runs hook for the social networks to add their thumbnail sizes
 * @return void
 */
function ppp_add_image_sizes() {
	do_action( 'ppp_add_image_sizes' );
}

/**
 * Return the array of supported post types
 *
 * @since  2.3
 * @return array Array of post types in a key/value store
 */
function ppp_supported_post_types() {
	$post_type_args = apply_filters( 'ppp_supported_post_type_args', array(
		'public'             => true,
		'show_ui'            => true,
	) );
	$post_types = get_post_types( $post_type_args, NULL, 'and' );

	$unsupported_post_types = array( 'wp_log', 'attachment' );
	foreach ( $unsupported_post_types as $unsupported_post_type ) {
		if ( array_key_exists( $unsupported_post_type, $post_types ) ) {
			unset( $post_types[ $unsupported_post_type ] );
		}
	}

	return apply_filters( 'ppp_supported_post_types', $post_types );
}

/**
 * Returns an array of the allowed post types
 *
 * @since  2.2.3
 * @return array Array of post types PPP should work for
 */
function ppp_allowed_post_types() {
	$ppp_options = get_option( 'ppp_options' );

	return apply_filters( 'ppp_schedule_share_post_types', array_keys( $ppp_options['post_types'] ) );
}

/**
 * Given a Post ID and Post object, should we try and save the metabox content
 * @param  int $post_id The Post ID being saved
 * @param  object $post WP_Post object of the post being saved
 * @return bool         Wether to save the metabox or not
 */
function ppp_should_save( $post_id, $post ) {
	$ret = true;

	if ( empty( $_POST ) ) {
		$ret = false;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		$ret = false;
	}

	global $ppp_options;
	if ( !isset( $ppp_options['post_types'] ) || !is_array( $ppp_options['post_types'] ) || !array_key_exists( $post->post_type, $ppp_options['post_types'] ) ) {
		$ret = false;
	}

	return apply_filters( 'ppp_should_save', $ret, $post );
}

/**
 * Verifies our directory exists, and it's protected
 *
 * @since  2.2
 * @return void
 */
function ppp_set_uploads_dir() {
	$upload_path = ppp_get_upload_path();

	if ( false === get_transient( 'ppp_check_protection_files' ) ) {

		// Make sure the /ppp folder is created
		wp_mkdir_p( $upload_path );

		// Prevent directory browsing and direct access to all files
		$rules  = "Options -Indexes\n";
		$rules .= "deny from all\n";

		$htaccess_exists = file_exists( $upload_path . '/.htaccess' );

		if ( $htaccess_exists ) {
			$contents = @file_get_contents( $upload_path . '/.htaccess' );
			if ( $contents !== $rules || ! $contents ) {
				// Update the .htaccess rules if they don't match
				@file_put_contents( $upload_path . '/.htaccess', $rules );
			}
		} elseif( wp_is_writable( $upload_path ) ) {
			// Create the file if it doesn't exist
			@file_put_contents( $upload_path . '/.htaccess', $rules );
		}

		// Top level blank index.php
		if ( ! file_exists( $upload_path . '/index.php' ) && wp_is_writable( $upload_path ) ) {
			@file_put_contents( $upload_path . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.' );
		}

		// Check for the files once per day
		set_transient( 'ppp_check_protection_files', true, 3600 * 24 );
	}
}
add_action( 'admin_init', 'ppp_set_uploads_dir' );

/**
 * The location of where we store our files for Local tokens
 *
 * @since  2.2
 * @return string The path to the /ppp folder in the uploads dir
 */
function ppp_get_upload_path() {
	$wp_upload_dir = wp_upload_dir();
	return $wp_upload_dir['basedir'] . '/ppp';
}

/**
 * Get's the array of completed upgrade actions
 *
 * @since  2.3
 * @return array The array of completed upgrades
 */
function ppp_get_completed_upgrades() {

	$completed_upgrades = get_option( 'ppp_completed_upgrades' );

	if ( false === $completed_upgrades ) {
		$completed_upgrades = array();
	}

	return $completed_upgrades;

}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since  2.3
 * @param  string $upgrade_action The upgrade action to check completion for
 * @return bool                   If the action has been added to the copmleted actions array
 */
function ppp_has_upgrade_completed( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades = ppp_get_completed_upgrades();

	return in_array( $upgrade_action, $completed_upgrades );

}

/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @since  2.3
 * @param  string $upgrade_action The action to add to the copmleted upgrades array
 * @return bool                   If the function was successfully added
 */
function ppp_set_upgrade_complete( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades   = ppp_get_completed_upgrades();
	$completed_upgrades[] = $upgrade_action;

	// Remove any blanks, and only show uniques
	$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

	return update_option( 'ppp_completed_upgrades', $completed_upgrades );
}

/**
 * Determines if the current site is a development or staging site.
 *
 * @return boolean
 */
function ppp_is_dev_or_staging() {
	$is_local_url = false;

	// Trim it up
	$url = strtolower( trim( get_home_url( '/' ) ) );

	// Need to get the host...so let's add the scheme so we can use parse_url
	if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
		$url = 'http://' . $url;
	}

	$url_parts = parse_url( $url );
	$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

	if ( ! empty( $url ) && ! empty( $host ) ) {

		if ( false !== ip2long( $host ) ) {
			if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				$is_local_url = true;
			}
		} else if ( 'localhost' === $host ) {
			$is_local_url = true;
		}

		$check_tlds = apply_filters( 'ppp_validate_tlds', true );
		if ( $check_tlds ) {
			$tlds_to_check = apply_filters( 'ppp_url_tlds', array(
				'.dev', '.local', '.test'
			) );

			foreach ( $tlds_to_check as $tld ) {
				if ( false !== strpos( $host, $tld ) ) {
					$is_local_url = true;
					continue;
				}
			}
		}

		if ( substr_count( $host, '.' ) > 1 ) {
			$subdomains_to_check = apply_filters( 'ppp_url_subdomains', array(
				'dev.', '*.staging.',
			) );

			foreach ( $subdomains_to_check as $subdomain ) {

				$subdomain = str_replace( '.', '(.)', $subdomain );
				$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );

				if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
					$is_local_url = true;
					continue;
				}
			}
		}
	}

	return apply_filters( 'ppp_is_local_url', $is_local_url, $url );
}
