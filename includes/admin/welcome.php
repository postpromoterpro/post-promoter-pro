<?php
/**
 * Weclome Page Class
 *
 * @package     PPP
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * Adapted for Post Promoter Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PPP_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since 1.2
 */
class PPP_Welcome {

	/**
	 * Get things started
	 *
	 * @since 1.2
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__( 'Welcome to Post Promoter Pro', 'ppp-txt' ),
			__( 'Welcome to Post Promoter Pro', 'ppp-txt' ),
			PostPromoterPro::get_manage_capability(),
			'ppp-about',
			array( $this, 'about_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with Post Promoter Pro', 'ppp-txt' ),
			__( 'Getting started with Post Promoter Pro', 'ppp-txt' ),
			PostPromoterPro::get_manage_capability(),
			'ppp-getting-started',
			array( $this, 'getting_started_screen' )
		);

	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'ppp-about' );
		remove_submenu_page( 'index.php', 'ppp-getting-started' );

		// Badge for welcome page
		$badge_url = PPP_URL . 'includes/images/ppp-badge.png';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.ppp-badge {
			padding-top: 150px;
			height: 50px;
			width: 300px;
			color: #666;
			font-weight: bold;
			font-size: 14px;
			text-align: center;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
			margin: 0 -5px;
			background: url('<?php echo $badge_url; ?>') no-repeat;
		}

		.about-wrap .ppp-badge {
			position: absolute;
			top: 0;
			right: 0;
		}

		.ppp-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
		}
		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function tabs() {
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'ppp-about';
		?>
		<h1 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'ppp-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'ppp-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'ppp-txt' ); ?>
			</a>
		</h1>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', PPP_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Post Promoter Pro %s', 'ppp-txt' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'The most effective way to promote your WordPress content.', 'ppp-txt' ), $display_version ); ?></div>
			<div class="ppp-badge"><?php printf( __( 'Version %s', 'ppp-txt' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<div class="changelog">
				<h3><?php _e( 'Free Form Tweet Scheduling', 'ppp-txt' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PPP_URL . '/includes/images/screenshots/free-form-tweets.jpg'; ?>" class="ppp-welcome-screenshots"/>

					<h4><?php _e( 'More effective, and user friendly.', 'ppp-txt' );?></h4>
					<p><?php _e( 'No longer do you have to figure out what "Day 1" means. With free form scheduling, you can now create Tweets, days, weeks, or months in the future. Even multiples a day.', 'ppp-txt' );?></p>
					<p><?php _e( 'Have an old post that you want to promote again? No problem, just go back and add another Scheduled Tweet to the list!', 'ppp-txt' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Twitter Cards Support', 'ppp-txt' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PPP_URL . '/includes/images/screenshots/twitter-cards.jpg'; ?>" class="ppp-welcome-screenshots"/>

					<h4><?php _e( 'Increased visibility on Twitter','ppp-txt' );?></h4>
					<p><?php _e( 'Enable this feature if you want your Tweets to look the best they can.', 'ppp-txt' );?></p>
					<p><?php _e( 'This is an opt-in setting, so if you have a plugin that already does this, no conflicts will arise upon upgrade.', 'ppp-txt' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Variable Twitter Images', 'ppp-txt' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Change the image attached to each Tweet','ppp-txt' );?></h4>
					<p><?php _e( 'Have a few rotating images you want to use for your scheduled Tweets? No problem, Version 2.2 supports changing this for every scheduled Tweet.', 'ppp-txt' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Additional Updates', 'ppp-txt' );?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'Updated image sizes', 'ppp-txt' );?></h4>
						<p><?php _e( 'Changed the optimal thumbnail image sizes to meet the 2015 network standards.', 'ppp-txt' );?></p>

						<h4><?php _e( 'CSS Updates', 'ppp-txt' );?></h4>
						<p><?php _e( 'Fixed a conflict in the media library CSS.', 'ppp-txt' );?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Improved Facebook Token Refresh', 'ppp-txt' );?></h4>
						<p><?php _e( 'Fixed a bug with the Facebook token refresh resetting the "Post As", dropdown.', 'ppp-txt' );?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ppp-options' ) ); ?>"><?php _e( 'Start Using Post Promoter Pro!', 'ppp-txt' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function getting_started_screen() {
		list( $display_version ) = explode( '-', PPP_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Post Promoter Pro %s', 'ppp-txt' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'The most effective way to promote your WordPress content.', 'ppp-txt' ), $display_version ); ?></div>
			<div class="ppp-badge"><?php printf( __( 'Version %s', 'ppp-txt' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'Post Promoter Pro makes sharing your content as easy as possible.', 'ppp-txt' ); ?></p>

		</div>
		<?php
	}

	/**
	 * Sends user to the Welcome page on first activation of PPP as well as each
	 * time PPP is upgraded to a new version
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function welcome() {

		$version_level = explode( '.', PPP_VERSION );
		if ( count( $version_level > 2 ) ) {
			return;
		}

		// Bail if no activation redirect
		if ( ! get_transient( '_ppp_activation_redirect' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_ppp_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'index.php?page=ppp-about' ) ); exit;
	}
}
new PPP_Welcome();
