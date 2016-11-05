<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return if linkedin account is found
 * @return bool If the Linkedin object exists
 */
function ppp_linkedin_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['linkedin'] ) && !empty( $ppp_social_settings['linkedin'] ) ) {
		return true;
	}

	return false;
}

/**
 * Registers LinkedIn as a service
 * @param  array $services The registered servcies
 * @return array           With LinkedIn added
 */
function ppp_li_register_service( $services = array() ) {
	$services[] = 'li';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_li_register_service', 10, 1 );

/**
 * The LinkedIn icon
 * @param  string $string Default list view string for icon
 * @return string         The LinkedIn Icon HTML
 */
function ppp_li_account_list_icon( $string = '' ) {
	$string .= '<span class="dashicons icon-ppp-li"></span>';

	return $string;
}
add_filter( 'ppp_account_list_icon-li', 'ppp_li_account_list_icon', 10, 1 );

/**
 * The LinkedIn Avatar for the account list
 * @param  string $string Default icon string
 * @return string         The HTML for the LinkedIn Avatar
 */
function ppp_li_account_list_avatar( $string = '' ) {
	return $string;
}
add_filter( 'ppp_account_list_avatar-li', 'ppp_li_account_list_avatar', 10, 1 );

/**
 * The name for the linked LinkedIn account
 * @param  string $string The default list name
 * @return string         The name for the attached LinkedIn account
 */
function ppp_li_account_list_name( $string = '' ) {

	if ( ppp_linkedin_enabled() ) {
		global $ppp_social_settings;
		$string .= $ppp_social_settings['linkedin']->firstName . ' ' . $ppp_social_settings['linkedin']->lastName;
		$string .= '<br />' . $ppp_social_settings['linkedin']->headline;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-li', 'ppp_li_account_list_name', 10, 1 );

/**
 * The actions column of the accounts list for LinkedIn
 * @param  string $string The default actions string
 * @return string         HTML for the LinkedIn Actions
 */
function ppp_li_account_list_actions( $string = '' ) {

	if ( ! ppp_linkedin_enabled() ) {
		global $ppp_linkedin_oauth, $ppp_social_settings;
		$li_authurl = $ppp_linkedin_oauth->ppp_get_linkedin_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );

		$string .= '<a class="button-primary" href="' . $li_authurl . '">' . __( 'Connect to Linkedin', 'ppp-txt' ) . '</a>';
	} else {
		$string  .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=linkedin' ) . '" >' . __( 'Disconnect from Linkedin', 'ppp-txt' ) . '</a>&nbsp;';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-li', 'ppp_li_account_list_actions', 10, 1 );

/**
 * The Extras column for the account list for LinkedIn
 * @param  string $string Default extras column string
 * @return string         The HTML for the LinkedIn Extras column
 */
function ppp_li_account_list_extras( $string ) {
	if ( ppp_linkedin_enabled() ) {
		global $ppp_social_settings, $ppp_options;
		if ( ! empty( $ppp_options['enable_debug'] ) ) {
			$days_left  = absint( round( ( $ppp_social_settings['linkedin']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
			$refresh_in = absint( round( ( get_option( '_ppp_linkedin_refresh' ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );

			$string .= '<br />' . sprintf( __( 'Token expires in %s days' , 'ppp-txt' ), $days_left );
			$string .= '<br />' . sprintf( __( 'Refresh notice in %s days', 'ppp-txt' ), $refresh_in );
		}
	}

	return $string;

}
add_filter( 'ppp_account_list_extras-li', 'ppp_li_account_list_extras', 10, 1 );

/**
 * Capture the oauth return from linkedin
 * @return void
 */
function ppp_capture_linkedin_oauth() {
	$should_capture = false;

	if ( isset( $_GET['state'] ) && strpos( $_GET['state'], 'ppp-local-keys-li' ) !== false ) {
		// Local config
		$should_capture = true;
	}

	if ( isset( $_REQUEST['li_access_token'] ) ) {
		// Returning from remote config
		$should_capture = true;
	}

	if ( $should_capture && ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ppp-social-settings' ) ) {
		global $ppp_linkedin_oauth;
		$ppp_linkedin_oauth->ppp_initialize_linkedin();
		wp_redirect( admin_url( 'admin.php?page=ppp-social-settings' ) );
		die();
	}
}
add_action( 'admin_init', 'ppp_capture_linkedin_oauth', 10 );

/**
 * Capture the disconnect request from Linkedin
 * @return void
 */
function ppp_disconnect_linkedin() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['linkedin'] ) ) {
		unset( $ppp_social_settings['linkedin'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
		delete_option( '_ppp_linkedin_refresh' );
	}
}
add_action( 'ppp_disconnect-linkedin', 'ppp_disconnect_linkedin', 10 );

/**
 * Add query vars for Linkedin
 * @param  array $vars Currenty Query Vars
 * @return array       Query vars array with linkedin added
 */
function ppp_li_query_vars( $vars ) {
	$vars[] = 'li_access_token';
	$vars[] = 'expires_in';

	return $vars;
}
add_filter( 'query_vars', 'ppp_li_query_vars' );

/**
 * Refreshes the Linkedin Access Token
 * @return void
 */
function ppp_li_execute_refresh() {

	if ( ! ppp_linkedin_enabled() ) {
		return;
	}

	$refresh_date = (int) get_option( '_ppp_linkedin_refresh', true );

	if ( current_time( 'timestamp' ) > $refresh_date ) {
		add_action( 'admin_notices', 'ppp_linkedin_refresh_notice' );
	}
}
add_action( 'admin_init', 'ppp_li_execute_refresh' );

/**
 * Displays notice when the Linkedin Token is nearing expiration
 * @return void
 */
function ppp_linkedin_refresh_notice() {

	if ( ! ppp_linkedin_enabled() ) {
		return;
	}

	$has_dismissed = get_transient( 'ppp-dismiss-refresh-li' . get_current_user_id() );
	if ( false !== $has_dismissed ) {
		return;
	}

	global $ppp_linkedin_oauth, $ppp_social_settings;

	// Look for the tokens coming back
	$ppp_linkedin_oauth->ppp_initialize_linkedin();

	$token = $ppp_social_settings['linkedin']->access_token;
	$url = $ppp_linkedin_oauth->ppp_get_linkedin_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );
	$url = str_replace( '?ppp-social-auth', '?ppp-social-auth&ppp-refresh=true&access_token=' . $token, $url );

	$days_left = (int) round( ( $ppp_social_settings['linkedin']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
	?>
	<div class="notice notice-warning is-dismissible" data-service="li">
		<?php if ( $days_left > 0 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your LinkedIn authentication expires in %d days. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $days_left, $url ); ?></p>
		<?php elseif ( $days_left < 1 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your LinkedIn authentication has expired. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $url ); ?></p>
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
function ppp_li_dismiss_notice() {

	$nag = sanitize_key( $_POST[ 'nag' ] );

	if ( $nag === $_POST[ 'nag' ] ) {
		set_transient( $nag . get_current_user_id(), true, DAY_IN_SECONDS );
	}


}
add_action( 'wp_ajax_ppp_dismiss_notice-li', 'ppp_li_dismiss_notice' );

/**
 * Define the linkedin tokens as constants
 * @param  array $social_tokens The Keys
 * @return void
 */
function ppp_set_li_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'linkedin' ) ) {
		define( 'LINKEDIN_KEY', $social_tokens->linkedin->api_key );
		define( 'LINKEDIN_SECRET', $social_tokens->linkedin->secret_key );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_li_token_constants', 10, 1 );

/**
 * Share a post to Linkedin
 * @param  string $title       The Title of the Linkedin Post
 * @param  string $description The Description of the post
 * @param  string $link        The URL to the post
 * @param  mixed  $media       False for no media, url to the image if exists
 * @return array               The results array from the API
 */
function ppp_li_share( $title, $description, $link, $media ) {
	global $ppp_linkedin_oauth;
	$args = array (
		'title' => ppp_entities_and_slashes( $title ),
		'description' => ppp_entities_and_slashes( $description ),
		'submitted-url' => $link,
		'submitted-image-url' => $media
		);

	return $ppp_linkedin_oauth->ppp_linkedin_share( $args );
}

/**
 * Add the LinkedIn tab to the social media area
 * @param  array $tabs The existing tabs
 * @return array       The tabs with LinkedIn Added
 */
function ppp_li_add_admin_tab( $tabs ) {
	$tabs['li'] = array( 'name' => __( 'LinkedIn', 'ppp-txt' ), 'class' => 'icon-ppp-li' );

	return $tabs;
}
add_filter( 'ppp_admin_tabs', 'ppp_li_add_admin_tab', 10, 1 );

/**
 * Add the content box for LinkedIn in the social media settings
 * @param  array $content The existing content blocks
 * @return array          With LinkedIn
 */
function ppp_li_register_admin_social_content( $content ) {
	$content[] = 'li';

	return $content;
}
add_filter( 'ppp_admin_social_content', 'ppp_li_register_admin_social_content', 10, 1 );

/**
 * Add LinkedIn to the Meta Box Tabs
 * @param  array $tabs Existing Metabox Tabs
 * @return array       Metabox tabs with LinkedIn
 */
function ppp_li_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( ! ppp_linkedin_enabled() ) {
		return $tabs;
	}

	$tabs['li'] = array( 'name' => __( 'LinkedIn', 'ppp-txt' ), 'class' => 'icon-ppp-li' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_li_add_meta_tab', 10, 1 );

/**
 * Add LinkedIn to the Metabox Content
 * @param  array $content The existing metabox content
 * @return array          With LinkedIn
 */
function ppp_li_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( ! ppp_linkedin_enabled() ) {
		return $content;
	}

	$content[] = 'li';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_li_register_metabox_content', 10, 1 );

/**
 * Returns the stored LinkedIn data for a post
 *
 * @since  2.3
 * @param  array $post_meta Array of meta data (empty)
 * @param  int   $post_id   The Post ID to get the meta for
 * @return array            The stored LinkedIn shares for a post
 */
function ppp_li_get_post_meta( $post_meta, $post_id ) {
	return get_post_meta( $post_id, '_ppp_li_shares', true );
}
add_filter( 'ppp_get_scheduled_items_li', 'ppp_li_get_post_meta', 10, 2 );

/**
 * Registers the thumbnail size for LinkedIn
 * @return void
 */
function ppp_li_register_thumbnail_size() {
	add_image_size( 'ppp-li-share-image', 800, 800, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_li_register_thumbnail_size' );

/**
 * Render the Metabox content for LinkedIn
 * @param  object $post The post object
 */
function ppp_li_add_metabox_content( $post ) {
	global $ppp_options, $ppp_share_settings;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_li_share_on_publish               = get_post_meta( $post->ID, '_ppp_li_share_on_publish', true );
	$ppp_share_on_publish_title            = get_post_meta( $post->ID, '_ppp_li_share_on_publish_title', true );
	$ppp_share_on_publish_desc             = get_post_meta( $post->ID, '_ppp_li_share_on_publish_desc', true );
	$ppp_li_share_on_publish_attachment_id = get_post_meta( $post->ID, '_ppp_li_share_on_publish_attachment_id', true );
	$ppp_li_share_on_publish_image_url     = get_post_meta( $post->ID, '_ppp_li_share_on_publish_image_url', true );

	$show_share_on_publish = false;

	$share_by_default      = empty( $ppp_share_settings['linkedin']['share_on_publish'] ) ? false : true;

	if ( $ppp_li_share_on_publish == '1' || ( $ppp_li_share_on_publish == '' && $share_by_default ) ) {
		$show_share_on_publish = true;
	}
	?>
	<p>
		<div class="ppp-post-override-wrap">
			<p><h3><?php _e( 'Share on LinkedIn', 'ppp-txt' ); ?></h3></p>
			<p>
				<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
				<label for="ppp_li_share_on_publish"><?php _e( 'Share this post on LinkedIn&hellip;', 'ppp-txt' ); ?></label>
				<select name="_ppp_li_share_on_publish" id="ppp_li_share_on_publish" class="ppp-toggle-share-on-publish">
					<option value="-1" <?php selected( true, $show_share_on_publish, true ); ?><?php if ( $disabled ): ?>disabled<?php endif; ?>><?php _e( 'Do not share this post', 'ppp-txt' ); ?></option>
					<option value="1" <?php selected( true, $show_share_on_publish, true ); ?><?php if ( $disabled ): ?>disabled<?php endif; ?>><?php _e( 'When this post is published', 'ppp-txt' ); ?></option>
					<option value="0" <?php selected( false, $show_share_on_publish, true ); ?>><?php _e( 'After this post is published', 'ppp-txt' ); ?></option>
				</select>
			</p>
			<div id="ppp-li-fields" class="ppp-fields">
				<div id="ppp-li-fields" class="ppp-meta-table-wrap">
					<table class="widefat ppp-repeatable-table" width="100%" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th style="width: 100px"><?php _e( 'Date', 'ppp-txt' ); ?></th>
								<th style="width: 75px;"><?php _e( 'Time', 'ppp-txt' ); ?></th>
								<th><?php _e( 'Link Info', 'ppp-txt' ); ?></th>
								<th style"width: 200px;"><?php _e( 'Image', 'ppp-txt' ); ?></th>
								<th style="width: 10px;"></th>
							</tr>
						</thead>
						<tbody id="li-share-on-publish" class="ppp-share-on-publish" <?php if ( false === $show_share_on_publish ) : echo 'style="display: none;"'; endif; ?>>
							<?php
								$args = array(
									'text'          => $ppp_share_on_publish_title,
									'desc'          => $ppp_share_on_publish_desc,
									'attachment_id' => $ppp_li_share_on_publish_attachment_id,
									'image'         => $ppp_li_share_on_publish_image_url,
								);

								ppp_render_li_share_on_publish_row( $args );
							?>
						</tbody>
						<tbody id="li-schedule-share" class="ppp-schedule-share" <?php if ( true === $show_share_on_publish ) : echo 'style="display: none;"'; endif; ?>>
							<?php $shares = get_post_meta( $post->ID, '_ppp_li_shares', true ); ?>
							<?php if ( ! empty( $shares ) ) : ?>

								<?php foreach ( $shares as $key => $value ) :
									$date          = isset( $value['date'] )          ? $value['date']          : '';
									$time          = isset( $value['time'] )          ? $value['time']          : '';
									$text          = isset( $value['text'] )          ? $value['text']          : '';
									$desc          = isset( $value['desc'] )          ? $value['desc']          : '';
									$image         = isset( $value['image'] )         ? $value['image']         : '';
									$attachment_id = isset( $value['attachment_id'] ) ? $value['attachment_id'] : '';

									$args = apply_filters( 'ppp_fb_row_args', compact( 'date','time','text', 'desc', 'image','attachment_id' ), $value );
									?>

									<?php ppp_render_li_share_row( $key, $args ); ?>


								<?php endforeach; ?>

							<?php else: ?>

								<?php ppp_render_li_share_row( 1, array( 'date' => '', 'time' => '', 'text' => '', 'desc' => '', 'image' => '', 'attachment_id' => '' ) ); ?>

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
add_action( 'ppp_generate_metabox_content-li', 'ppp_li_add_metabox_content', 10, 1 );

/**
 * Render the LinkedIn share on publish row
 *
 * @since  2.3
 * @param  array  $args Contains share on publish data, if there is any
 * @return void
 */
function ppp_render_li_share_on_publish_row( $args = array() ) {
	global $post;
	$readonly = $post->post_status !== 'publish' ? '' : 'readonly="readonly" ';
	$disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false;
	?>
	<tr class="ppp-li-wrapper ppp-repeatable-row on-publish-row">
		<td colspan="2" class="ppp-on-plublish-date-column">
			<?php _e( 'Share On Publish', 'ppp-txt' ); ?>
		</td>

		<td>
			<input <?php echo $readonly; ?>class="ppp-tweet-text-repeatable" type="text" name="_ppp_li_share_on_publish_title" value="<?php echo esc_attr( $args['text'] ); ?>" placeholder="<?php _e( 'Link Title', 'ppp-txt' ); ?>" />
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px" colspan="2">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_li_share_on_publish_attachment_id" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_li_share_on_publish_image_url" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $args['image'] ); ?>" />

				<span class="ppp-upload-file">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>
	</tr>
	<tr>
		<td colspan="2"></td>
		<td colspan="3">
			<textarea <?php if ( $disabled ): ?>readonly<?php endif; ?> name="_ppp_li_share_on_publish_desc" placeholder="<?php _e( 'Link Description', 'ppp-txt' ); ?>"><?php echo esc_attr( $args['desc'] ); ?></textarea>
		</td>
	</tr>
<?php
}

/**
 * Render the scheduled share row for LinkedIn
 *
 * @since  2.3
 * @param  int $key        The key in the array
 * @param  array  $args    Arguements for the current post's share data
 * @param  int    $post_id The post ID being edited
 * @return void
 */
function ppp_render_li_share_row( $key, $args = array() ) {
	global $post;

	$share_time = ppp_generate_timestamp( $args['date'], $args['time'] );
	$readonly   = ppp_generate_timestamp() > $share_time ? 'readonly="readonly" ' : false;
	$no_date    = ! empty( $readonly ) ? ' hasDatepicker' : '';
	$hide       = ! empty( $readonly ) ? 'display: none;' : '';
	?>
	<tr class="ppp-li-wrapper ppp-repeatable-row ppp-repeatable-linkedin scheduled-row" data-key="<?php echo esc_attr( $key ); ?>">
		<td>
			<input <?php echo $readonly; ?>type="text" class="share-date-selector<?php echo $no_date; ?>" name="_ppp_li_shares[<?php echo $key; ?>][date]" placeholder="mm/dd/yyyy" value="<?php echo $args['date']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>type="text" class="share-time-selector" name="_ppp_li_shares[<?php echo $key; ?>][time]" value="<?php echo $args['time']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>class="ppp-tweet-text-repeatable" type="text" name="_ppp_li_shares[<?php echo $key; ?>][text]" value="<?php echo esc_attr( $args['text'] ); ?>" placeholder="<?php _e( 'Link Title', 'ppp-txt' ); ?>"/>
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_li_shares[<?php echo $key; ?>][attachment_id]" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_li_shares[<?php echo $key; ?>][image]" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $args['image'] ); ?>" />

				<span class="ppp-upload-file" style="<?php echo $hide; ?>">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>

		<td>
			<a href="#" class="ppp-repeatable-row ppp-remove-repeatable" data-type="linkedin" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;<?php echo $hide; ?>">&times;</a>
		</td>

	</tr>
	<tr>
		<td colspan="2"></td>
		<td colspan="3">
			<textarea <?php echo $readonly; ?> class="ppp-repeatable-textarea" name="_ppp_li_shares[<?php echo $key; ?>][desc]" placeholder="<?php _e( 'Link Description', 'ppp-txt' ); ?>"><?php echo esc_attr( $args['desc'] ); ?></textarea>
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
function ppp_li_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_li_share_on_publish            = ( isset( $_REQUEST['_ppp_li_share_on_publish'] ) )               ? $_REQUEST['_ppp_li_share_on_publish']               : '-1';
	$ppp_share_on_publish_title         = ( isset( $_REQUEST['_ppp_li_share_on_publish_title'] ) )         ? $_REQUEST['_ppp_li_share_on_publish_title']         : '';
	$ppp_share_on_publish_desc          = ( isset( $_REQUEST['_ppp_li_share_on_publish_desc'] ) )          ? $_REQUEST['_ppp_li_share_on_publish_desc']          : '';
	$ppp_share_on_publish_image_url     = ( isset( $_REQUEST['_ppp_li_share_on_publish_image_url'] ) )     ? $_REQUEST['_ppp_li_share_on_publish_image_url']     : '';
	$ppp_share_on_publish_attachment_id = ( isset( $_REQUEST['_ppp_li_share_on_publish_attachment_id'] ) ) ? $_REQUEST['_ppp_li_share_on_publish_attachment_id'] : '';

	update_post_meta( $post_id, '_ppp_li_share_on_publish',               $ppp_li_share_on_publish );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_title',         $ppp_share_on_publish_title );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_desc',          $ppp_share_on_publish_desc );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_image_url',     $ppp_share_on_publish_image_url );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_attachment_id', $ppp_share_on_publish_attachment_id );

	$li_data = ( isset( $_REQUEST['_ppp_li_shares'] ) && empty( $ppp_li_share_on_publish ) ) ? $_REQUEST['_ppp_li_shares'] : array();
	foreach ( $li_data as $index => $share ) {
		$li_data[ $index ]['text'] = sanitize_text_field( $share['text'] );
		$li_data[ $index ]['desc'] = sanitize_text_field( $share['desc'] );
	}

	update_post_meta( $post_id, '_ppp_li_shares', $li_data );
}
add_action( 'save_post', 'ppp_li_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Share a linkedin post on Publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post object
 * @return void
 */
function ppp_li_share_on_publish( $new_status, $old_status, $post ) {
	global $ppp_options;
	$from_meta = ! empty( $_POST['ppp_post_edit'] ) ? false : get_post_meta( $post->ID, '_ppp_li_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_li_share_on_publish'] ) ? $_POST['_ppp_li_share_on_publish']: '0';

	if ( '1' != $from_meta && '1' != $from_post ) {
		return;
	}

	$from_meta = $from_meta == '1' ? true : false;
	$from_post = $from_post == '1' ? true : false;

	$title         = '';
	$desc          = '';
	$attachment_id = 0;
	$image_url     = '';

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && ! $from_post ) {
		$title         = get_post_meta( $post->ID, '_ppp_li_share_on_publish_title'        , true );
		$desc          = get_post_meta( $post->ID, '_ppp_li_share_on_publish_desc'         , true );
		$attachment_id = get_post_meta( $post->ID, '_ppp_li_share_on_publish_attachment_id', true );
		$image_url     = get_post_meta( $post->ID, '_ppp_li_share_on_publish_image_url'    , true );
	} else {
		$title         = isset( $_POST['_ppp_li_share_on_publish_title'] )         ? $_POST['_ppp_li_share_on_publish_title']         : '';
		$desc          = isset( $_POST['_ppp_li_share_on_publish_desc'] )          ? $_POST['_ppp_li_share_on_publish_desc']          : false;
		$attachment_id = isset( $_POST['_ppp_li_share_on_publish_attachment_id'] ) ? $_POST['_ppp_li_share_on_publish_attachment_id'] : 0;
		$image_url     = isset( $_POST['_ppp_li_share_on_publish_image_url'] )     ? $_POST['_ppp_li_share_on_publish_image_url']     : '';
	}

	$thumbnail = '';
	if ( empty( $attachment_id ) && ! empty( $image_url ) ) {
		$thumbnail = $image_url;
	} else {
		$thumbnail = ppp_post_has_media( $post->ID, 'li', true, $attachment_id );
	}

	$name = 'sharedate_0_' . $post->ID . '_li';

	$default_title = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	// If an override was found, use it, otherwise try the default text content
	if ( empty( $title ) && empty( $default_title ) ) {
		$title = get_the_title( $post->ID );
	}

	$link      = ppp_generate_link( $post->ID, $name, true );
	$status    = ppp_li_share( $title, $desc, $link, $thumbnail );
	$log_title = ppp_li_build_share_message( $post->ID, $name );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  => '',
		'post_parent'   => $post->ID,
		'log_type'      => 'ppp_share',
	);

	$log_meta = array(
		'network'   => 'li',
		'share_id'  => 0,
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	update_post_meta( $log_entry, '_ppp_share_status', $status );
}
add_action( 'ppp_share_on_publish', 'ppp_li_share_on_publish', 10, 3 );

/**
 * Send out a scheduled share to LinkedIn
 *
 * @since  2.3
 * @param  integer $post_id The Post ID to share fore
 * @param  integer $index   The index in the shares
 * @param  string  $name    The name of the Cron
 * @return void
 */
function ppp_li_scheduled_share(  $post_id = 0, $index = 1, $name = ''  ) {
	global $ppp_options;

	$link = ppp_generate_link( $post_id, $name );

	$post_meta     = get_post_meta( $post_id, '_ppp_li_shares', true );
	$this_share    = $post_meta[ $index ];
	$attachment_id = isset( $this_share['attachment_id'] ) ? $this_share['attachment_id'] : false;

	$share_message = ppp_li_build_share_message( $post_id, $name );

	if ( empty( $attachment_id ) && ! empty( $this_share['image'] ) ) {
		$media = $this_share['image'];
	} else {
		$use_media = ppp_li_use_media( $post_id, $index );
		$media     = ppp_post_has_media( $post_id, 'li', $use_media, $attachment_id );
	}

	$desc      = ppp_li_get_share_description( $post_id, $index );
	$status    = ppp_li_share( $share_message, $desc, $link, $media );
	$log_title = ppp_li_build_share_message( $post_id, $name );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  => '',
		'post_parent'   => $post_id,
		'log_type'      => 'ppp_share'
	);

	$log_meta = array(
		'network'   => 'li',
		'share_id'  => $index,
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	update_post_meta( $log_entry, '_ppp_share_status', $status );

}
add_action( 'ppp_share_scheduled_li', 'ppp_li_scheduled_share', 10, 3 );

/**
 * Return if media is supported for this scheduled post
 * @param  int $post_id The Post ID
 * @param  int $index   The index of this tweet in the _ppp_tweets data
 * @return bool         Whether or not this tweet should contain a media post
 */
function ppp_li_use_media( $post_id, $index ) {
	if ( empty( $post_id ) || empty( $index ) ) {
		return false;
	}

	return true; // Always include an image for facebook, even if it's a fallback to the featured image
}

/**
 * Build the text for the LinkedIn share
 *
 * @since  2.3
 * @param  int     $post_id   The Post ID
 * @param  string  $name      The cron name
 * @param  boolean $scheduled If the item is being fired by a schedule (default, true), or retrieved for display (false)
 * @return string             The message to share
 */
function ppp_li_build_share_message( $post_id, $name, $scheduled = true ) {
	$share_content = ppp_li_generate_share_content( $post_id, $name );

	return apply_filters( 'ppp_li_build_share_message', $share_content );
}

/**
 * The worker function for ppp_li_build_share_message
 *
 * @since  2.3
 * @param  int     $post_id      Post ID
 * @param  string  $name         The cron name
 * @param  boolean $scheduled    If the item is being fired by a schedule (default, true), or retrieved for display (false)
 * @return string                The formatted link to the post
 */
function ppp_li_generate_share_content( $post_id, $name, $is_scheduled = true ) {
	global $ppp_options;
	$default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	$li_shares    = get_post_meta( $post_id, '_ppp_li_shares', true );

	if ( $is_scheduled && ! empty( $li_shares ) ) {
		$name_array    = explode( '_', $name );
		$index         = $name_array[1];
		$share_content = $li_shares[ $index ]['text'];
	}

	// If an override was found, use it, otherwise try the default text content
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : $default_text;

	// If the content is still empty, just use the post title
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content_li', $share_content, array( 'post_id' => $post_id ) );
}

/**
 * Builds out the link description for sharing to LinkedIn
 *
 * @since  2.3
 * @param  int $post_id The Post Id
 * @param  int $index   The share index
 * @return string       Link description for the share index of the given post ID
 */
function ppp_li_get_share_description( $post_id, $index ) {
	$description = '';
	$li_shares   = get_post_meta( $post_id, '_ppp_li_shares', true );

	if ( ! empty( $li_shares[ $index ] ) ) {
		$description = ! empty( $li_shares[ $index ]['desc'] ) ? $li_shares[ $index ]['desc'] : '';
	}

	return $description;
}

/**
 * Generate the timestamps and names for the scheduled LinkedIn shares
 *
 * @since  2.3
 * @param  array $times   The times to save
 * @param  int   $post_id The Post ID of the item being saved
 * @return array          Array of timestamps and cron names
 */
function ppp_li_generate_timestamps( $times, $post_id ) {
	$li_shares = get_post_meta( $post_id, '_ppp_li_shares', true );

	if ( empty( $li_shares ) ) {
		$li_shares = array();
	}

	foreach ( $li_shares as $key => $data ) {
		if ( ! array_filter( $data ) ) {
			continue;
		}

		$timestamp = ppp_generate_timestamp( $data['date'], $data['time'] );

		if ( $timestamp > current_time( 'timestamp', 1 ) ) { // Make sure the timestamp we're getting is in the future
			$time_key           = strtotime( date_i18n( 'd-m-Y H:i:s', $timestamp , true ) ) . '_li';
			$times[ $time_key ] = 'sharedate_' . $key . '_' . $post_id . '_li';
		}

	}

	return $times;
}
add_filter( 'ppp_get_timestamps', 'ppp_li_generate_timestamps', 10, 2 );

/**
 * Builds a list of events for the Schedule calendar
 *
 * @since  2.3
 * @param  array $events  The currently found events
 * @param  int   $post_id Post ID to find items for
 * @return array          Array of events with any LinkedIn items
 */
function ppp_li_calendar_on_publish_event( $events, $post_id ) {
	$share_on_publish = get_post_meta( $post_id, '_ppp_li_share_on_publish', true );

	if ( ! empty( $share_on_publish ) ) {
		$share_text = get_post_meta( $post_id, '_ppp_li_share_on_publish_title', true );
		$events[] = array(
			'id'        => $post_id . '-share-on-publish',
			'title'     => ( ! empty( $share_text ) ) ? $share_text : ppp_li_generate_share_content( $post_id, null, false ),
			'start'     => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'end'       => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'className' => 'ppp-calendar-item-li cal-post-' . $post_id,
			'belongsTo' => $post_id,
		);
	}

	return $events;
}
add_filter( 'ppp_calendar_on_publish_event', 'ppp_li_calendar_on_publish_event', 10, 2 );

function ppp_li_get_post_shares( $items, $post_id ) {
	$shares = get_post_meta( $post_id, '_ppp_li_shares', true );
	if ( empty( $shares ) ) { return $items; }

	foreach ( $shares as $key => $share ) {
		$items[] = array( 'id' => $key, 'service' => 'li' );
	}
	return $items;
}
add_filter( 'ppp_get_post_scheduled_shares', 'ppp_li_get_post_shares', 10, 2 );
