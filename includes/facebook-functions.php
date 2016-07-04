<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return if Facebook account is found
 * @return bool If the Twitter object exists
 */
function ppp_facebook_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['facebook'] ) && !empty( $ppp_social_settings['facebook'] ) ) {
		return true;
	}

	return false;
}

/**
 * Register Facebook as a service
 * @param  array $services The Currently registered services
 * @return array           The services with Facebook added
 */
function ppp_fb_register_service( $services = array() ) {
	$services[] = 'fb';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_fb_register_service', 10, 1 );

/**
 * Registers the facebook icon
 * @param  string $string The item passed into the list icons
 * @return string         The Facebook Icon
 */
function ppp_fb_account_list_icon( $string = '' ) {
	$string .= '<span class="dashicons icon-ppp-fb"></span>';

	return $string;
}
add_filter( 'ppp_account_list_icon-fb', 'ppp_fb_account_list_icon', 10, 1 );

/**
 * Show the Facebook Avatar in the account list
 * @param  string $string The list default
 * @return string         The Facebook avatar
 */
function ppp_fb_account_list_avatar( $string = '' ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings;
		$avatar_url = $ppp_social_settings['facebook']->avatar;
		$string = '<img class="ppp-social-icon" src="' . $avatar_url . '" />';
	}

	return $string;
}
add_filter( 'ppp_account_list_avatar-fb', 'ppp_fb_account_list_avatar', 10, 1 );

/**
 * Adds Facebook name to the list-class
 * @param  string $string The default name
 * @return string         The name of the auth'd Facebook Profile
 */
function ppp_fb_account_list_name( $string = '' ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings;
		$string  = $ppp_social_settings['facebook']->name;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-fb', 'ppp_fb_account_list_name', 10, 1 );

/**
 * The Facebook actions for the list view
 * @param  string $string The default list view actions
 * @return string         The HTML for the actions
 */
function ppp_fb_account_list_actions( $string = '' ) {

	if ( ! ppp_facebook_enabled() ) {
		global $ppp_facebook_oauth, $ppp_social_settings;
		$fb_authurl = $ppp_facebook_oauth->ppp_get_facebook_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );

		$string .= '<a class="button-primary" href="' . $fb_authurl . '">' . __( 'Connect to Facebook', 'ppp-txt' ) . '</a>';
	} else {
		$string  .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=facebook' ) . '" >' . __( 'Disconnect from Facebook', 'ppp-txt' ) . '</a>&nbsp;';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-fb', 'ppp_fb_account_list_actions', 10, 1 );

/**
 * The Facebook Extras section for the list-class
 * @param  string $string The default extras colun
 * @return string         The HTML for the Pages dropdown and debug info
 */
function ppp_fb_account_list_extras( $string ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings, $ppp_facebook_oauth, $ppp_options;
		$pages = $ppp_facebook_oauth->ppp_get_fb_user_pages( $ppp_social_settings['facebook']->access_token );
		$selected = isset( $ppp_social_settings['facebook']->page ) ? stripslashes( $ppp_social_settings['facebook']->page ) : 'me';

		if ( !empty( $pages ) ) {
			$string = '<label>' . __( 'Publish as:', 'ppp-txt' ) . '</label><br />';
			$string .= '<select id="fb-page">';
			$string .= '<option value="me">' . __( 'Me', 'ppp-txt' ) . '</option>';
			foreach ( $pages as $page ) {
				$value = $page->name . '|' . $page->access_token . '|' . $page->id;
				$string .= '<option ' . selected( $value, $selected, false ) . ' value="' . $value . '">' . $page->name . '</option>';
			}
			$string .= '</select><span class="spinner"></span>';
		}

		if ( ! empty( $ppp_options['enable_debug'] ) ) {
			$days_left  = absint( round( ( $ppp_social_settings['facebook']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
			$refresh_in = absint( round( ( get_option( '_ppp_facebook_refresh' ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );

			$string .= '<br />' . sprintf( __( 'Token expires in %s days' , 'ppp-txt' ), $days_left );
			$string .= '<br />' . sprintf( __( 'Refresh notice in %s days', 'ppp-txt' ), $refresh_in );
		}
	}

	return $string;
}
add_filter( 'ppp_account_list_extras-fb', 'ppp_fb_account_list_extras', 10, 1 );

/**
 * Sets the constants for the oAuth tokens for Twitter
 * @param  array $social_tokens The tokens stored in the transient
 * @return void
 */
function ppp_set_fb_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'facebook' ) ) {
		define( 'PPP_FB_APP_ID', $social_tokens->facebook->app_id );
		define( 'PPP_FB_APP_SECRET', $social_tokens->facebook->app_secret );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_fb_token_constants', 10, 1 );

/**
 * Capture the oauth return from facebook
 * @return void
 */
function ppp_capture_facebook_oauth() {
	$should_capture = false;

	if ( isset( $_GET['state'] ) && strpos( $_GET['state'], 'ppp-local-keys-fb' ) !== false ) {
		// Local config
		$should_capture = true;
	}

	if ( isset( $_REQUEST['fb_access_token'] ) ) {
		// Returning from remote config
		$should_capture = true;
	}

	if ( $should_capture && ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ppp-social-settings' ) ) {
		global $ppp_facebook_oauth;
		$ppp_facebook_oauth->ppp_initialize_facebook();
		wp_redirect( admin_url( 'admin.php?page=ppp-social-settings' ) );
		die();
	}

}
add_action( 'admin_init', 'ppp_capture_facebook_oauth', 10 );

/**
 * Capture the disconnect request from Facebook
 * @return void
 */
function ppp_disconnect_facebook() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['facebook'] ) ) {
		unset( $ppp_social_settings['facebook'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
		delete_option( '_ppp_facebook_refresh' );
	}
}
add_action( 'ppp_disconnect-facebook', 'ppp_disconnect_facebook', 10 );

/**
 * Add query vars for Facebook
 * @param  array $vars Currenty Query Vars
 * @return array       Query vars array with facebook added
 */
function ppp_fb_query_vars( $vars ) {
	$vars[] = 'fb_access_token';
	$vars[] = 'expires_in';

	return $vars;
}
add_filter( 'query_vars', 'ppp_fb_query_vars' );

/**
 * Refreshes the Facebook Access Token
 * @return void
 */
function ppp_fb_execute_refresh() {

	if ( ! ppp_facebook_enabled() ) {
		return;
	}

	$refresh_date = (int) get_option( '_ppp_facebook_refresh', true );

	if ( current_time( 'timestamp' ) > $refresh_date ) {
		add_action( 'admin_notices', 'ppp_facebook_refresh_notice' );
	}
}
add_action( 'admin_init', 'ppp_fb_execute_refresh', 99 );

/**
 * Displays notice when the Facebook Token is nearing expiration
 * @return void
 */
function ppp_facebook_refresh_notice() {

	if ( ! ppp_facebook_enabled() ) {
		return;
	}

	$has_dismissed = get_transient( 'ppp-dismiss-refresh-fb' . get_current_user_id() );
	if ( false !== $has_dismissed ) {
		return;
	}

	global $ppp_facebook_oauth, $ppp_social_settings;

	// Look for the tokens coming back
	$ppp_facebook_oauth->ppp_initialize_facebook();

	$token = $ppp_social_settings['facebook']->access_token;
	$url = $ppp_facebook_oauth->ppp_get_facebook_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );
	$url = str_replace( '?ppp-social-auth', '?ppp-social-auth&ppp-refresh=true&access_token=' . $token, $url );

	$days_left = (int) round( ( $ppp_social_settings['facebook']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
	?>
	<div class="notice notice-warning is-dismissible" data-service="fb">
		<?php if ( $days_left > 0 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your Facebook authentication expires in within %d days. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $days_left, $url ); ?></p>
		<?php elseif ( $days_left < 1 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your Facebook authentication has expired. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $url ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Allow dismissing of the admin notices on a user level
 *
 * @since  2.3
 * @return void
 */
function ppp_fb_dismiss_notice() {

	$nag = sanitize_key( $_POST[ 'nag' ] );

	if ( $nag === $_POST[ 'nag' ] ) {
		set_transient( $nag . get_current_user_id(), true, DAY_IN_SECONDS );
	}


}
add_action( 'wp_ajax_ppp_dismiss_notice-fb', 'ppp_fb_dismiss_notice' );

/**
 * Share a post to Facebook
 * @param  string $link        The link to Share
 * @param  string $message     The message attached to the link
 * @return array               The results array from the API
 */
function ppp_fb_share( $link, $message, $picture ) {
	global $ppp_facebook_oauth;

	return $ppp_facebook_oauth->ppp_fb_share_link( $link, ppp_entities_and_slashes( $message ), $picture );
}

/**
 * Send out a scheduled share to Facebook
 *
 * @since  2.3
 * @param  integer $post_id The Post ID to share fore
 * @param  integer $index   The index in the shares
 * @param  string  $name    The name of the Cron
 * @return void
 */
function ppp_fb_scheduled_share(  $post_id = 0, $index = 1, $name = ''  ) {
	global $ppp_options;

	$link = ppp_generate_link( $post_id, $name );

	$post_meta     = get_post_meta( $post_id, '_ppp_fb_shares', true );
	$this_share    = $post_meta[ $index ];
	$attachment_id = isset( $this_share['attachment_id'] ) ? $this_share['attachment_id'] : false;

	$share_message = ppp_fb_build_share_message( $post_id, $name );

	if ( empty( $attachment_id ) && ! empty( $this_share['image'] ) ) {
		$media = $this_share['image'];
	} else {
		$use_media = ppp_fb_use_media( $post_id, $index );
		$media     = ppp_post_has_media( $post_id, 'fb', $use_media, $attachment_id );
	}

	$status = ppp_fb_share( $link, $share_message, $media );

	$log_title = ppp_fb_build_share_message( $post_id, $name );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  =>  '',
		'post_parent'   => $post_id,
		'log_type'      => 'ppp_share'
	);

	$log_meta = array(
		'network'   => 'fb',
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	update_post_meta( $log_entry, '_ppp_share_status', $status );
}
add_action( 'ppp_share_scheduled_fb', 'ppp_fb_scheduled_share', 10, 3 );

/**
 * Returns the stored Facebook data for a post
 *
 * @since  2.3
 * @param  array $post_meta Array of meta data (empty)
 * @param  int   $post_id   The Post ID to get the meta for
 * @return array            The stored Facebook shares for a post
 */
function ppp_fb_get_post_meta( $post_meta, $post_id ) {
	return get_post_meta( $post_id, '_ppp_fb_shares', true );
}
add_filter( 'ppp_get_scheduled_items_fb', 'ppp_fb_get_post_meta', 10, 2 );

/**
 * Registers the thumbnail size for Facebook
 * @return void
 */
function ppp_fb_register_thumbnail_size() {
	add_image_size( 'ppp-fb-share-image', 1200, 628, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_fb_register_thumbnail_size' );

/**
 * Add Facebook to the Meta Box Tabs
 * @param  array $tabs Existing Metabox Tabs
 * @return array       Metabox tabs with Facebook
 */
function ppp_fb_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( ! ppp_facebook_enabled() ) {
		return $tabs;
	}

	$tabs['fb'] = array( 'name' => __( 'Facebook', 'ppp-txt' ), 'class' => 'icon-ppp-fb' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_fb_add_meta_tab', 10, 1 );

/**
 * Add Facebook to the Metabox Content
 * @param  array $content The existing metabox content
 * @return array          With Facebook
 */
function ppp_fb_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( ! ppp_facebook_enabled() ) {
		return $content;
	}

	$content[] = 'fb';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_fb_register_metabox_content', 10, 1 );

/**
 * Render the Metabox content for Facebook
 * @param  object $post The Post object being edited
 */
function ppp_fb_add_metabox_content( $post ) {
	global $ppp_options, $ppp_share_settings;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_fb_share_on_publish               = get_post_meta( $post->ID, '_ppp_fb_share_on_publish', true );
	$ppp_share_on_publish_title            = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_title', true );
	$ppp_fb_share_on_publish_attachment_id = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_attachment_id', true );
	$ppp_fb_share_on_publish_image_url     = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_image_url', true );

	$show_share_on_publish = false;

	$share_by_default      = empty( $ppp_share_settings['facebook']['share_on_publish'] ) ? false : true;

	if ( $ppp_fb_share_on_publish == '1' || ( $ppp_fb_share_on_publish == '' && $share_by_default ) ) {
		$show_share_on_publish = true;
	}

	?>
	<p>
		<div class="ppp-post-override-wrap">
			<p><h3><?php _e( 'Share on Facebook', 'ppp-txt' ); ?></h3></p>
			<p>
				<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
				<label for="ppp_fb_share_on_publish"><?php _e( 'Share this post on Facebook&hellip;', 'ppp-txt' ); ?></label>
				<select name="_ppp_fb_share_on_publish" id="ppp_fb_share_on_publish" class="ppp-toggle-share-on-publish">
					<option value="-1" <?php selected( true, $show_share_on_publish, true ); ?><?php if ( $disabled ): ?>disabled<?php endif; ?>><?php _e( 'Do not share this post', 'ppp-txt' ); ?></option>
					<option value="1" <?php selected( true, $show_share_on_publish, true ); ?><?php if ( $disabled ): ?>disabled<?php endif; ?>><?php _e( 'When this post is published', 'ppp-txt' ); ?></option>
					<option value="0" <?php selected( false, $show_share_on_publish, true ); ?>><?php _e( 'After this post is published', 'ppp-txt' ); ?></option>
				</select>
			</p>
			<div id="ppp-fb-fields" class="ppp-fields">
				<div id="ppp-fb-fields" class="ppp-meta-table-wrap">
					<table class="widefat ppp-repeatable-table" width="100%" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th style="width: 100px"><?php _e( 'Date', 'ppp-txt' ); ?></th>
								<th style="width: 75px;"><?php _e( 'Time', 'ppp-txt' ); ?></th>
								<th><?php _e( 'Link Message', 'ppp-txt' ); ?></th>
								<th style"width: 200px;"><?php _e( 'Image', 'ppp-txt' ); ?></th>
								<th style="width: 10px;"></th>
							</tr>
						</thead>
						<tbody id="fb-share-on-publish" class="ppp-share-on-publish" <?php if ( false === $show_share_on_publish ) : echo 'style="display: none;"'; endif; ?>>
							<?php
								$args = array(
									'text'          => $ppp_share_on_publish_title,
									'attachment_id' => $ppp_fb_share_on_publish_attachment_id,
									'image'         => $ppp_fb_share_on_publish_image_url,
								);

								ppp_render_fb_share_on_publish_row( $args );
							?>
						</tbody>
						<tbody id="fb-schedule-share" class="ppp-schedule-share" <?php if ( true === $show_share_on_publish ) : echo 'style="display: none;"'; endif; ?>>
							<?php $shares = get_post_meta( $post->ID, '_ppp_fb_shares', true ); ?>
							<?php if ( ! empty( $shares ) ) : ?>

								<?php foreach ( $shares as $key => $value ) :
									$date          = isset( $value['date'] )          ? $value['date']          : '';
									$time          = isset( $value['time'] )          ? $value['time']          : '';
									$text          = isset( $value['text'] )          ? $value['text']          : '';
									$image         = isset( $value['image'] )         ? $value['image']         : '';
									$attachment_id = isset( $value['attachment_id'] ) ? $value['attachment_id'] : '';

									$args = apply_filters( 'ppp_fb_row_args', compact( 'date','time','text','image','attachment_id' ), $value );
									?>

									<?php ppp_render_fb_share_row( $key, $args, $post->ID ); ?>


								<?php endforeach; ?>

							<?php else: ?>

								<?php ppp_render_fb_share_row( 1, array( 'date' => '', 'time' => '', 'text' => '', 'image' => '', 'attachment_id' => '' ), $post->ID, 1 ); ?>

							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div><!--end #edd_variable_price_fields-->

			<p><?php _e( 'Do not include links in your text, this will be added automatically.', 'ppp-txt' ); ?></p>
		</div>
		<?php _e( 'Note: If no image is chosen, and the post has a featured image, the Featured image will be attached to this share', 'ppp-txt' ); ?>
	</p>
	<?php
}
add_action( 'ppp_generate_metabox_content-fb', 'ppp_fb_add_metabox_content', 10, 1 );

/**
 * Render the Facebook share on publish row
 *
 * @since  2.3
 * @param  array  $args Contains share on publish data, if there is any
 * @return void
 */
function ppp_render_fb_share_on_publish_row( $args = array() ) {
	global $post;
	$readonly = $post->post_status !== 'publish' ? '' : 'readonly="readonly" ';
	?>
	<tr class="ppp-fb-wrapper ppp-repeatable-row on-publish-row">
		<td colspan="2" class="ppp-on-plublish-date-column">
			<?php _e( 'Share On Publish', 'ppp-txt' ); ?>
		</td>

		<td>
			<input <?php echo $readonly; ?>class="ppp-tweet-text-repeatable" type="text" name="_ppp_fb_share_on_publish_title" value="<?php echo esc_attr( $args['text'] ); ?>" />
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_fb_share_on_publish_attachment_id" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_fb_share_on_publish_image_url" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $args['image'] ); ?>" />

				<span class="ppp-upload-file">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>

		<td>&nbsp;</td>

	</tr>
<?php
}

/**
 * Render the scheduled share row for Facebook
 *
 * @since  2.3
 * @param  int $key        The key in the array
 * @param  array  $args    Arguements for the current post's share data
 * @param  int    $post_id The post ID being edited
 * @return void
 */
function ppp_render_fb_share_row( $key, $args = array(), $post_id ) {
	global $post;

	$share_time     = strtotime( $args['date'] . ' ' . $args['time'] );
	$readonly       = current_time( 'timestamp' ) > $share_time ? 'readonly="readonly" ' : '';
	$no_date        = ! empty( $readonly ) ? ' hasDatepicker' : '';
	$hide           = ! empty( $readonly ) ? 'display: none;' : '';
	?>
	<tr class="ppp-fb-wrapper ppp-repeatable-row ppp-repeatable-facebook scheduled-row" data-key="<?php echo esc_attr( $key ); ?>">
		<td>
			<input <?php echo $readonly; ?>type="text" class="share-date-selector<?php echo $no_date; ?>" name="_ppp_fb_shares[<?php echo $key; ?>][date]" placeholder="mm/dd/yyyy" value="<?php echo $args['date']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>type="text" class="share-time-selector" name="_ppp_fb_shares[<?php echo $key; ?>][time]" value="<?php echo $args['time']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>class="ppp-tweet-text-repeatable" type="text" name="_ppp_fb_shares[<?php echo $key; ?>][text]" value="<?php echo esc_attr( $args['text'] ); ?>" />
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_fb_shares[<?php echo $key; ?>][attachment_id]" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_fb_shares[<?php echo $key; ?>][image]" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $args['image'] ); ?>" />

				<span class="ppp-upload-file" style="<?php echo $hide; ?>">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>

		<td>
			<a href="#" class="ppp-repeatable-row ppp-remove-repeatable" data-type="facebook" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;<?php echo $hide; ?>">&times;</a>
		</td>

	</tr>
<?php
}

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 * @return int          The Post ID
 */
function ppp_fb_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_fb_share_on_publish            = ( isset( $_REQUEST['_ppp_fb_share_on_publish'] ) )               ? $_REQUEST['_ppp_fb_share_on_publish']               : '-1';
	$ppp_share_on_publish_title         = ( isset( $_REQUEST['_ppp_fb_share_on_publish_title'] ) )         ? $_REQUEST['_ppp_fb_share_on_publish_title']         : '';
	$ppp_share_on_publish_image_url     = ( isset( $_REQUEST['_ppp_fb_share_on_publish_image_url'] ) )     ? $_REQUEST['_ppp_fb_share_on_publish_image_url']     : '';
	$ppp_share_on_publish_attachment_id = ( isset( $_REQUEST['_ppp_fb_share_on_publish_attachment_id'] ) ) ? $_REQUEST['_ppp_fb_share_on_publish_attachment_id'] : '';

	update_post_meta( $post_id, '_ppp_fb_share_on_publish',               $ppp_fb_share_on_publish );
	update_post_meta( $post_id, '_ppp_fb_share_on_publish_title',         $ppp_share_on_publish_title );
	update_post_meta( $post_id, '_ppp_fb_share_on_publish_image_url',     $ppp_share_on_publish_image_url );
	update_post_meta( $post_id, '_ppp_fb_share_on_publish_attachment_id', $ppp_share_on_publish_attachment_id );

	$fb_data = ( isset( $_REQUEST['_ppp_fb_shares'] ) && empty( $ppp_fb_share_on_publish ) ) ? $_REQUEST['_ppp_fb_shares'] : array();
	foreach ( $fb_data as $index => $share ) {
		$fb_data[ $index ]['text'] = sanitize_text_field( $share['text'] );
	}

	update_post_meta( $post_id, '_ppp_fb_shares', $fb_data );
}
add_action( 'save_post', 'ppp_fb_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Share a Facebook post on Publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post object
 * @return void
 */
function ppp_fb_share_on_publish( $new_status, $old_status, $post ) {
	global $ppp_options;

	$from_meta = ! empty( $_POST['ppp_post_edit'] ) ? false : get_post_meta( $post->ID, '_ppp_fb_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_fb_share_on_publish'] ) ? $_POST['_ppp_fb_share_on_publish'] : '0';

	if ( '1' != $from_meta && '1' != $from_post ) {
		return;
	}

	$from_meta = $from_meta == '1' ? true : false;
	$from_post = $from_post == '1' ? true : false;

	$title         = '';
	$attachment_id = 0;
	$image_url     = '';

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && ! $from_post ) {
		$title         = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_title',         true );
		$attachment_id = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_attachment_id', true );
		$image_url     = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_image_url',     true );
	} else {
		$title         = isset( $_POST['_ppp_fb_share_on_publish_title'] )         ? $_POST['_ppp_fb_share_on_publish_title']         : '';
		$attachment_id = isset( $_POST['_ppp_fb_share_on_publish_attachment_id'] ) ? $_POST['_ppp_fb_share_on_publish_attachment_id'] : 0;
		$image_url     = isset( $_POST['_ppp_fb_share_on_publish_image_url'] )     ? $_POST['_ppp_fb_share_on_publish_image_url']     : '';
	}

	$thumbnail = '';
	if ( empty( $attachment_id ) && ! empty( $image_url ) ) {
		$thumbnail = $image_url;
	} else {
		$thumbnail = ppp_post_has_media( $post->ID, 'fb', true, $attachment_id );
	}

	$name = 'sharedate_0_' . $post->ID . '_fb';

	$default_title = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	// If an override was found, use it, otherwise try the default text content
	if ( empty( $title ) && empty( $default_title ) ) {
		$title = get_the_title( $post->ID );
	}

	$title = apply_filters( 'ppp_share_content', $title, array( 'post_id' => $post->ID ) );
	$link  = ppp_generate_link( $post->ID, $name, true );

	$status = ppp_fb_share( $link, $title, $thumbnail );

	$log_title = ppp_fb_build_share_message( $post->ID, $name );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  => '',
		'post_parent'   => $post->ID,
		'log_type'      => 'ppp_share'
	);

	$log_meta = array(
		'network'   => 'fb',
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	update_post_meta( $log_entry, '_ppp_share_status', $status );
}
add_action( 'ppp_share_on_publish', 'ppp_fb_share_on_publish', 10, 3 );

/**
 * Generate the timestamps and names for the scheduled Facebook shares
 *
 * @since  2.3
 * @param  array $times   The times to save
 * @param  int   $post_id The Post ID of the item being saved
 * @return array          Array of timestamps and cron names
 */
function ppp_fb_generate_timestamps( $times, $post_id ) {
	$fb_shares = get_post_meta( $post_id, '_ppp_fb_shares', true );

	if ( empty( $fb_shares ) ) {
		$fb_shares = array();
	}

	foreach ( $fb_shares as $key => $data ) {
		if ( ! array_filter( $data ) ) {
			continue;
		}

		$timestamp = ppp_generate_timestamp( $data['date'], $data['time'] );

		if ( $timestamp > current_time( 'timestamp', 1 ) ) { // Make sure the timestamp we're getting is in the future
			$time_key           = strtotime( date_i18n( 'd-m-Y H:i:s', $timestamp , true ) ) . '_fb';
			$times[ $time_key ] = 'sharedate_' . $key . '_' . $post_id . '_fb';
		}

	}

	return $times;
}
add_filter( 'ppp_get_timestamps', 'ppp_fb_generate_timestamps', 10, 2 );

/**
 * Build the text for the Facebook share
 *
 * @since  2.3
 * @param  int     $post_id   The Post ID
 * @param  string  $name      The cron name
 * @param  boolean $scheduled If the item is being fired by a schedule (default, true), or retrieved for display (false)
 * @return string             The message to share
 */
function ppp_fb_build_share_message( $post_id, $name, $scheduled = true ) {
	$share_content = ppp_fb_generate_share_content( $post_id, $name );

	return apply_filters( 'ppp_fb_build_share_message', $share_content );
}

/**
 * Build the link for the Facebook Share
 *
 * @since  2.3
 * @param  int     $post_id   The post ID being shared
 * @param  string  $name      The cron name
 * @param  boolean $scheduled If the item is being fired by a schedule (default, true), or retrieved for display (false)
 * @return string             The formatted link to the post
 */
function ppp_fb_build_share_link( $post_id, $name, $scheduled = true ) {
	$share_link = ppp_generate_link( $post_id, $name, $scheduled );

	return $share_link;
}

/**
 * The worker function for ppp_fb_build_share_message
 *
 * @since  2.3
 * @param  int     $post_id      Post ID
 * @param  string  $name         The cron name
 * @param  boolean $scheduled    If the item is being fired by a schedule (default, true), or retrieved for display (false)
 * @return string                The formatted link to the post
 */
function ppp_fb_generate_share_content( $post_id, $name, $is_scheduled = true ) {
	global $ppp_options;
	$default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	$fb_shares    = get_post_meta( $post_id, '_ppp_fb_shares', true );

	if ( $is_scheduled && ! empty( $fb_shares ) ) {
		$name_array    = explode( '_', $name );
		$index         = $name_array[1];
		$share_content = $fb_shares[ $index ]['text'];
	}

	// If an override was found, use it, otherwise try the default text content
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : $default_text;

	// If the content is still empty, just use the post title
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content_fb', $share_content, array( 'post_id' => $post_id ) );
}

/**
 * Return if media is supported for this scheduled post
 * @param  int $post_id The Post ID
 * @param  int $index   The index of this tweet in the _ppp_tweets data
 * @return bool         Whether or not this tweet should contain a media post
 */
function ppp_fb_use_media( $post_id, $index ) {
	if ( empty( $post_id ) || empty( $index ) ) {
		return false;
	}

	return true; // Always include an image for facebook, even if it's a fallback to the featured image
}

/**
 * Update the Post As field for Facebook
 * @return sends 1 when successfully updated
 */
function ppp_fb_update_page() {
	global $ppp_social_settings, $ppp_facebook_oauth;

	ppp_set_social_tokens();

	$account = isset( $_POST['account'] ) ? $_POST['account'] : false;

	if ( !empty( $account ) ) {
		$ppp_social_settings['facebook']->page = $account;

		update_option( 'ppp_social_settings', $ppp_social_settings );
		echo 1;
	} else {
		echo 0;
	}

	die(); // this is required to return a proper result
}
add_action( 'wp_ajax_fb_set_page', 'ppp_fb_update_page' );

function ppp_fb_calendar_on_publish_event( $events, $post_id ) {
	$share_on_publish = get_post_meta( $post_id, '_ppp_fb_share_on_publish', true );

	if ( ! empty( $share_on_publish ) ) {
		$share_text = get_post_meta( $post_id, '_ppp_fb_share_on_publish_title', true );
		$events[] = array(
			'id' => $post_id . '-share-on-publish',
			'title' => ( ! empty( $share_text ) ) ? $share_text : ppp_fb_generate_share_content( $post_id, null, false ),
			'start'     => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'end'       => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'className' => 'ppp-calendar-item-fb cal-post-' . $post_id,
			'belongsTo' => $post_id,
		);
	}

	return $events;
}
add_filter( 'ppp_calendar_on_publish_event', 'ppp_fb_calendar_on_publish_event', 10, 2 );

function ppp_fb_get_post_shares( $items, $post_id ) {
	$shares = get_post_meta( $post_id, '_ppp_fb_shares', true );
	if ( empty( $shares ) ) { return $items; }

	foreach ( $shares as $key => $share ) {
		$items[] = array( 'id' => $key, 'service' => 'fb' );
	}
	return $items;
}
add_filter( 'ppp_get_post_scheduled_shares', 'ppp_fb_get_post_shares', 10, 2 );
