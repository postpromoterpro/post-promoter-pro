<?php
/*
Plugin Name: Post Promoter Pro
Plugin URI: https://postpromoterpro.com
Description: Maximize your social media presence on Twitter, Facebook, and LinkedIn.
Version: 2.3.24
Author: Post Promoter Pro
Author URI: https://postpromoterpro.com
License: GPLv2
*/

define( 'PPP_PATH', plugin_dir_path( __FILE__ ) );
define( 'PPP_VERSION', '2.3.24' );
define( 'PPP_FILE', plugin_basename( __FILE__ ) );
define( 'PPP_URL', plugins_url( '/', PPP_FILE ) );

define( 'PPP_STORE_URL', 'https://postpromoterpro.com' );
define( 'PPP_PLUGIN_NAME', 'Post Promoter Pro' );
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( PPP_PATH . '/includes/EDD_SL_Plugin_Updater.php' );
}

class PostPromoterPro {
	private static $ppp_instance;

	private function __construct() {
		add_action( 'init', array( $this, 'ppp_loaddomain' ), 1 );

		if ( ! is_callable( 'curl_init' ) ) {
			add_action( 'admin_notices', array( $this, 'no_curl' ) );
		} else {
			global $ppp_options, $ppp_social_settings, $ppp_share_settings;

			include PPP_PATH . '/includes/general-functions.php';
			include PPP_PATH . '/includes/share-functions.php';
			include PPP_PATH . '/includes/cron-functions.php';
			include PPP_PATH . '/includes/filters.php';
			include PPP_PATH . '/includes/libs/social-loader.php';

			if( ! class_exists( 'WP_Logging' ) ) {
				include PPP_PATH . '/includes/libs/class-wp-logging.php';
			}

			if ( is_admin() ) {
				include PPP_PATH . '/includes/admin/upgrades.php';
				include PPP_PATH . '/includes/admin/do-upgrades.php';
				include PPP_PATH . '/includes/admin/actions.php';
				include PPP_PATH . '/includes/admin/admin-pages.php';
				include PPP_PATH . '/includes/admin/admin-ajax.php';
				include PPP_PATH . '/includes/admin/meta-boxes.php';
				include PPP_PATH . '/includes/admin/welcome.php';
				include PPP_PATH . '/includes/admin/dashboard.php';
			}

			$ppp_options         = get_option( 'ppp_options' );
			$ppp_social_settings = get_option( 'ppp_social_settings' );
			$ppp_share_settings  = get_option( 'ppp_share_settings' );

			// Do some leg work on the social settings for Issue #257
			if ( is_array( $ppp_share_settings ) && ! array_key_exists( 'share_on_publish', $ppp_share_settings ) ) {
				$tw_share_on_publish = ! empty( $ppp_share_settings['twitter']['share_on_publish'] ) ? true : false;
				$fb_share_on_publish = ! empty( $ppp_share_settings['facebook']['share_on_publish'] ) ? true : false;
				$li_share_on_publish = ! empty( $ppp_share_settings['linkedin']['share_on_publish'] ) ? true : false;

				unset(
					$ppp_share_settings['twitter']['share_on_publish'],
					$ppp_share_settings['facebook']['share_on_publish'],
					$ppp_share_settings['linkedin']['share_on_publish']
				);

				$post_types = ppp_supported_post_types();
				foreach ( $post_types as $key => $post_type ) {
					$ppp_share_settings['share_on_publish'][ $key ]['twitter'] = $tw_share_on_publish;
					$ppp_share_settings['share_on_publish'][ $key ]['facebook'] = $fb_share_on_publish;
					$ppp_share_settings['share_on_publish'][ $key ]['linkedin'] = $li_share_on_publish;
				}

				update_option( 'ppp_share_settings', $ppp_share_settings );
			}

			$this->hooks();
		}

	}

	/**
	 * Get the singleton instance of our plugin
	 * @return class The Instance
	 * @access public
	 */
	public static function getInstance() {
		if ( !self::$ppp_instance ) {
			self::$ppp_instance = new PostPromoterPro();
		}

		return self::$ppp_instance;
	}

	/**
	 * Nag if cURL is disabled
	 * @return void
	 */
	public function no_curl() {
		?>
		<div class="no-curl">
			<p><?php _e( 'Post Promoter Pro requires cURL to be enabled. Please enable it to continue using the plugin.', 'ppp-txt' ); ?></p>
		</div>
		<?php
	}

	private function hooks() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'ppp_register_settings' ) );
			add_action( 'admin_init', 'ppp_upgrade_plugin', 1 );

			// Handle licenses
			add_action( 'admin_init', array( $this, 'plugin_updater' ) );
			add_action( 'admin_init', array( $this, 'activate_license' ) );
			add_action( 'admin_init', array( $this, 'deactivate_license' ) );

			add_action( 'admin_menu', array( $this, 'ppp_setup_admin_menu' ), 1000, 0 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_settings_links' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_scripts' ), 99 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_styles' ), 99999 );
			add_action( 'wp_trash_post', 'ppp_remove_scheduled_shares', 10, 1 );

			if ( ppp_is_dev_or_staging() ) {
				add_action( 'admin_notices', array( $this, 'local_site_nag' ) );
			}
		}

		add_action( 'init', array( $this, 'get_actions' ) );
		add_action( 'save_post', 'ppp_schedule_share', 99, 2);
		add_action( 'wp_insert_post', 'ppp_schedule_share', 99, 2);
		add_action( 'transition_post_status', 'ppp_share_on_publish', 99, 3);
		add_action( 'init', 'ppp_add_image_sizes' );
		add_filter( 'wp_log_types', array( $this, 'register_log_type' ), 10, 1 );
	}

	/**
	 * Queue up the JavaScript file for the admin page, only on our admin page
	 * @param  string $hook The current page in the admin
	 * @return void
	 * @access public
	 */
	public function load_custom_scripts( $hook ) {

		$allowed_pages = array(
			'toplevel_page_ppp-options',
			'post-promoter_page_ppp-social-settings',
			'post-new.php',
			'post.php',
			'post-promoter_page_ppp-schedule-info',
			'profile.php',
			'user-edit.php',
		);

		$allowed_pages = apply_filters( 'ppp_admin_scripts_pages', $allowed_pages, $hook );

		if ( ! in_array( $hook, $allowed_pages ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		$jquery_ui_timepicker_path = PPP_URL . 'includes/scripts/libs/jquery-ui-timepicker-addon.js';
		wp_enqueue_script( 'ppp_timepicker_js', $jquery_ui_timepicker_path , array( 'jquery', 'jquery-ui-core' ), PPP_VERSION, true );
		wp_enqueue_script( 'ppp_core_custom_js', PPP_URL.'includes/scripts/js/ppp_custom.js', 'jquery', PPP_VERSION, true );

	}

	public function load_styles() {

		global $wp_styles;

		// List of people who make it impossible to override their jQuery UI as it's in their core CSS...so only
		// load ours if they don't exist
		if ( ! wp_style_is( 'ot-admin-css' ) && ! wp_style_is( 'jquery-ui-css' ) ) {
			wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/flick/jquery-ui.css' );
		}

		wp_register_style( 'ppp_admin_css', PPP_URL . 'includes/scripts/css/admin-style.css', false, PPP_VERSION );
		wp_enqueue_style( 'ppp_admin_css' );

		$sources = array_map( 'basename', (array) wp_list_pluck( $wp_styles->registered, 'src' ) );
		if ( ! in_array( 'font-awesome.css', $sources ) || in_array( 'font-awesome.min.css', $sources )  ) {
			wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', false, null );
			wp_enqueue_style( 'font-awesome' );
		}

	}

	/**
	 * Adds the Settings and Post Promoter Pro Link to the Settings page list
	 * @param  array $links The current list of links
	 * @param  string $file The plugin file
	 * @return array        The new list of links, with our additional ones added
	 * @access public
	 */
	public function plugin_settings_links( $links, $file ) {
		if ( $file != PPP_FILE ) {
			return $links;
		}

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=ppp-options' ), __( 'Settings', 'ppp-txt' ) );

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Returns the capability (or role) required to manage the plugin.
	 *
	 * @return string A WordPress capability or role name.
	 */
	public static function get_manage_capability() {
		return apply_filters( 'ppp_manage_role', 'manage_options' );
	}

	/**
	 * Add the Pushover Notifications item to the Settings menu
	 * @return void
	 * @access public
	 */
	public function ppp_setup_admin_menu() {
		$capability = self::get_manage_capability();

		add_menu_page(
			__( 'Post Promoter', 'ppp-txt' ),
			__( 'Post Promoter', 'ppp-txt' ),
			$capability,
			'ppp-options',
			'ppp_admin_page'
		);

		add_submenu_page(
			'ppp-options',
			__( 'Social Settings', 'ppp-txt' ),
			__( 'Social Settings', 'ppp-txt' ),
			$capability,
			'ppp-social-settings',
			'ppp_display_social'
		);

		add_submenu_page(
			'ppp-options',
			__( 'Schedule', 'ppp-txt' ),
			__( 'Schedule', 'ppp-txt' ),
			$capability,
			'ppp-schedule-info',
			'ppp_display_schedule'
		);

		add_submenu_page(
			'ppp-options',
			__( 'System Info', 'ppp-txt' ),
			__( 'System Info', 'ppp-txt' ),
			$capability,
			'ppp-system-info',
			'ppp_display_sysinfo'
		);

		add_submenu_page(
			null,
			__( 'PPP Upgrades', 'ppp-txt' ),
			__( 'PPP Upgrades', 'ppp-txt' ),
			$capability,
			'ppp-upgrades',
			'ppp_upgrades_screen'
		);

	}

	/**
	 * Register/Whitelist our settings on the settings page, allow extensions and other plugins to hook into this
	 * @return void
	 * @access public
	 */
	public function ppp_register_settings() {
		register_setting( 'ppp-options', 'ppp_options' );
		register_setting( 'ppp-options', '_ppp_license_key', array( $this, 'ppp_sanitize_license' ) );

		register_setting( 'ppp-social-settings', 'ppp_social_settings' );
		register_setting( 'ppp-share-settings', 'ppp_share_settings' );
		do_action( 'ppp_register_additional_settings' );
	}

	/**
	 * Load the Text Domain for i18n
	 * @return void
	 * @access public
	 */
	public function ppp_loaddomain() {
		load_plugin_textdomain( 'ppp-txt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Sets up the EDD SL Plugin updated class
	 * @return void
	 */
	public function plugin_updater() {
		global $ppp_options;
		if ( defined( 'NO_AUTO_UPDATE' ) && true === NO_AUTO_UPDATE ) {
			return;
		}

		$license_key = trim( get_option( '_ppp_license_key' ) );

		if ( empty( $license_key ) ) {
			add_action( 'admin_notices', array( $this, 'no_license_nag' ) );
			return;
		}

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( PPP_STORE_URL, __FILE__, array(
				'version'   => PPP_VERSION,         // current version number
				'license'   => $license_key,        // license key (used get_option above to retrieve from DB)
				'item_name' => PPP_PLUGIN_NAME,     // name of this plugin
				'author'    => 'Post Promoter Pro',  // author of this plugin
				'beta'      => ! empty( $ppp_options['enable_betas'] ) ? true : false, // If we should install beta versions
			)
		);
	}

	/**
	 * If no license key is saved, show a notice
	 * @return void
	 */
	public function no_license_nag() {
		?>
		<div class="updated">
			<p>
				<?php printf(
					 __( 'Post Promoter Pro requires your license key to work, please <a href="%s">enter it now</a>.', 'ppp-txt' ),
						  admin_url( 'admin.php?page=ppp-options' )
					 );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * If site is detected as local, show notice
	 * @return void
	 */
	public function local_site_nag() {
		$dismissed = get_option( 'ppp_local_url_notice_dismissed' );
		if ( ! empty( $dismissed ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery('#ppp-local-url-notice').on('click', '.notice-dismiss', function (event) {
					event.preventDefault();
					jQuery.ajax({
						type   : 'post',
						url    : ajaxurl,
						data   : {
							action: 'ppp_local_url_notice_dismiss',
							nonce : jQuery(this).parent().data('nonce'),
						},
						success: function (response) {
						}
					});
				});
			} );
		</script>
		<div id="ppp-local-url-notice" data-nonce="<?php echo wp_create_nonce( 'ppp_local_url_notice_nonce' ); ?>" class="notice notice-info is-dismissible">
			<p>
				<?php
					_e( 'Post Promoter Pro has detected a development or staging site. To prevent unintended social media posts, sharing has been disabled.', 'ppp-txt' );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Deactivates the license key
	 * @return void
	 */
	public function deactivate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['ppp_license_deactivate'] ) ) {

			// run a quick security check
			if( ! check_admin_referer( 'ppp_deactivate_nonce', 'ppp_deactivate_nonce' ) ) {
				return;
			}
			// get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( '_ppp_license_key' ) );


			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'deactivate_license',
				'license' 	=> $license,
				'item_name' => urlencode( PPP_PLUGIN_NAME ) // the name of our product in EDD
			);

			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, PPP_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' ) {
				delete_option( '_ppp_license_key_status' );
			}

		}
	}

	/**
	 * Activates the license key provided
	 * @return void
	 */
	public function activate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['ppp_license_activate'] ) ) {

			// run a quick security check
			if( ! check_admin_referer( 'ppp_activate_nonce', 'ppp_activate_nonce' ) ) {
				return;
			}
			// get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( '_ppp_license_key' ) );


			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( PPP_PLUGIN_NAME ),
			);

			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, PPP_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "active" or "inactive"

			update_option( '_ppp_license_key_status', $license_data->license );

		}
	}

	/**
	 * Sanatize the liscense key being provided
	 * @param  string $new The License key provided
	 * @return string      Sanitized license key
	 */
	public function ppp_sanitize_license( $new ) {
		$old = get_option( '_ppp_license_key' );
		if( $old && $old != $new ) {
			delete_option( '_ppp_license_key_status' ); // new license has been entered, so must reactivate
		}
		return $new;
	}

	/**
	 * Hook to listen for our actions
	 *
	 * @return void
	 */
	public function get_actions() {
		if ( isset( $_GET['ppp_action'] ) ) {
			do_action( 'ppp_' . $_GET['ppp_action'], $_GET );
		}
	}

	/**
	 * Register our log type for when items are shared
	 *
	 * @since  2.3
	 * @param  array $log_types Array of log types
	 * @return array
	 */
	public function register_log_type( $log_types ) {
		$types[] = 'ppp_share';
		return $types;
	}

}

/**
 * Load and access the one true instance of Post Promoter Pro
 *
 * @return object The Post_Promoter_Pro instance
 */
function post_promoter_pro() {
	return PostPromoterPro::getInstance();
}
add_action( 'plugins_loaded', 'post_promoter_pro' );

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

	$default_share_settings['share_on_publish'] = array(
		'post' => array(
			'twitter'  => 1,
			'facebook' => 1,
			'linkedin' => 1,
		),
	);
	update_option( 'ppp_share_settings', $default_share_settings );

	update_option( 'ppp_completed_upgrades', array( 'upgrade_post_meta', 'fix_scheduled_shares_2319' ) );
}
register_activation_hook( PPP_FILE, 'post_promoter_pro_activation_setup' );