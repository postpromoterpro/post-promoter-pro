<?php

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = '';
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

define( 'EDD_USE_PHP_SESSIONS', false );

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../post-promoter-pro.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

activate_plugin( 'post-promoter-pro/post-promoter-pro.php' );

echo "Installing Post Promoter Pro...\n";

global $current_user, $ppp_loaded;

$ppp_loaded->activation_setup();

$ppp_social_settings = get_option( 'ppp_social_settings' );
$ppp_share_settings  = get_option( 'ppp_share_settings' );

$current_user = new WP_User(1);
$current_user->set_role('administrator');

// Include helpers
require_once 'helpers/shims.php';
