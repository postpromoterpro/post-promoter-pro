<?php


/**
 * @group ppp_cron
 */
class Tests_Cron extends WP_UnitTestCase {
	protected $object;

	public static $_post_id;

	public static function wpSetUpBeforeClass() {
		self::$_post_id = self::factory()->post->create( array( 'post_title' => 'Test Post', 'post_type' => 'post', 'post_status' => 'publish' ) );

		add_filter( 'ppp_get_scheduled_crons', array( 'Tests_Cron', 'add_crons' ) );
	}

	public function test_get_scheduled_crons() {
		$scheduled_crons = ppp_get_shceduled_crons();

		$this->assertInternalType( 'array', $scheduled_crons );
		$this->assertTrue( ! empty( $scheduled_crons ) );
	}

	public function test_ppp_has_cron_within() {
		$current_time = current_time( 'timestamp' );

		$this->assertTrue( ppp_has_cron_within() );
		$this->assertTrue( ppp_has_cron_within( $current_time ) );
		$this->assertFalse( ppp_has_cron_within( $current_time + WEEK_IN_SECONDS, 60 ) );
		$this->assertFalse( ppp_has_cron_within( $current_time - WEEK_IN_SECONDS, 60 ) );

	}

	public static function add_crons() {
		$test_crons = array(
			'ef1e2ad70394f45f6281fe7281be8c2e' => array(
				'schedule' => false,
				'args'     => array(
					self::$_post_id,
					'sharedate_3_' . self::$_post_id
				),
				'timestamp' => current_time( 'timestamp' )
			)
		);

		return $test_crons;

	}

}
