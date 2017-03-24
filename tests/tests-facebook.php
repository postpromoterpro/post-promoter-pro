<?php


/**
 * @group ppp_social
 */
class Tests_Facebook extends WP_UnitTestCase {
	protected $object;

	public static $_post_id;

	public static function wpSetUpBeforeClass() {
		self::$_post_id = self::factory()->post->create( array( 'post_title' => 'Test Post', 'post_type' => 'post', 'post_status' => 'publish' ) );
	}

	private function enable_facebook() {
		global $ppp_social_settings;
		$facebook_user = new stdClass;
		$facebook_user->access_token = 'TestAccessToken';
		$facebook_user->expires_on   = time() + WEEK_IN_SECONDS;
		$facebook_user->name         = 'Test Facebook Account';
		$facebook_user->userid       = '123';
		$facebook_user->avatar       = 'https://graph.facebook.com/100004652499860/picture?type=square';

		$ppp_social_settings = array(
			'facebook' => $facebook_user,
		);

		return $facebook_user;
	}

	private function disable_facebook() {
		global $ppp_social_settings;

		unset( $ppp_social_settings['facebook'] );
	}

	public function test_facebook_enabled() {
		$this->assertFalse( ppp_facebook_enabled() );

		$this->enable_facebook();
		$this->assertTrue( ppp_facebook_enabled() );
	}

	public function test_registration_function() {
		$services = ppp_fb_register_service();
		$this->assertTrue( in_array( 'fb', $services ) );
	}

	public function test_facebook_icon() {
		$expected = '<span class="dashicons icon-ppp-fb"></span>';
		$this->assertEquals( $expected, ppp_fb_account_list_icon() );
	}

	public function test_facebook_avatar() {
		$facebook_user   = $this->enable_facebook();
		$this->assertContains( $facebook_user->avatar, ppp_fb_account_list_avatar() );
	}

	public function test_facebook_name() {
		$facebook_user = $this->enable_facebook();
		$this->assertEquals( $facebook_user->name, ppp_fb_account_list_name() );
	}

	public function test_facebook_actions() {
		$this->enable_facebook();
		$actions_string = ppp_fb_account_list_actions();
		$this->assertContains( 'Disconnect from Facebook', $actions_string );
	}

	public function test_facebook_query_vars() {
		$registered_query_vars = apply_filters( 'query_vars', array() );
		$this->assertTrue( in_array( 'fb_access_token', $registered_query_vars ) );
		$this->assertTrue( in_array( 'expires_in', $registered_query_vars ) );
	}

	public function test_facebook_registered_image_size() {
		$registered_thumbnails = get_intermediate_image_sizes();
		$this->assertTrue( in_array( 'ppp-fb-share-image', $registered_thumbnails ) );
	}

	public function test_facebook_meta_box() {
		$this->enable_facebook();
		$tabs = ppp_fb_add_meta_tab( array() );
		$this->assertTrue( array_key_exists( 'fb', $tabs ) );

		$content_areas = ppp_fb_register_metabox_content( array() );
		$this->assertTrue( in_array( 'fb', $content_areas ) );
	}

}
