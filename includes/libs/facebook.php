<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Facebook Class
 *
 * Handles all facebook functions
 *
 */
if( !class_exists( 'PPP_Facebook' ) ) {

	class PPP_Facebook {

		var $facebook;

		public function __construct(){
			ppp_maybe_start_session();
		}

		/**
		 * Include Facebook Class
		 *
		 * Handles to load facebook class
		 *
		 */
		public function ppp_load_facebook() {


			if( !class_exists( 'Facebook' ) ) {
				require_once ( PPP_PATH . '/includes/libs/facebook/facebook.php' );
			}

			ppp_set_social_tokens();

			$this->facebook = new Facebook( array(
					'appId' => PPP_FB_APP_ID,
					'secret' => PPP_FB_APP_SECRET,
					'cookie' => true
			));

			return true;

		}

		/**
		 * Initializes Facebook API
		 *
		 */
		public function ppp_initialize_facebook() {
			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//when user is going to logged in and verified successfully session will create
			if ( isset( $_REQUEST['fb_access_token'] ) && isset( $_REQUEST['expires_in'] ) ) {

				$access_token = $_REQUEST['fb_access_token'];
				$expires_in   = $_REQUEST['expires_in'];

			} elseif ( isset( $_GET['state'] ) && strpos( $_GET['state'], 'ppp-local-keys-fb' ) !== false ) {
				$access_code = isset( $_GET['code'] ) ? $_GET['code'] : false;

				if ( empty( $access_code ) ) {
					return;
				}

				$params  = '?client_id=' . PPP_FB_APP_ID;
				$params .= '&client_secret=' . PPP_FB_APP_SECRET;
				$params .= '&code=' . $access_code;
				$params .= '&redirect_uri=' . admin_url( 'admin.php?page=ppp-social-settings' );
				$url = 'https://graph.facebook.com/oauth/access_token' . $params;

				$access_token = '';
				$expires      = '';
				parse_str( wp_remote_retrieve_body( wp_remote_post( $url ) ) );

				$access_token = ! empty( $access_token ) ? $access_token : false;
				$expires_in   = ! empty( $expires ) ? $expires : false;

			}

			if ( ! empty( $access_token ) ) {
				global $ppp_social_settings;
				$ppp_social_settings = get_option( 'ppp_social_settings' );

				//check facebook class is loaded or not
				if( !$facebook ) {
					return false;
				}

				$data = new stdClass();
				$data->access_token = $access_token;

				$expires_in = empty( $expires_in ) ? 60 * 24 * 60 * 60 : $expires_in; // days * hours * minutes * seconds
				$data->expires_on = current_time( 'timestamp' ) + $expires_in;

				update_option( '_ppp_facebook_refresh', current_time( 'timestamp' ) + round( $expires_in/1.25 ) );

				// Now that we have a valid auth, get some user info
				$user_info = $this->ppp_get_fb_user( $data->access_token );

				if ( $user_info ) {
					if ( !empty( $user_info->name ) ) {
						$data->name = $user_info->name;
					} else {
						$parsed_name = $user_info->first_name . ' ' . $user_info->last_name;
						$data->name = $parsed_name;
					}
					$data->userid = $user_info->id;
					$data->avatar = $this->ppp_fb_get_profile_picture( array( 'type' => 'square' ), $data->userid );

					if ( ! empty( $ppp_social_settings['facebook']->page ) ) {
						$current_page = $ppp_social_settings['facebook']->page;
						$page_parts   = explode( '|', $current_page );

						$pages = $this->ppp_get_fb_user_pages( $data->access_token );

						foreach ( $pages as $page ) {
							if ( $page->id == $page_parts[2] ) {
								$data->page = $page->name . '|' . $page->access_token . '|' . $page->id;
								continue;
							}
						}

					}

					$ppp_social_settings['facebook'] = $data;

					update_option( 'ppp_social_settings', $ppp_social_settings );
				}


				$url = remove_query_arg( array( 'fb_access_token' , 'expires_in' ) );
				wp_redirect( $url );
				die();
			}
		}

		/**
		 * Get Facebook User
		 *
		 * Handles to return facebook user id
		 *
		 */
		public function ppp_get_fb_user( $access_token ) {

			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//check facebook class is exis or not
			if( !$facebook ) {
				return false;
			}

			global $ppp_social_settings;
			$user = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/me?access_token=' . $access_token ) ) );

			return $user;

		}

		public function ppp_get_fb_user_pages( $access_token ) {

			// load facebook class
			$facebook = $this->ppp_load_facebook();

			// check facebook cleast is exists or not
			if( !$facebook ) {
				return false;
			}

			global $ppp_social_settings;
			$facebook_settings = $ppp_social_settings['facebook'];

			if ( ! isset( $facebook_settings->available_pages ) ||
				 ! isset( $facebook_settings->pages_last_updated ) ||
				 $facebook_settings->pages_last_updated < current_time( 'timestamp' ) ) {

				$all_pages = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/me/accounts?access_token=' . $access_token ) ) );
				$pages = array();

				if ( !empty( $all_pages ) ) {
					foreach ( $all_pages->data as $page ) {
						if ( in_array( 'CREATE_CONTENT', $page->perms ) ) {
							$pages[] = $page;

							if ( ! empty( $ppp_social_settings['facebook']->page ) && strpos( $ppp_social_settings['facebook']->page, $page->id ) ) {
								$ppp_social_settings['facebook']->page = $page->name . '|' . $page->access_token . '|' . $page->id;
							}
						}
					}
				} else {
					$pages = false;
				}


				$pages = (object) $pages;
				$ppp_social_settings['facebook']->available_pages = $pages;
				$ppp_social_settings['facebook']->pages_last_updated = current_time( 'timestamp' ) + ( HOUR_IN_SECONDS / 4 );
				update_option( 'ppp_social_settings', $ppp_social_settings );
			} else {
				$pages = $facebook_settings->available_pages;

			}

			return $pages;
		}

		/**
		 * Access Token
		 *
		 * Getting the access token from Facebook.
		 *
		 */
		public function ppp_fb_getaccesstoken() {

			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//check facebook class is exis or not
			if( !$facebook ) {
				return false;
			}

			return $this->facebook->getAccessToken();
		}

		/**
		 * Get auth url for facebook
		 *
		 */
		public function ppp_get_facebook_auth_url ( $return_url ) {

			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//check facebook class is exis or not
			if( !$facebook ) {
				return false;
			}

			if ( ! PPP_LOCAL_TOKENS ) {
				$base_url = 'https://postpromoterpro.com/?ppp-social-auth';
				$url  = $base_url . '&ppp-service=fb&ppp-license-key=' . trim( get_option( '_ppp_license_key' ) );
				$url .= '&nocache';
				$url .= '&return_url=' . esc_url( $return_url );
			} else {
				$url  = 'https://graph.facebook.com/oauth/authorize?';
				$url .= 'client_id=' . PPP_FB_APP_ID;
				$url .= '&scope=public_profile,publish_actions,manage_pages,publish_pages';
				$url .= '&state=ppp-local-keys-fb';
				$url .= '&redirect_uri=' . esc_url( $return_url ) . '&nocache';
			}

			return $url;
		}

		/**
		 * User Image
		 *
		 * Getting the the profile image of the connected Facebook user.
		 *
		 */
		public function ppp_fb_get_profile_picture( $args=array(), $user ) {

			if( isset( $args['type'] ) && !empty( $args['type'] ) ) {
				$type = $args['type'];
			} else {
				$type = 'large';
			}
			$url = 'https://graph.facebook.com/' . $user . '/picture?type=' . $type;
			return $url;
		}

		public function ppp_fb_share_link( $link, $message, $image ) {
			global $ppp_social_settings;
			$facebook_settings = $ppp_social_settings['facebook'];

			if ( !isset( $facebook_settings->page ) || strtolower( $facebook_settings->page ) === 'me' ) {
				$account      = 'me';
				$access_token = $facebook_settings->access_token;
			} else {
				$page_info    = explode( '|', $facebook_settings->page );
				$account      = $page_info[2];
				$access_token = $page_info[1];
			}

			$url = 'https://graph.facebook.com/' . $account . '/feed?access_token=' . $access_token;
			$args = array( 'link' => $link, 'message' => $message );
			if ( !empty( $image ) ) {
				$args['picture'] = $image;
			}
			$results = json_decode( wp_remote_retrieve_body( wp_remote_post( $url, array( 'body' => $args ) ) ) );

			return $results;
		}

		/**
		 * Get Data From URL
		 *
		 * Handles to get data from url
		 * via CURL
		 *
		 */

		public function ppp_get_data_from_url( $url ) {

			//Use wp_remote_post and wp_remote_get
			$data	= wp_remote_retrieve_body( wp_remote_get( $url ) );

			return $data;
		}
	}
}
