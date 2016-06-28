<?php


/**
 * @group ppp_share
 */
class Tests_Share extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->_post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_type' => 'post', 'post_status' => 'publish' ) );
	}

	public function test_get_timestamps() {
		$timestamps = ppp_get_timestamps( $this->_post_id );

		$this->assertInternalType( 'array', $timestamps );
		$this->assertEmpty( $timestamps );

		// Verify a past date doesn't save
		$tweet_data[1] = array(
			'date' => '1/1/2015',
			'time' => '12:00pm',
		);

		update_post_meta( $this->_post_id, '_ppp_tweets', $tweet_data );
		$timestamps = ppp_get_timestamps( $this->_post_id );
		$this->assertEmpty( $timestamps );

		$tweet_data[1] = array(
			'date' => date( 'm/d/Y', time() + 86400 ),
			'time' => '12:00pm',
		);
		update_post_meta( $this->_post_id, '_ppp_tweets', $tweet_data );
		$timestamps = ppp_get_timestamps( $this->_post_id );
		$this->assertNotEmpty( $timestamps );

		$found_timestamp = strtotime( $tweet_data[1]['date'] . ' ' . $tweet_data[1]['time'] ) . '_tw';
		$timestamp = key( $timestamps );
		$this->assertEquals( $timestamp, $found_timestamp );
		$this->assertEquals( 'sharedate_1_'. $this->_post_id . '_tw', $timestamps[ $found_timestamp ] );


		$tweet_data[1] = array(
			'date' => date( 'm/d/Y', time() + 86400 ),
			'time' => '12:00pm',
		);

		$tweet_data[2] = array(
			'date' => '1/1/2015',
			'time' => '12:00pm',
		);
		update_post_meta( $this->_post_id, '_ppp_tweets', $tweet_data );
		$timestamps = ppp_get_timestamps( $this->_post_id );
		$this->assertEquals( 1, count( $timestamps ) );


		$tweet_data[1] = array(
			'date' => date( 'm/d/Y', time() + 86400 ),
			'time' => '12:00pm',
		);

		$tweet_data[2] = array(
			'date' => date( 'm/d/Y', time() + 86400 + 86400 ),
			'time' => '12:00pm',
		);
		update_post_meta( $this->_post_id, '_ppp_tweets', $tweet_data );
		$timestamps = ppp_get_timestamps( $this->_post_id );
		$this->assertEquals( 2, count( $timestamps ) );
		$found_timestamp = strtotime( $tweet_data[2]['date'] . ' ' . $tweet_data[2]['time'] ) . '_tw';
		$this->assertEquals( 'sharedate_2_'. $this->_post_id . '_tw', $timestamps[ $found_timestamp ] );

	}

	public function test_has_local_tokens() {
		$this->assertFalse( ppp_has_local_tokens() );

	}

	public function test_generate_link_general() {
		$link = ppp_generate_link( $this->_post_id, 'sharedate_1_' . $this->_post_id, false );
		$this->assertEquals( 'http://example.org/?p=' . $this->_post_id, $link );
	}



}
