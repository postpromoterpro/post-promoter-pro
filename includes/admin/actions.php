<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates a listener for disconnecting a social media account
 *
 * Developers simply need to include the ?ppp_social_disconnect=true&ppp_network=[network_shortname]
 * in their link to disconnect and then hook ppp_disconnect-[network_shortname]
 *
 * @return void
 */
function ppp_disconnect_social() {

	if ( isset( $_GET['ppp_social_disconnect'] ) && isset( $_GET['ppp_network'] ) ) {

		if ( ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
			wp_die( __( 'You do not have permission to view this page', 'ppp-txt' ) );
		}

		$network = $_GET['ppp_network'];
		do_action( 'ppp_disconnect-' . $network );
	}
}
add_action( 'admin_init', 'ppp_disconnect_social', 10 );

/**
 * Sets up and uses the social media metabox tabs
 * @return void
 */
function ppp_generate_metabox_tabs() {
	global $visibleKey;

	$tabs = apply_filters( 'ppp_metabox_tabs', array() );
	$i = 0;
	foreach ( $tabs as $key => $values ) {
		if ( $i === 0 ) {
			$visibleKey = $key;
			$class = 'tabs';
		} else {
			$class = '';
		}

		?><li class="<?php echo $class; ?>"><a href="#<?php echo $key; ?>"><?php
		if ( $values['class'] !== false ) {
			?>
			<span class="dashicons <?php echo $values['class']; ?>"></span>&nbsp;
			<?php
		}
		echo $values['name']; ?></a></li><?php
		$i++;
	}
}
add_action( 'ppp_metabox_tabs_display', 'ppp_generate_metabox_tabs', 10 );

/**
 * Sets up and uses the social media account tabs
 * @return void
 */
function ppp_generate_social_account_tabs() {
	global $visibleSettingTab;

	$tabs = apply_filters( 'ppp_admin_tabs', array() );
	$i = 0;
	?><h2 id="ppp-social-connect-tabs" class="nav-tab-wrapper"><?php
	foreach ( $tabs as $key => $values ) {
		if ( $i === 0 ) {
			$visibleSettingTab = $key;
			$class = ' nav-tab-active';
		} else {
			$class = '';
		}
		?><a class="nav-tab<?php echo $class; ?>" href='#<?php echo $key; ?>'><?php
		if ( $values['class'] !== false ) {
			?>
			<span class="dashicons <?php echo $values['class']; ?>"></span>&nbsp;
			<?php
		}
		echo $values['name']; ?></a></li><?php
		?></a><?php
		$i++;
	}
	?></h2><?php
}
add_action( 'ppp_social_media_tabs_display', 'ppp_generate_social_account_tabs', 10 );

/**
 * Sets up and displays the social media metaboxes
 * @param  [type] $post [description]
 * @return [type]       [description]
 */
function ppp_generate_metabox_content( $post ) {
	global $visibleKey;
	$tab_content = apply_filters( 'ppp_metabox_content', array() );
	if ( empty( $tab_content ) ) {
		printf( __( 'No social media accounts active. <a href="%s">Connect with your accounts now</a>.', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-social-settings' ) );
	} else {
		foreach ( $tab_content as $service ) {
			$hidden = ( $visibleKey == $service ) ? '' : ' hidden';
			?>
			<div class="wp-tab-panel tabs-panel<?php echo $hidden; ?>" id="<?php echo $service; ?>">
				<?php do_action( 'ppp_generate_metabox_content-' . $service, $post ); ?>
			</div>
			<?php
		}
	}
}
add_action( 'ppp_metabox_content_display', 'ppp_generate_metabox_content', 10, 1 );

/**
 * Sets up and displays the social media account settings
 * @return [type] [description]
 */
function ppp_generate_social_account_content() {
	global $visibleSettingTab;
	$tab_content = apply_filters( 'ppp_admin_social_content', array() );
	if ( empty( $tab_content ) ) {
		printf( __( 'No social media accounts active. <a href="%s">Connect with your accounts now</a>.', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-social-settings' ) );
	} else {
		foreach ( $tab_content as $service ) {
			$hidden = ( $visibleSettingTab == $service ) ? '' : ' hidden';
			?>
			<div class="ppp-social-connect<?php echo $hidden; ?>" id="<?php echo $service; ?>">
				<?php do_action( 'ppp_connect_display-' . $service ); ?>
			</div>
			<?php
		}
	}
}
add_action( 'ppp_social_media_content_display', 'ppp_generate_social_account_content', 10, 1 );

/**
 * Listens for the share/delete actions on the schedule view
 * @return void
 */
function ppp_list_view_maybe_take_action() {
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ppp-schedule-info' ) {
		return;
	}

	if ( ! isset( $_GET['action'] ) ) {
		return;
	}

	// Get the necessary info for the actions
	$post_id = isset( $_GET['post_id'] ) ? $_GET['post_id'] : 0;
	$name    = isset( $_GET['name'] ) ? $_GET['name'] : '';
	$index   = isset( $_GET['index'] ) ? $_GET['index'] : 0;
	$delete  = isset( $_GET['delete_too'] ) ? true : false;

	switch( $_GET['action'] ) {
		case 'delete_item':
			if ( ! empty( $post_id ) && ! empty( $name ) || empty( $index ) ) {
				ppp_remove_scheduled_share( array( (int)$post_id, $name ) ); // Remove the item in cron

				// Remove the item from postmeta if it exists.
				$current_post_meta = get_post_meta( $post_id, '_ppp_tweets', true );

				if ( isset( $current_post_meta[$index] ) ) {
					unset( $current_post_meta[$index] );
					update_post_meta( $post_id, '_ppp_tweets', $current_post_meta );
				}

				// Display the notice
				add_action( 'admin_notices', 'ppp_item_deleted_notice' );
			}
			break;
		case 'share_now':
			if ( ! empty( $post_id ) && ! empty( $name ) ) {
				ppp_share_post( $post_id, $name );

				if ( $delete && ! empty( $index ) ) {
					ppp_remove_scheduled_share( array( (int)$post_id, $name ) ); // Remove the item in cron

					// Remove the item from postmeta if it exists.
					$current_post_meta = get_post_meta( $post_id, '_ppp_tweets', true );

					if ( isset( $current_post_meta[$index] ) ) {
						unset( $current_post_meta[$index] );
						update_post_meta( $post_id, '_ppp_tweets', $current_post_meta );
					}

					// Display the notice
					add_action( 'admin_notices', 'ppp_item_shared_and_deleted_notice' );
				} else {
					add_action( 'admin_notices', 'ppp_item_posted_notice' );
				}
			}
			break;
		default:
			break;
	}
}
add_action( 'admin_head', 'ppp_list_view_maybe_take_action', 10 );

	// These are used by the ppp_list_view_maybe_take_action function

	/**
	 * When an entry is deleted from schedule view, register a notice
	 * @return void
	 */
	function ppp_item_deleted_notice() {
		?>
		<div class="updated">
			<p><?php _e( 'Scheduled item has been deleted.', 'ppp-txt' ); ?></p>
		</div>
		<?php
	}

	/**
	 * When an entry is shared from schedule view, register a notice
	 * @return void
	 */
	function ppp_item_posted_notice() {
		?>
		<div class="updated">
			<p><?php _e( 'Item has been shared.', 'ppp-txt' ); ?></p>
		</div>
		<?php
	}

	/**
	 * When an entry is shared and deleted from schedule view, register a notice
	 * @return void
	 */
	function ppp_item_shared_and_deleted_notice() {
		?>
		<div class="updated">
			<p><?php _e( 'Item has been shared and deleted.', 'ppp-txt' ); ?></p>
		</div>
		<?php
	}
