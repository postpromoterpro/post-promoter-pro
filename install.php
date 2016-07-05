<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * On activation, setup the default options
 * @return void
 */
function post_promoter_pro_activation_setup() {
	// If the settings already exist, don't do this
	if ( get_option( 'ppp_options' ) ) {
		return;
	}

	$default_settings['post_types']['post'] = '1';
	update_option( 'ppp_options', $default_settings );

	$default_share_settings['twitter']['share_on_publish']  = '1';
	$default_share_settings['facebook']['share_on_publish'] = '1';
	$default_share_settings['linkedin']['share_on_publish'] = '1';
	update_option( 'ppp_share_settings', $default_share_settings );

	set_transient( '_ppp_activation_redirect', 'true', 30 );

	ppp_set_upgrade_complete( 'upgrade_post_meta' );
}
register_activation_hook( PPP_FILE, 'post_promoter_pro_activation_setup' );