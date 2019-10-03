<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Linkedin Class
 *
 * Handles all linkedin functions
 *
 */
if( !class_exists( 'PPP_Linkedin' ) ) {

	class PPP_Linkedin {

		var $linkedin;

		public function __construct(){
			ppp_maybe_start_session();
		}

		/**
		 * Include Linkedin Class
		 *
		 * Handles to load linkedin class
		 */
		public function ppp_load_linkedin() {

				if( !class_exists( 'LinkedIn' ) ) {
					require_once ( PPP_PATH . '/includes/libs/linkedin/linkedin_oAuth.php' );
				}


				ppp_set_social_tokens();

				if ( ! defined( 'LINKEDIN_KEY' ) || ! defined( 'LINKEDIN_SECRET' ) ) {
					return false;
				}

				global $ppp_social_settings;
				$config = array( 'appKey' => LINKEDIN_KEY, 'appSecret' => LINKEDIN_SECRET );
				if ( isset( $ppp_social_settings['linkedin']->access_token ) ) {
					$config['accessToken'] = $ppp_social_settings['linkedin']->access_token;
				}

				if ( !$this->linkedin ) {
					$this->linkedin = new LinkedIn( $config );
				}

				return true;
		}

		/**
		 * Initializes Linkedin API
		 *
		 */
		public function ppp_initialize_linkedin() {
			$linkedin = $this->ppp_load_linkedin();

			//when user is going to logged in and verified successfully session will create
			if ( isset( $_REQUEST['li_access_token'] ) && isset( $_REQUEST['expires_in'] ) ) {

				$access_token = $_REQUEST['li_access_token'];
				$expires_in   = $_REQUEST['expires_in'];

			} elseif ( isset( $_GET['state'] ) && strpos( $_GET['state'], 'ppp-local-keys-li' ) !== false ) {
				$access_code = isset( $_GET['code'] ) ? $_GET['code'] : false;

				if ( empty( $access_code ) ) {
					return;
				}

				$params  = 'grant_type=authorization_code&client_id=' . LINKEDIN_KEY;
				$params .= '&client_secret=' . LINKEDIN_SECRET;
				$params .= '&code=' . $access_code;
				$params .= '&redirect_uri=' . admin_url( 'admin.php?page=ppp-social-settings' );
				$url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . $params;
				$response = json_decode( wp_remote_retrieve_body( wp_remote_post( $url ) ) );

				$access_token = isset( $response->access_token ) ? $response->access_token : false;
				$expires_in   = isset( $response->expires_in ) ? $response->expires_in : false;

			}

			if ( ! empty( $access_token ) && ! empty( $expires_in ) ) {
				global $ppp_social_settings;
				$ppp_social_settings = get_option( 'ppp_social_settings' );

				//check linkedin class is loaded or not
				if( !$linkedin ) {
					return false;
				}

				$data = new stdClass();
				$data->access_token = $access_token;

				$expires_in = (int) $expires_in;
				$data->expires_on = current_time( 'timestamp' ) + $expires_in;

				update_option( '_ppp_linkedin_refresh', current_time( 'timestamp' ) + round( $expires_in/1.25 ) );

				$ppp_social_settings['linkedin'] = $data;
				update_option( 'ppp_social_settings', $ppp_social_settings );
				// Now that we have a valid auth, get some user info
				$user_info = json_decode( $this->ppp_linkedin_profile( $data->access_token ) );

				$ppp_social_settings['linkedin']->id        = $user_info->id;
				$ppp_social_settings['linkedin']->firstName = $user_info->localizedFirstName;
				$ppp_social_settings['linkedin']->lastName  = $user_info->localizedLastName;

				update_option( 'ppp_social_settings', $ppp_social_settings );

				$url = remove_query_arg( array( 'li_access_token' , 'expires_in' ) );
				wp_redirect( $url );
				die();
			}
		}

		/**
		 * Get auth url for linkedin
		 *
		 */
		public function ppp_get_linkedin_auth_url ( $return_url ) {

			if ( ! PPP_LOCAL_TOKENS ) {
				$base_url = 'https://postpromoterpro.com/?ppp-social-auth';
				$url  = $base_url . '&ppp-service=li&ppp-license-key=' . trim( get_option( '_ppp_license_key' ) );
				$url .= '&nocache';
				$url .= '&return_url=' . esc_url( $return_url );
			} else {
				$url  = 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code';
				$url .= '&client_id=' . LINKEDIN_KEY;
				$url .= '&scope=w_share%20r_basicprofile';
				$url .= '&state=ppp-local-keys-li';
				$url .= '&redirect_uri=' . $return_url;
			}

			return $url;
		}

		/**
		 * Share somethign on linkedin
		 */
		public function ppp_linkedin_share( $args ) {
			if ( empty( $args ) ) {
				return false;
			}

			$this->ppp_load_linkedin();
			global $ppp_social_settings;
			$li_user_info = $ppp_social_settings['linkedin'];
			$url          = 'https://api.linkedin.com/v2/shares/';

			$share = array(
				'content' => array(
					'contentEntities' => array(
						array(
							'entityLocation' => $args['submitted-url'],
						)
					),
					'description' => $args['description'],
					'title' => $args['title'],
				),
				'owner' => 'urn:li:person:' . $li_user_info->id,
			);


			if ( $args['submitted-image-url'] !== false ) {
				$share['content']['contentEntities'][0]['thumbnails'] = array(
					array(
						'resolvedUrl' => $args['submitted-image-url'],
					)
				);
			}

			$headers = array( 'X-Restli-Protocol-Version' => '2.0.0', 'Authorization' => 'Bearer ' . $ppp_social_settings['linkedin']->access_token, 'Content-Type' => 'application/json' );
			$request = wp_remote_post( $url, array( 'httpversion' => '1.1', 'timeout' => '30', 'headers' => $headers, 'body' => json_encode( $share ) ) );

			return json_decode( wp_remote_retrieve_body( $request ) );
		}

		public function ppp_linkedin_profile( $access_token ) {
			$url = 'https://api.linkedin.com/v2/me';

			$headers = array( 'Authorization' => 'Bearer ' . $access_token );

			$request = wp_remote_get( $url, array( 'headers' => $headers ) );
			$data = wp_remote_retrieve_body( $request );
			return $data;
		}
	}

}
