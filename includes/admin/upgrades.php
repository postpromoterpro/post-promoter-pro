<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ppp_upgrade_notices() {

	if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'ppp-about' || $_GET['page'] == 'ppp-upgrades' ) ) {
		return; // Don't show notices on the upgrades page
	}

	$ppp_version = get_option( 'ppp_version' );

	// Sequential Orders was the first stepped upgrade, so check if we have a stalled upgrade
	$resume_upgrade = ppp_maybe_resume_upgrade();
	if ( ! empty( $resume_upgrade ) ) {

		$resume_url = add_query_arg( $resume_upgrade, admin_url( 'index.php' ) );
		printf(
			'<div class="error"><p>' . __( 'Post Promoter Pro needs to complete a database upgrade that was previously started, click <a href="%s">here</a> to resume the upgrade.', 'ppp-txt' ) . '</p></div>',
			esc_url( $resume_url )
		);

	} else {

		// Include all 'Stepped' upgrade process notices in this else statement,
		// to avoid having a pending, and new upgrade suggested at the same time

		if ( version_compare( $ppp_version, '2.2', '<' ) || ! ppp_has_upgrade_completed( 'upgrade_post_meta' ) ) {
			printf(
				'<div class="notice notice-info"><p>' . __( 'Post Promoter Pro needs to upgrade share override data, click <a href="%s">here</a> to start the upgrade.', 'ppp-txt' ) . '</p></div>',
				esc_url( admin_url( 'index.php?page=ppp-upgrades&ppp-upgrade=upgrade_post_meta' ) )
			);
		}

		if ( version_compare( $ppp_version, '2.3.19', '<' ) || ! ppp_has_upgrade_completed( 'fix_scheduled_shares_2319' ) ) {
			printf(
				'<div class="notice notice-info"><p>' . __( 'Post Promoter Pro needs to fix an issue with scheduled shares, click <a href="%s">here</a> to start the upgrade.', 'ppp-txt' ) . '</p></div>',
				esc_url( admin_url( 'index.php?page=ppp-upgrades&ppp-upgrade=fix_scheduled_shares_2319' ) )
			);
		}

		// End 'Stepped' upgrade process notices

	}

}
add_action( 'admin_notices', 'ppp_upgrade_notices' );

/**
 * Run any upgrade routines needed depending on versions
 * @return void
 */
function ppp_upgrade_plugin() {
	$ppp_version = get_option( 'ppp_version' );

	$upgrades_executed = false;

	// We don't have a version yet, so we need to run the upgrader
	if ( !$ppp_version && PPP_VERSION == '1.3' ) {
		ppp_v13_upgrades();
		$upgrades_executed = true;
	}

	if ( version_compare( $ppp_version, 2.1, '<' ) ) {
		ppp_v21_upgrades();
		$upgrades_executed = true;
	}

	if ( $upgrades_executed || version_compare( $ppp_version, PPP_VERSION, '<' ) ) {
		set_transient( '_ppp_activation_redirect', '1', 60 );
		update_option( 'ppp_version', PPP_VERSION );
	}

}

/** Helper Functions **/

/**
 * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle
 * @since 2.2.6
 * @return mixed   When nothing to resume returns false, otherwise starts the upgrade where it left off
 */
function ppp_maybe_resume_upgrade() {

	$doing_upgrade = get_option( 'ppp_doing_upgrade', false );

	if ( empty( $doing_upgrade ) ) {
		return false;
	}

	return $doing_upgrade;

}

/** End Helper Functions **/

/**
 * Run the 1.3 upgrades
 * @return void
 */
function ppp_v13_upgrades() {
	global $ppp_share_settings;
	$uq_status = ( isset( $ppp_share_settings['ppp_unique_links'] ) && $ppp_share_settings['ppp_unique_links'] == '1' ) ? $ppp_share_settings['ppp_unique_links'] : 0;
	$ga_status = ( isset( $ppp_share_settings['ppp_ga_tags'] ) && $ppp_share_settings['ppp_ga_tags'] == '1' ) ? $ppp_share_settings['ppp_ga_tags'] : 0;

	if ( $uq_status ) {
		$ppp_share_settings['analytics'] = 'unique_links';
		unset( $ppp_share_settings['ppp_unique_links'] );
	} elseif ( $ga_status ) {
		$ppp_share_settings['analytics'] = 'google_analytics';
		unset( $ppp_share_settings['ppp_ga_tags'] );
	}

	update_option( 'ppp_share_settings', $ppp_share_settings );

	global $ppp_options;
	$ppp_options['default_text'] = '{post_title}';
	$ppp_options['days'] = array( 'day1' => 'on', 'day2' => 'on', 'day3' => 'on', 'day4' => 'on', 'day5' => 'on', 'day6' => 'on');

	update_option( 'ppp_options', $ppp_options );
}

/**
 * Run the 2.1 updates
 * @return void
 */
function ppp_v21_upgrades() {
	// Auto load was set to true, let's make that false, we don't always need the version
	delete_option( 'ppp_version' );
	add_option( 'ppp_version', PPP_VERSION, '', 'no' );
}

/**
 * Run the 2.2 upgrades
 *
 * @return void
 */
function ppp_v22_postmeta_upgrade() {

	if( ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
		wp_die( __( 'You do not have permission to do upgrades', 'ppp-txt' ), __( 'Error', 'ppp-txt' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! ini_get( 'safe_mode' ) ) {
		@set_time_limit(0);
	}

	global $wpdb;


	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$number = 25;
	$offset = $step == 1 ? 0 : ( $step - 1 ) * $number;

	if ( $step < 2 ) {
		// Check if we have any payments before moving on
		$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ppp_post_override_data' LIMIT 1";
		$has_overrides = $wpdb->get_col( $sql );

		if( empty( $has_overrides ) ) {
			// We had no payments, just complete
			update_option( 'ppp_version', preg_replace( '/[^0-9.].*/', '', PPP_VERSION ) );
			ppp_set_upgrade_complete( 'upgrade_post_meta' );
			delete_option( 'ppp_doing_upgrade' );
			wp_redirect( admin_url() ); exit;
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;

	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(post_id) as total FROM $wpdb->postmeta WHERE meta_key = '_ppp_post_override_data'";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total     = $results->total;
	}

	$results       = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_ppp_post_override_data' ORDER BY meta_id DESC LIMIT %d,%d;", $offset, $number ) );
	$new_post_meta = array();

	if ( $results ) {
		foreach ( $results as $result ) {

			$share_key     = 1;

			$override_data = unserialize( $result->meta_value );

			foreach ( $override_data as $day => $values ) {

				if ( ! isset( $values['enabled'] ) ) {
					continue;
				}

				$text = ! empty( $values['text'] ) ? $values['text'] : '';
				$time = ! empty( $values['time'] ) ? $values['time'] : '8:00am';

				$post          = get_post( $result->post_id );
				$days_ahead    = substr( $day, -1 );
				$date          = date( 'm\/d\/Y', strtotime( $post->post_date . '+' . $days_ahead . ' days' ) );
				$image         = '';
				$attachment_id = '';

				if ( ! empty( $values['use_image'] ) ) {
					$thumb_id  = get_post_thumbnail_id( $result->post_id );
					$thumb_url = wp_get_attachment_image_src( $thumb_id, 'ppp-tw-share-image', true );

					if ( isset( $thumb_url[0] ) && ! empty( $thumb_url[0] ) && !strpos( $thumb_url[0], 'wp-includes/images/media/default.png' ) ) {
						$thumb_url = $thumb_url[0];
					}

					if ( ! empty( $thumb_id ) && ! empty( $thumb_url ) ) {
						$attachment_id = $thumb_id;
						$image         = $thumb_url;
					}
				}

				$new_post_meta[$share_key] = array (
					'date'          => $date,
					'time'          => $time,
					'text'          => $text,
					'image'         => ! empty( $image ) ? $image : '',
					'attachment_id' => ! empty( $attachment_id ) ? $attachment_id : ''
				);

				$share_key++;

			}

			update_post_meta( $result->post_id, '_ppp_tweets', $new_post_meta );
		}

		// Postmeta found so upgrade them
		$step++;
		$redirect = add_query_arg( array(
			'page'        => 'ppp-upgrades',
			'ppp-upgrade' => 'upgrade_post_meta',
			'step'        => $step,
			'number'      => $number,
			'total'       => $total
		), admin_url( 'index.php' ) );
		wp_redirect( $redirect ); exit;

	} else {

		// No more postmeta found, finish up
		update_option( 'ppp_version', preg_replace( '/[^0-9.].*/', '', PPP_VERSION ) );
		ppp_set_upgrade_complete( 'upgrade_post_meta' );
		delete_option( 'ppp_doing_upgrade' );
		wp_redirect( admin_url() ); exit;

	}
}
add_action( 'ppp_upgrade_post_meta', 'ppp_v22_postmeta_upgrade' );

function ppp_fix_scheduled_shares_2319() {

	if( ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
		wp_die( __( 'You do not have permission to do upgrades', 'ppp-txt' ), __( 'Error', 'ppp-txt' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! ini_get( 'safe_mode' ) ) {
		@set_time_limit(0);
	}

	global $wpdb;


	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$number = 25;
	$offset = $step == 1 ? 0 : ( $step - 1 ) * $number;

	if ( $step < 2 ) {
		// Check if we have any payments before moving on
		$sql = "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key IN ( '_ppp_tweets', '_ppp_fb_shares', '_ppp_li_shares' ) LIMIT 1";
		$has_shares = $wpdb->get_col( $sql );

		if( empty( $has_shares ) ) {
			// We had no payments, just complete
			update_option( 'ppp_version', preg_replace( '/[^0-9.].*/', '', PPP_VERSION ) );
			ppp_set_upgrade_complete( 'fix_scheduled_shares_2319' );
			delete_option( 'ppp_doing_upgrade' );
			wp_redirect( admin_url() ); exit;
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;

	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT( DISTINCT post_id ) as total FROM $wpdb->postmeta WHERE meta_key IN ( '_ppp_tweets', '_ppp_fb_shares', '_ppp_li_shares' )";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total     = $results->total;
	}

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key IN ( '_ppp_tweets', '_ppp_fb_shares', '_ppp_li_shares' ) ORDER BY post_id DESC LIMIT %d,%d;", $offset, $number ) );

	if ( $results ) {
		$allowed_post_types = ppp_allowed_post_types();

		foreach ( $results as $result ) {
			$post = get_post( $result->post_id );
			if ( ! in_array( $post->post_type, $allowed_post_types ) ) {
				continue;
			}

			$timestamps = ppp_get_timestamps( $result->post_id );

			foreach ( $timestamps as $timestamp => $name ) {
				$timestamp = substr( $timestamp, 0, strlen( $timestamp ) - 3 );
				wp_schedule_single_event( $timestamp, 'ppp_share_post_event', array( $result->post_id, $name ) );
			}
		}

		// Postmeta found so upgrade them
		$step++;
		$redirect = add_query_arg( array(
			'page'        => 'ppp-upgrades',
			'ppp-upgrade' => 'fix_scheduled_shares_2319',
			'step'        => $step,
			'number'      => $number,
			'total'       => $total
		), admin_url( 'index.php' ) );
		wp_redirect( $redirect ); exit;

	} else {

		// No more postmeta found, finish up
		update_option( 'ppp_version', preg_replace( '/[^0-9.].*/', '', PPP_VERSION ) );
		ppp_set_upgrade_complete( 'fix_scheduled_shares_2319' );
		delete_option( 'ppp_doing_upgrade' );
		wp_redirect( admin_url() ); exit;

	}
}
add_action( 'ppp_fix_scheduled_shares_2319', 'ppp_fix_scheduled_shares_2319' );
