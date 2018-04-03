<?php
use Abraham\TwitterOAuth\TwitterOAuth;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twitter Class
 *
 * Handles all twitter functions
 *
 */
if( !class_exists( 'PPP_Twitter' ) ) {

	class PPP_Twitter {

		var $twitter;

		public function __construct( $_user_id = 0 ) {
			ppp_maybe_start_session();

			$this->user_id = $_user_id;
		}

		/**
		 * Include Twitter Class
		 *
		 * Handles to load twitter class
		 */
		public function ppp_load_twitter() {
			ppp_set_social_tokens();

			if ( ! defined( 'PPP_TW_CONSUMER_KEY' ) || ! defined( 'PPP_TW_CONSUMER_SECRET' ) ) {
				return false;
			}

			$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET );

			return true;
		}

		public function revoke_access() {
			global $ppp_social_settings;

			unset( $ppp_social_settings['twitter'] );

			update_option( 'ppp_social_settings', $ppp_social_settings );
		}

		/**
		 * Initializes Twitter API
		 *
		 */
		public function ppp_initialize_twitter() {

			//when user is going to logged in in twitter and verified successfully session will create
			if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) {
				$ppp_social_settings = get_option( 'ppp_social_settings' );

				//load twitter class
				$twitter = $this->ppp_load_twitter();

				//check twitter class is loaded or not
				if( !$twitter ) {
					return false;
				}

				$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET, $_SESSION['ppp_twt_oauth_token'], $_SESSION['ppp_twt_oauth_token_secret'] );

				// Request access tokens from twitter
				$ppp_tw_access_token = $this->twitter->oauth( 'oauth/access_token', array( 'oauth_verifier' => $_REQUEST['oauth_verifier'] ) );

				$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET, $ppp_tw_access_token['oauth_token'], $ppp_tw_access_token['oauth_token_secret'] );

				//getting user data from twitter
				$response = $this->twitter->get('account/verify_credentials');

				//if user data get successfully
				if ( $response->id_str ) {

					$data['user'] = $response;
					$data['user']->accessToken = $ppp_tw_access_token;

					$ppp_social_settings['twitter'] = $data;
					update_option( 'ppp_social_settings', $ppp_social_settings );
				}
			}
		}

		public function ppp_verify_twitter_credentials() {
			$this->ppp_load_twitter();

			global $ppp_social_settings;
			if ( isset( $ppp_social_settings['twitter'] ) ) {

				$this->twitter = new TwitterOAuth(
					PPP_TW_CONSUMER_KEY,
					PPP_TW_CONSUMER_SECRET,
					$ppp_social_settings['twitter']['user']->accessToken['oauth_token'],
					$ppp_social_settings['twitter']['user']->accessToken['oauth_token_secret']
				);

				$response = $this->twitter->get('account/verify_credentials');
				if ( is_object( $response ) && property_exists( $response, 'errors' ) && count( $response->errors ) > 0 ) {
					foreach ( $response->errors as $error ) {
						if ( $error->code == 89 ) { // Expired or revoked tokens
							unset( $ppp_social_settings['twitter'] );
							update_option( 'ppp_social_settings', $ppp_social_settings );

							return array( 'error' => __( 'Post Promoter Pro has been removed from your Twitter account. Please reauthorize to continue promoting your content.', 'ppp-txt' ) );
						}
					}
				}
			}

			return true;
		}

		/**
		 * Get auth url for twitter
		 *
		 */
		public function ppp_get_twitter_auth_url ( $return_url = '' ) {

			if ( empty( $return_url ) ) {
				$return_url = admin_url( 'admin.php?page=ppp-social-settings' );
			}
			//load twitter class
			$twitter_loaded = $this->ppp_load_twitter();

			//check twitter class is loaded or not
			if( ! $twitter_loaded ) {
				return false;
			}

			$request_token = $this->twitter->oauth( 'oauth/request_token', array( 'oauth_callback' => $return_url ) );

			// If last connection failed don't display authorization link.
			switch( $this->twitter->getLastHttpCode() ) {

			case 200:
				$_SESSION['ppp_twt_oauth_token']        = $request_token['oauth_token'];
				$_SESSION['ppp_twt_oauth_token_secret'] = $request_token['oauth_token_secret'];

				$token = $request_token['oauth_token'];
				$url = $this->twitter->oauth(
					'oauth/authenticate',
					array(
						'oauth_token' => $token,
						'force_login' => 'true',
					)
				);
				break;
			default:
				$url = '';
				break;
			}
			return $url;
		}

		public function ppp_tweet( $message = '', $media = null ) {
			if ( empty( $message ) ) {
				return false;
			}

			$verify = $this->ppp_verify_twitter_credentials();
			if ( $verify === true ) {
				$args = array();
				$media_ids = array();

				if ( ! empty( $media ) ) {
					if ( is_array( $media ) ) {

						foreach ( $media as $media_item ) {
							$media_id = $this->upload_media( $media_item );
							if ( false !== $media_id ) {
								$media_ids[] = $media_id;
							}
						}

					} else {

						$media_id = $this->upload_media( $media );
						if ( false !== $media_id ) {
							$media_ids[] = $media_id;
						}

					}
				}

				$args['status'] = $message;

				if ( ! empty( $media_ids ) ) {
					if ( count( $media_ids ) > 4 ) {
						$media_ids = array_slice( $media_ids, 0, 4 );
					}
					$args['media_ids'] = implode( ',', $media_ids );
				}

				return $this->twitter->post( 'statuses/update', $args, false );
			} else {
				return false;
			}
		}

		public function upload_media( $media_url )  {
			$this->ppp_verify_twitter_credentials();
			$attachment_id = ppp_get_attachment_id_from_image_url( $media_url );

			$alt_text = false;

			if ( ! empty( $attachment_id ) ) {
				$alt_text = ppp_get_attachment_alt_text( $attachment_id );
			}

			$media_upload = array(
				'media' => $media_url,
			);

			$media_data = $this->twitter->upload( 'media/upload', $media_upload );

			if ( ! empty( $media_data->media_id ) ) {

				// We got a media ID back, if we have alt text, supply it.
				if ( ! empty( $alt_text ) ) {

					$media_meta = array(
						'media_id' => (string) $media_data->media_id,
						'alt_text' => array(
							'text' => $alt_text
						)
					);

					$media_meta = array( 'json' => json_encode( $media_meta ) );
					var_dump( $this->twitter->http( 'POST', $this->twitter->UPLOAD_HOST, 'media/metadata/create', $media_meta ) );

				}
exit;
				return $media_data->media_id;

			}

			return false;
		}

	}

}
