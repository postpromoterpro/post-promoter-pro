<?php


/**
 * @group ppp_social
 */
class Tests_Twitter extends WP_UnitTestCase {
	protected $object;

	public static $_post_id;

	public static function wpSetUpBeforeClass() {
		self::$_post_id = self::factory()->post->create( array( 'post_title' => 'Test Post', 'post_type' => 'post', 'post_status' => 'publish' ) );
	}

	public function test_twitter_enabled() {
		$this->assertFalse( ppp_twitter_enabled() );
	}

	public function test_registration_function() {
		$services = ppp_tw_register_service();
		$this->assertTrue( in_array( 'tw', $services ) );
	}

	public function test_twitter_icon() {
		$expected = '<span class="dashicons icon-ppp-tw"></span>';
		$this->assertEquals( $expected, ppp_tw_account_list_icon() );
	}

	public function test_twitter_account() {
	}

	public function test_twitter_meta_description() {
		$pre_description = '<img src="test.jpg" />This is the excerpt';
		$this->assertEquals( 'This is the excerpt', ppp_tw_format_card_description( $pre_description ) );
	}

}
