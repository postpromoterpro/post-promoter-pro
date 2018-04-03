<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( PPP_PATH . '/includes/wpme-functions.php' );

global $ppp_twitter_oauth;
include_once( PPP_PATH . '/includes/twitter-functions.php' );
require_once( PPP_PATH . '/includes/libs/twitter.php');
$ppp_twitter_oauth = new PPP_Twitter();

// Also include the user specific Twitter Class ( separate for now )
require_once( PPP_PATH . '/includes/libs/twitter-user.php' );

global $ppp_facebook_oauth;
include_once( PPP_PATH . '/includes/facebook-functions.php' );
require_once( PPP_PATH . '/includes/libs/facebook.php');
$ppp_facebook_oauth = new PPP_Facebook();

global $ppp_bitly_oauth;
include_once( PPP_PATH . '/includes/bitly-functions.php' );
require_once( PPP_PATH . '/includes/libs/bitly.php' );
$ppp_bitly_oauth = new PPP_Bitly();

global $ppp_linkedin_oauth;
include_once( PPP_PATH . '/includes/linkedin-functions.php' );
require_once( PPP_PATH . '/includes/libs/linkedin.php' );
$ppp_linkedin_oauth = new PPP_Linkedin();

include_once( PPP_PATH . '/includes/pinterest-functions.php' );
