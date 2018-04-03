<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return if twitter account is found
 * @return bool If the Twitter object exists
 */
function ppp_twitter_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['twitter'] ) && !empty( $ppp_social_settings['twitter'] ) ) {
		return true;
	}

	return false;
}

/**
 * Register Twitter as a servcie
 * @param  array $services The registered services
 * @return array           With Twitter added
 */
function ppp_tw_register_service( $services = array() ) {
	$services[] = 'tw';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_tw_register_service', 10, 1 );

/**
 * The Twitter Icon
 * @param  string $string The default icon
 * @return string         The HTML for the Twitter Icon
 */
function ppp_tw_account_list_icon( $string = '' ) {
	$string .= '<span class="dashicons icon-ppp-tw"></span>';

	return $string;
}
add_filter( 'ppp_account_list_icon-tw', 'ppp_tw_account_list_icon', 10, 1 );

/**
 * The avatar for the connected Twitter Account
 * @param  string $string Default avatar string
 * @return string         The Twitter avatar
 */
function ppp_tw_account_list_avatar( $string = '' ) {

	if ( ppp_twitter_enabled() ) {
		global $ppp_social_settings;
		$avatar_url = $ppp_social_settings['twitter']['user']->profile_image_url_https;
		$string .= '<img class="ppp-social-icon" src="' . $avatar_url . '" />';
	}

	return $string;
}
add_filter( 'ppp_account_list_avatar-tw', 'ppp_tw_account_list_avatar', 10, 1 );

/**
 * The name of the connected Twitter account for the list view
 * @param  string $string The default name
 * @return string         The name from Twitter
 */
function ppp_tw_account_list_name( $string = '' ) {

	if ( ppp_twitter_enabled() ) {
		global $ppp_social_settings;
		$string .= $ppp_social_settings['twitter']['user']->name;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-tw', 'ppp_tw_account_list_name', 10, 1 );

/**
 * The actions for the Twitter account list
 * @param  string $string The default actions
 * @return string         The actions buttons HTML for Twitter
 */
function ppp_tw_account_list_actions( $string = '' ) {

	if ( ! ppp_twitter_enabled() ) {
		global $ppp_twitter_oauth, $ppp_social_settings;
		$tw_auth    = $ppp_twitter_oauth->ppp_verify_twitter_credentials();
		$tw_authurl = $ppp_twitter_oauth->ppp_get_twitter_auth_url();

		$string .= '<a href="' . $tw_authurl . '"><img src="' . PPP_URL . '/includes/images/sign-in-with-twitter-gray.png" /></a>';
	} else {
		$string .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=twitter' ) . '" >' . __( 'Disconnect from Twitter', 'ppp-txt' ) . '</a>&nbsp;';
		$string .= '<a class="button-secondary" href="https://twitter.com/settings/applications" target="blank">' . __( 'Revoke Access via Twitter', 'ppp-txt' ) . '</a>';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-tw', 'ppp_tw_account_list_actions', 10, 1 );

/**
 * Listen for the oAuth tokens and verifiers from Twitter when in admin
 * @return void
 */
function ppp_capture_twitter_oauth() {
	if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) {
		$current_screen = get_current_screen();
		if ( 'user-edit' === $current_screen->base ) {
			$user_id = ! empty( $_GET['user_id'] ) && is_numeric( $_GET['user_id'] ) ? $_GET['user_id'] : false;
			$twitter = new PPP_Twitter_User( $user_id );
			$twitter->init();
			$redirect = admin_url( 'user-edit.php?updated=1&user_id=' . $user_id );
		} else {
			global $ppp_twitter_oauth;
			$ppp_twitter_oauth->ppp_initialize_twitter();
			$redirect = admin_url( 'admin.php?page=ppp-social-settings' );
		}
		?>
		<meta http-equiv="refresh" content="0;URL=<?php echo $redirect; ?>">
		<?php
	}
}
add_action( 'admin_head', 'ppp_capture_twitter_oauth', 10 );

/**
 * Listen for the disconnect from Twitter
 * @return void
 */
function ppp_disconnect_twitter() {
	if ( ! empty( $_GET['user_id'] ) ) {
		$user_id = (int) sanitize_text_field( $_GET['user_id'] );
		if ( $user_id !== get_current_user_id() || ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
			wp_die( __( 'Unable to disconnect Twitter account', 'ppp-txt' ) );
		}
		delete_user_meta( $user_id, '_ppp_twitter_data' );
	} else {
		global $ppp_social_settings;
		$ppp_social_settings = get_option( 'ppp_social_settings' );
		if ( isset( $ppp_social_settings['twitter'] ) ) {
			unset( $ppp_social_settings['twitter'] );
			update_option( 'ppp_social_settings', $ppp_social_settings );
		}
	}
}
add_action( 'ppp_disconnect-twitter', 'ppp_disconnect_twitter', 10 );

/**
 * Given a message, sends a tweet
 * @param  string $message The Text to share as the body of the tweet
 * @return object          The Results from the Twitter API
 */
function ppp_send_tweet( $message, $post_id, $use_media = false, $name = '' ) {
	global $ppp_twitter_oauth;

	return apply_filters( 'ppp_twitter_tweet', $ppp_twitter_oauth->ppp_tweet( ppp_entities_and_slashes( $message ), $use_media ) );
}

/**
 * Send out a scheduled share to Twitter
 *
 * @since  2.3
 * @param  integer $post_id The Post ID to share fore
 * @param  integer $index   The index in the shares
 * @param  string  $name    The name of the Cron
 * @return void
 */
function ppp_tw_scheduled_share( $post_id = 0, $index = 1, $name = '' ) {
	global $ppp_options, $wp_logs, $wp_filter;

	$post_meta     = get_post_meta( $post_id, '_ppp_tweets', true );
	$this_share    = $post_meta[ $index ];
	$attachment_id = isset( $this_share['attachment_id'] ) ? $this_share['attachment_id'] : false;

	$share_message = ppp_tw_build_share_message( $post_id, $name );

	if ( empty( $attachment_id ) && ! empty( $this_share['image'] ) ) {
		$media = $this_share['image'];
	} else {
		$use_media = ppp_tw_use_media( $post_id, $index );
		$media     = ppp_post_has_media( $post_id, 'tw', $use_media, $attachment_id );
	}

	$status = ppp_send_tweet( $share_message, $post_id, $media );

	$log_title = ppp_tw_build_share_message( $post_id, $name, false, false );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  => '',
		'post_parent'   => $post_id,
		'log_type'      => 'ppp_share'
	);

	$log_meta = array(
		'network'   => 'tw',
		'share_id'  => $index,
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	update_post_meta( $log_entry, '_ppp_share_status', $status );

	if ( ! empty( $status->id_str ) ) {
		$post      = get_post( $post_id );
		$author_id = $post->post_author;
		$author_rt = get_user_meta( $author_id, '_ppp_share_scheduled', true );

		if ( $author_rt ) {
			$twitter_user = new PPP_Twitter_User( $author_id );
			$twitter_user->retweet( $status->id_str );
		}

		/* Get an array of users who should retweet if another author has a scheduled share
		 * Exclude the author that retweeted earlier to avoid duplicate retweets.
		 */
		$args = array(
			'meta_query' => array(
				array(
					'key' 		=> '_ppp_share_others_scheduled',
					'value'		=> true,
					'compare'	=> '='
				),
			),
			'fields'	=> array( 'ID' ),
			'exclude'	=> array( $author_id )
		);
		$other_rt = get_users( $args );

		if ( $other_rt ){
			foreach ( $other_rt as $user ) {
				$twitter_user = new PPP_Twitter_User( $user->ID );
				$twitter_user->retweet( $status->id_str );
			}
		}
	}
}
add_action( 'ppp_share_scheduled_tw', 'ppp_tw_scheduled_share', 10, 3 );

/**
 * Combines the results from ppp_generate_share_content and ppp_generate_link into a single string
 * @param  int $post_id The Post ID
 * @param  string $name    The 'name' element from the Cron
 * @param  boolean $scheduled If the item is being requsted by a scheduled post
 * @param  bool $include_link If a link should be included in the text
 * @return string          The Full text for the social share
 */
function ppp_tw_build_share_message( $post_id, $name, $scheduled = true, $include_link = true ) {
	$share_content = ppp_tw_generate_share_content( $post_id, $name, $scheduled );

	if ( $include_link ) {
		$share_link    = ppp_generate_link( $post_id, $name, $scheduled );
		$share_content = $share_content . ' ' . $share_link;
	}

	return apply_filters( 'ppp_tw_build_share_message', $share_content );
}

/**
 * Generate the content for the shares
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name' from the cron
 * @return string          The Content to include in the social media post
 */
function ppp_tw_generate_share_content( $post_id, $name, $is_scheduled = true ) {
	global $ppp_options;
	$default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	$ppp_tweets   = get_post_meta( $post_id, '_ppp_tweets', true );

	if ( ! empty( $ppp_tweets ) ) {
		$name_array    = explode( '_', $name );
		$index         = $name_array[1];
		if ( isset( $ppp_tweets[ $index ] ) ) {
			$share_content = $ppp_tweets[ $index ]['text'];
		}
	}

	// If an override was found, use it, otherwise try the default text content
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : $default_text;

	// If the content is still empty, just use the post title
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content', $share_content, array( 'post_id' => $post_id ) );
}

/**
 * Return if media is supported for this scheduled tweet
 * @param  int $post_id The Post ID
 * @param  int $index   The index of this tweet in the _ppp_tweets data
 * @return bool         Whether or not this tweet should contain a media post
 */
function ppp_tw_use_media( $post_id, $index ) {
	if ( empty( $post_id ) || empty( $index ) ) {
		return false;
	}

	$share_data = get_post_meta( $post_id, '_ppp_tweets', true );
	$use_media  = ! empty( $share_data[$index]['attachment_id'] ) || ! empty( $share_data[$index]['image'] ) ? true : false;

	return $use_media;
}

/**
 * Sets the constants for the oAuth tokens for Twitter
 * @param  array $social_tokens The tokens stored in the transient
 * @return void
 */
function ppp_set_tw_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'twitter' ) ) {
		define( 'PPP_TW_CONSUMER_KEY', $social_tokens->twitter->consumer_token );
		define( 'PPP_TW_CONSUMER_SECRET', $social_tokens->twitter->consumer_secret );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_tw_token_constants', 10, 1 );

/**
 * Register Twitter for the Social Media Accounts section
 * @param  array $tabs Array of existing tabs
 * @return array       The Array of existing tabs with Twitter added
 */
function ppp_tw_add_admin_tab( $tabs ) {
	$tabs['tw'] = array( 'name' => __( 'Twitter', 'ppp-txt' ), 'class' => 'icon-ppp-tw' );

	return $tabs;
}
add_filter( 'ppp_admin_tabs', 'ppp_tw_add_admin_tab', 10, 1 );

/**
 * Register the Twitter connection area for the Social Media Accounts section
 * @param  array $content The existing content tokens
 * @return array          The content tokens with Twitter added
 */
function ppp_tw_register_admin_social_content( $content ) {
	$content[] = 'tw';

	return $content;
}
add_filter( 'ppp_admin_social_content', 'ppp_tw_register_admin_social_content', 10, 1 );

/**
 * Register the Twitter metabox tab
 * @param  array $tabs The tabs
 * @return array       The tabs with Twitter added
 */
function ppp_tw_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( !isset( $ppp_social_settings['twitter'] ) ) {
		return $tabs;
	}

	$tabs['tw'] = array( 'name' => __( 'Twitter', 'ppp-txt' ), 'class' => 'icon-ppp-tw' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_tw_add_meta_tab', 10, 1 );

/**
 * Register the metabox content for Twitter
 * @param  array $content The existing metabox tokens
 * @return array          The metabox tokens with Twitter added
 */
function ppp_tw_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( !isset( $ppp_social_settings['twitter'] ) ) {
		return $content;
	}

	$content[] = 'tw';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_tw_register_metabox_content', 10, 1 );

/**
 * Returns the stored Twitter data for a post
 *
 * @since  2.3
 * @param  array $post_meta Array of meta data (empty)
 * @param  int   $post_id   The Post ID to get the meta for
 * @return array            The stored Twitter shares for a post
 */
function ppp_tw_get_post_meta( $post_meta, $post_id ) {
	return get_post_meta( $post_id, '_ppp_tweets', true );
}
add_filter( 'ppp_get_scheduled_items_tw', 'ppp_tw_get_post_meta', 10, 2 );

/**
 * Registers the thumbnail size for Twitter
 * @return void
 */
function ppp_tw_register_thumbnail_size() {
	add_image_size( 'ppp-tw-share-image', 1024, 512, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_tw_register_thumbnail_size' );

/**
 * The callback that adds Twitter metabox content
 * @param  object $post The post object
 * @return void         Displays the metabox content
 */
function ppp_tw_add_metabox_content( $post ) {
	global $ppp_options, $ppp_share_settings, $has_past_shares;
	$has_past_shares = 0;
	?>
		<p>
			<div class="ppp-post-override-wrap">
				<p><h3><?php _e( 'Share on Twitter', 'ppp-txt' ); ?></h3></p>
				<div id="ppp-tweet-fields" class="ppp-tweet-fields">
					<div id="ppp-tweet-fields" class="ppp-meta-table-wrap">
						<table class="widefat ppp-repeatable-table" width="100%" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th style="width: 100px"><?php _e( 'Date', 'ppp-txt' ); ?></th>
									<th style="width: 75px;"><?php _e( 'Time', 'ppp-txt' ); ?></th>
									<th><?php _e( 'Text', 'ppp-txt' ); ?></th>
									<th style"width: 200px;"><?php _e( 'Image', 'ppp-txt' ); ?></th>
									<th style="width: 30px;"></th>
								</tr>
							</thead>
							<tbody>
								<?php ppp_render_tweet_share_on_publish_row(); ?>
								<?php $tweets = get_post_meta( $post->ID, '_ppp_tweets', true ); ?>
								<?php if ( ! empty( $tweets ) ) : ?>

									<?php foreach ( $tweets as $key => $value ) :
										$date          = isset( $value['date'] )          ? $value['date']          : '';
										$time          = isset( $value['time'] )          ? $value['time']          : '';
										$text          = isset( $value['text'] )          ? $value['text']          : '';
										$image         = isset( $value['image'] )         ? $value['image']         : '';
										$attachment_id = isset( $value['attachment_id'] ) ? $value['attachment_id'] : '';

										$args = apply_filters( 'ppp_tweet_row_args', compact( 'date','time','text','image','attachment_id' ), $value );
										?>

										<?php ppp_render_tweet_row( $key, $args, $post->ID ); ?>


									<?php endforeach; ?>

									<?php
									if ( ! empty( $has_past_shares ) && count ( $tweets ) == $has_past_shares ) {
										$args = array(
											'date'          => '',
											'time'          => '',
											'text'          => '',
											'image'         => '',
											'attachment_id' => '',
										);


										ppp_render_tweet_row( $key + 1, $args, $post->ID );
									}
									?>

								<?php else: ?>

									<?php ppp_render_tweet_row( 1, array( 'date' => '', 'time' => '', 'text' => '', 'image' => '', 'attachment_id' => '' ), $post->ID, 1 ); ?>

								<?php endif; ?>

								<tr class="ppp-add-repeatable-wrapper">
									<td class="submit" colspan="4" style="float: none; clear:both; background:#fff;">
										<a class="button-secondary ppp-add-repeatable" style="margin: 6px 0;"><?php _e( 'Add New Tweet', 'ppp-txt' ); ?></a>
										<?php if ( ! empty( $has_past_shares ) ) : ?>
											<a class="button-secondary ppp-view-all" style="margin: 6px 0;"><?php _e( 'Toggle Past Tweets', 'ppp-txt' ); ?></a>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div><!--end #edd_variable_price_fields-->

				<p><?php _e( 'Do not include links in your text, this will be added automatically.', 'ppp-txt' ); ?></p>
				<p style="display: none;" id="ppp-show-conflict-warning"><?php printf( __( 'Items highlighted in red have a time assigned that is within %d minutes of an already scheduled Tweet', 'ppp-txt' ), floor( ppp_get_default_conflict_window() / 60 ) ); ?></p>
			</div>
		</p>
	<?php
}
add_action( 'ppp_generate_metabox_content-tw', 'ppp_tw_add_metabox_content', 10, 1 );

/**
 * Generates the 'share on publish row' of the Twitter Metabox content
 *
 * @since  2.3
 * @return void
 */
function ppp_render_tweet_share_on_publish_row() {
	global $post, $ppp_share_settings;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_post_exclude = get_post_meta( $post->ID, '_ppp_post_exclude', true );

	$ppp_share_on_publish  = get_post_meta( $post->ID, '_ppp_share_on_publish', true );
	$show_share_on_publish = false;

	$share_by_default      = empty( $ppp_share_settings['twitter']['share_on_publish'] ) ? false : true;

	if ( $ppp_share_on_publish == '1' || ( $ppp_share_on_publish == '' && $share_by_default ) ) {
		$show_share_on_publish = true;
	}

	$ppp_share_on_publish_text          = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );
	$ppp_share_on_publish_include_image = get_post_meta( $post->ID, '_ppp_share_on_publish_include_image', true );

	$disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false;
	?>
	<tr class="ppp-tweet-wrapper ppp-repeatable-row on-publish-row">
		<td colspan="2" class="ppp-on-plublish-date-column">
			<input <?php if ( $disabled ): ?>readonly<?php endif; ?> type="checkbox" name="_ppp_share_on_publish" id="ppp_share_on_publish" value="1" <?php checked( true, $show_share_on_publish, true ); ?> />
			&nbsp;<label for="ppp_share_on_publish"><?php _e( 'Tweet On Publish', 'ppp-txt' ); ?></label>
		</td>

		<td>
			<textarea <?php if ( $disabled ): ?>readonly<?php endif; ?> class="ppp-tweet-text-repeatable" type="text" name="_ppp_share_on_publish_text"><?php echo esc_attr( $ppp_share_on_publish_text ); ?></textarea>
			<?php $length = ! empty( $ppp_share_on_publish_text ) ? strlen( $ppp_share_on_publish_text ) : 0; ?>
			&nbsp;<span class="ppp-text-length"><?php echo $length; ?></span>
		</td>

		<td style="width: 200px" colspan="2">
			<input class="ppp-tw-featured-image-input" <?php if ( $disabled ): ?>readonly<?php endif; ?> id="ppp-share-on-publish-image" type="checkbox" name="_ppp_share_on_publish_include_image" value="1" <?php checked( '1', $ppp_share_on_publish_include_image, true ); ?>/>
			&nbsp;<label for="ppp-share-on-publish-image"><?php _e( 'Featured Image', 'ppp-txt' ); ?></label>
		</td>

	</tr>
<?php
}

/**
 * Generates the row for a scheduled Tweet in the metabox
 *
 * @param  int    $key     The array index
 * @param  array  $args    Arguements/Data for the specific index
 * @param  int    $post_id The post ID
 * @return void
 */
function ppp_render_tweet_row( $key, $args = array(), $post_id ) {
	global $post, $has_past_shares;

	if ( ! empty( $args['date'] ) && ! empty( $args['time'] ) ) {
		$share_time = ppp_generate_timestamp( $args['date'], $args['time'] );
		$readonly   = ppp_generate_timestamp() > $share_time ? 'readonly="readonly" ' : false;
	} else {
		$share_time = false;
		$readonly   = false;
	}

	$no_date        = ! empty( $readonly ) ? ' hasDatepicker' : '';
	$hide           = ! empty( $readonly ) ? 'display: none;' : '';
	$shared         = ! empty( $readonly ) ? 'past-share' : '';

	if ( ! empty( $readonly ) ) {
		$has_past_shares++;
	}
	?>
	<tr class="ppp-tweet-wrapper ppp-repeatable-row ppp-repeatable-twitter scheduled-row <?php echo $shared; ?>" data-key="<?php echo esc_attr( $key ); ?>">
		<td>
			<input <?php echo $readonly; ?>type="text" class="share-date-selector<?php echo $no_date; ?>" name="_ppp_tweets[<?php echo $key; ?>][date]" placeholder="mm/dd/yyyy" value="<?php echo $args['date']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>type="text" class="share-time-selector" name="_ppp_tweets[<?php echo $key; ?>][time]" value="<?php echo $args['time']; ?>" />
		</td>

		<td>
			<textarea class="ppp-tweet-text-repeatable" type="text" name="_ppp_tweets[<?php echo $key; ?>][text]" <?php echo $readonly; ?>><?php echo esc_attr( $args['text'] ); ?></textarea>
			<?php $length = ! empty( $args['text'] ) ? strlen( $args['text'] ) : 0; ?>
			&nbsp;<span class="ppp-text-length"><?php echo $length; ?></span>
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_tweets[<?php echo $key; ?>][attachment_id]" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_tweets[<?php echo $key; ?>][image]" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $args['image'] ); ?>" />

				<span class="ppp-upload-file" style="<?php echo $hide; ?>">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>

		<td class="ppp-action-icons">
			<a title="<?php _e( 'Duplicate', 'ppp-txt' ); ?>" href="#" class="ppp-clone-tweet"><i class="fa fa-repeat" aria-hidden="true"></i></a>
			&nbsp;<a title="<?php _e( 'Delete', 'ppp-txt' ); ?>" href="#" class="ppp-repeatable-row ppp-remove-repeatable" data-type="twitter" style="<?php echo $hide; ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
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
function ppp_tw_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_post_exclude = ( isset( $_REQUEST['_ppp_post_exclude'] ) ) ? $_REQUEST['_ppp_post_exclude'] : '0';

	$ppp_share_on_publish = ( isset( $_REQUEST['_ppp_share_on_publish'] ) ) ? $_REQUEST['_ppp_share_on_publish'] : '0';
	$ppp_share_on_publish_text = ( isset( $_REQUEST['_ppp_share_on_publish_text'] ) ) ? $_REQUEST['_ppp_share_on_publish_text'] : '';
	$ppp_share_on_publish_include_image = ( isset( $_REQUEST['_ppp_share_on_publish_include_image'] ) ) ? $_REQUEST['_ppp_share_on_publish_include_image'] : '';


	update_post_meta( $post_id, '_ppp_share_on_publish', $ppp_share_on_publish );
	update_post_meta( $post_id, '_ppp_share_on_publish_text', $ppp_share_on_publish_text );
	update_post_meta( $post_id, '_ppp_share_on_publish_include_image', $ppp_share_on_publish_include_image );

	$tweet_data = isset( $_REQUEST['_ppp_tweets'] ) ? $_REQUEST['_ppp_tweets'] : array();
	foreach ( $tweet_data as $index => $tweet ) {
		$tweet_data[ $index ]['text'] = sanitize_text_field( $tweet['text'] );
	}
	update_post_meta( $post_id, '_ppp_tweets', $tweet_data );

}
add_action( 'save_post', 'ppp_tw_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Determines if the post should be shared on publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post Object
 * @return void               Shares the post
 */
function ppp_tw_share_on_publish( $new_status, $old_status, $post ) {
	global $ppp_options;

	$from_meta = ! empty( $_POST['ppp_post_edit'] ) ? false : get_post_meta( $post->ID, '_ppp_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_share_on_publish'] );

	if ( empty( $from_meta ) && empty( $from_post ) ) {
		return;
	}

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && !$from_post ) {
		$ppp_share_on_publish_text = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );
		$use_media = get_post_meta( $post->ID, '_ppp_share_on_publish_include_image', true );
	} else {
		$ppp_share_on_publish_text = isset( $_POST['_ppp_share_on_publish_text'] ) ? $_POST['_ppp_share_on_publish_text'] : '';
		$use_media = isset( $_POST['_ppp_share_on_publish_include_image'] ) ? $_POST['_ppp_share_on_publish_include_image'] : false;
	}

	$share_content = ( !empty( $ppp_share_on_publish_text ) ) ? $ppp_share_on_publish_text : ppp_tw_generate_share_content( $post->ID, null, false );
	$share_content = apply_filters( 'ppp_share_content', $share_content, array( 'post_id' => $post->ID ) );
	$name = 'sharedate_0_' . $post->ID;
	$media = ppp_post_has_media( $post->ID, 'tw', $use_media );
	$share_link = ppp_generate_link( $post->ID, $name, true );

	$status = ppp_send_tweet( $share_content . ' ' . $share_link, $post->ID, $media );

	$log_title = ppp_tw_build_share_message( $post->ID, $name, false, false );

	$log_data = array(
		'post_title'    => $log_title,
		'post_content'  =>  json_encode( $status ),
		'post_parent'   => $post->ID,
		'log_type'      => 'ppp_share'
	);

	$log_meta = array(
		'network'   => 'tw',
		'share_id'  => 0,
	);

	$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

	if ( ! empty( $status->id_str ) ) {
		$author_id = $post->post_author;
		$author_rt = get_user_meta( $author_id, '_ppp_share_on_publish', true );

		if ( $author_rt ) {
			$twitter_user = new PPP_Twitter_User( $author_id );
			$twitter_user->retweet( $status->id_str );
		}

		/* Get an array of users who should retweet if another author has a post published
		 * Exclude the author that shared earlier to avoid duplicate retweets.
		 */
		$args = array(
			'meta_query' => array(
				array(
					'key' 		=> '_ppp_share_others_on_publish',
					'value'		=> true,
					'compare'	=> '='
				),
			),
			'fields'	=> array( 'ID' ),
			'exclude'	=> array( $author_id )
		);
		$other_rt = get_users( $args );

		if ( $other_rt ){
			foreach ( $other_rt as $user ) {
				$twitter_user = new PPP_Twitter_User( $user->ID );
				$twitter_user->retweet( $status->id_str );
			}
		}

	}
}
add_action( 'ppp_share_on_publish', 'ppp_tw_share_on_publish', 10, 3 );

/**
 * Generate the timestamps and names for the scheduled Twitter shares
 *
 * @since  2.3
 * @param  array $times   The times to save
 * @param  int   $post_id The Post ID of the item being saved
 * @return array          Array of timestamps and cron names
 */
function ppp_tw_generate_timestamps( $times, $post_id ) {
	$ppp_tweets = get_post_meta( $post_id, '_ppp_tweets', true );

	if ( empty( $ppp_tweets ) ) {
		$ppp_tweets = array();
	}

	foreach ( $ppp_tweets as $key => $data ) {
		if ( ! array_filter( $data ) ) {
			continue;
		}

		$timestamp = ppp_generate_timestamp( $data['date'], $data['time'] );

		if ( $timestamp > current_time( 'timestamp', 1 ) ) { // Make sure the timestamp we're getting is in the future
			$time_key           = strtotime( date_i18n( 'd-m-Y H:i:s', $timestamp , true ) ) . '_tw';
			$times[ $time_key ] = 'sharedate_' . $key . '_' . $post_id . '_tw';
		}

	}

	return $times;
}
add_filter( 'ppp_get_timestamps', 'ppp_tw_generate_timestamps', 10, 2 );

/**
 * Returns if the Twitter Cards are enabled
 *
 * @since  2.2
 * @return bool If the user has checked to enable Twitter cards
 */
function ppp_tw_cards_enabled() {
	global $ppp_share_settings;

	$ret = false;

	if ( ! empty( $ppp_share_settings['twitter']['cards_enabled'] ) ) {
		$ret = true;
	}

	return apply_filters( 'ppp_tw_cards_enabled', $ret );
}

/**
 * Output the Twitter Card Meta
 *
 * @since  2.2
 * @return void
 */
function ppp_tw_card_meta() {

	if ( ! is_single() || ! ppp_twitter_enabled() || ! ppp_tw_cards_enabled() ) {
		return;
	}

	global $post, $ppp_options;

	if ( ! array_key_exists( $post->post_type, $ppp_options['post_types'] ) ) {
		return;
	}

	echo ppp_tw_get_cards_meta();
}
add_action( 'wp_head', 'ppp_tw_card_meta', 10 );

/**
 * Generates the Twitter Card Content
 *
 * @since  2.2
 * @return string The Twitter card Meta tags
 */
function ppp_tw_get_cards_meta() {

	$return = '';

	if ( ! is_single() || ! ppp_tw_cards_enabled() ) {
		return $return;
	}

	global $post, $ppp_social_settings;


	if ( empty( $post ) ) {
		return;
	}

	$elements = ppp_tw_default_meta_elements();
	foreach ( $elements as $name => $content ) {
		$return .= '<meta name="' . $name . '" content="' . $content . '" />' . "\n";
	}

	return apply_filters( 'ppp_tw_card_meta', $return );

}

/**
 * Sets an array of names and content for Twitter Card Meta
 * for easy filtering by devs
 *
 * @since  2.2
 * @return array The array of keys and values for the Twitter Meta
 */
function ppp_tw_default_meta_elements() {
	global $post, $ppp_social_settings;

	$elements = array();

	$image_url = ppp_post_has_media( $post->ID, 'tw', true );
	if ( $image_url ) {
		$elements['twitter:card']      = 'summary_large_image';
		$elements['twitter:image:src'] = $image_url;
	} else {
		$elements['twitter:card'] = 'summary';
	}

	$elements['twitter:site']        = '@' . $ppp_social_settings['twitter']['user']->screen_name;
	$elements['twitter:title']       = esc_attr( strip_tags( $post->post_title ) );
	$elements['twitter:description'] = esc_attr( ppp_tw_get_card_description() );

	$author_twitter_handle = get_user_meta( $post->post_author, 'twitter', true );
	if ( ! empty( $author_twitter_handle ) ) {

		if ( strpos( $author_twitter_handle, '@' ) === false ) {
			$author_twitter_handle = '@' . $author_twitter_handle;
		}

		$elements['twitter:creator'] = esc_attr( strip_tags( $author_twitter_handle ) );

	}

	return apply_filters( 'ppp_tw_card_elements', $elements );
}

/**
 * Given a post, will give the post excerpt or the truncated post content to fit in a Twitter Card
 *
 * @since  2.2
 * @return string The post excerpt/description
 */
function ppp_tw_get_card_description() {
	global $post;

	if ( ! is_single() || empty( $post ) ) {
		return false;
	}

	$excerpt = $post->post_excerpt;

	if ( empty( $excerpt ) ) {
		$excerpt = ppp_tw_format_card_description( $post->post_content );
	}

	return apply_filters( 'ppp_tw_card_desc', $excerpt );
}

/**
 * Format a given string for the excerpt
 *
 * @since  2.3
 * @param  string $excerpt The string to possibly truncate
 * @return string          The description, truncated if over 200 characters
 */
function ppp_tw_format_card_description( $excerpt ) {
	$max_len = apply_filters( 'ppp_tw_cart_desc_length', 200 );
	$excerpt = strip_tags( $excerpt );

	if ( strlen( $excerpt ) > $max_len ) {
		$excerpt_pre = substr( $excerpt, 0, $max_len );
		$last_space  = strrpos( $excerpt_pre, ' ' );
		$excerpt     = substr( $excerpt_pre, 0, $last_space ) . '...';
	}

	return $excerpt;
}

/**
 * Add a Twitter method to the profile editor
 *
 * @since  2.2.4
 * @param  array $user_contactmethods List of user contact methods for the profile editor
 * @return array                      List of contact methods with twitter added
 */
function ppp_tw_add_contact_method( $user_contactmethods ) {

	if ( ! isset( $user_contactmethods['twitter'] ) ) {
		$user_contactmethods['twitter'] = __( 'Twitter', 'ppp-txt' );
	}
	// Returns the contact methods
	return $user_contactmethods;
}
add_filter( 'user_contactmethods', 'ppp_tw_add_contact_method' );


/**
 * Adds in the Post Promoter Pro Preferences Profile Section
 *
 * @since  2.2.7
 * @param  object $user The User object being viewed
 * @return void         Displays HTML
 */
function ppp_tw_profile_settings( $user ) {
	global $ppp_social_settings;

	if ( $user->ID == get_current_user_id() && ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( $user->ID !== get_current_user_id() && ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
		return;
	}

	if ( ! isset( $ppp_social_settings['twitter'] ) || is_null( $ppp_social_settings['twitter'] ) ) {
		return;
	}

	$connected = false;
	?>
	<h3><?php _e( 'Post Promoter Pro', 'ppp-txt' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><?php _e( 'Connect to Twitter', 'ppp-txt' ); ?></th>
			<td>
			<?php
			$twitter = new PPP_Twitter_User( get_current_user_id() );
			$tw_user = get_user_meta( $user->ID, '_ppp_twitter_data', true );
			if ( empty( $tw_user ) ) {
				$tw_authurl = $twitter->get_auth_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) );

				echo '<a href="' . $tw_authurl . '"><img src="' . PPP_URL . '/includes/images/sign-in-with-twitter-gray.png" /></a>';
			} else {
				$connected = true;
				?>
				<p><strong><?php _e( 'Signed in as', 'ppp-txt' ); ?>: </strong><?php echo $tw_user['user']->screen_name; ?></p>
				<p>
					<a class="button-primary" href="<?php echo admin_url( 'user-edit.php?user_id=' . $user->ID . '&ppp_social_disconnect=true&ppp_network=twitter&user_id=' . $user->ID ); ?>" ><?php _e( 'Disconnect from Twitter', 'ppp-txt' ); ?></a>&nbsp;
					<a class="button-secondary" href="https://twitter.com/settings/applications" target="blank"><?php _e( 'Revoke Access via Twitter', 'ppp-txt' ); ?></a>
				</p>
				<?php
			}
			?>
			</td>
		</tr>

		<?php if ( $connected ) : ?>
		<?php
			$share_on_publish 			= get_user_meta( $user->ID, '_ppp_share_on_publish', true );
			$share_scheduled  			= get_user_meta( $user->ID, '_ppp_share_scheduled' , true );
			$share_others_on_publish 	= get_user_meta( $user->ID, '_ppp_share_others_on_publish', true );
			$share_others_scheduled  	= get_user_meta( $user->ID, '_ppp_share_others_scheduled' , true );
		?>
		<tr>
			<th><?php _e( 'Sharing Options', 'ppp-txt' ); ?></th>
			<td>
				<input type="checkbox" <?php checked( true, $share_on_publish, true ); ?>name="share_on_publish" value="1" id="share-on-publish" /> <label for="share-on-publish"><?php _e( 'Retweet my posts when they are published', 'ppp-txt' ); ?></label>
				<p class="description"><?php printf( __( 'Retweet the primary account as %s when it Tweets on publishing my posts.', 'ppp-txt' ), $tw_user['user']->screen_name ); ?></p>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" <?php checked( true, $share_scheduled, true ); ?> name="share_scheduled" value="1" id="share-scheduled" /> <label for="share-scheduled"><?php _e( 'Retweet scheduled shares of my posts', 'ppp-txt' ); ?></label>
				<p class="description"><?php printf( __( 'When the primary account schedules a Tweet for one of my posts, Retweet it as %s.', 'ppp-txt' ), $tw_user['user']->screen_name ); ?></p>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" <?php checked( true, $share_others_on_publish, true ); ?> name="share_others_on_publish" value="1" id="share-others-on-publish" /> <label for="share-others-on-publish"><?php _e( 'Retweet other author\'s posts when they are published', 'ppp-txt' ); ?></label>
				<p class="description"><?php printf( __( 'Retweet the primary account as %s when it Tweets on publishing another author\'s posts.', 'ppp-txt' ), $tw_user['user']->screen_name ); ?></p>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" <?php checked( true, $share_others_scheduled, true ); ?> name="share_others_scheduled" value="1" id="share-others-scheduled" /> <label for="share-others-scheduled"><?php _e( 'Retweet scheduled shares of other author\'s posts', 'ppp-txt' ); ?></label>
				<p class="description"><?php printf( __( 'When the primary account schedules a Tweet for another author\'s post, Retweet it as %s.', 'ppp-txt' ), $tw_user['user']->screen_name ); ?></p>
			</td>
		</tr>

		<?php endif; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'ppp_tw_profile_settings' );
add_action( 'edit_user_profile', 'ppp_tw_profile_settings' );

/**
 * Saves the User Profile Settings
 *
 * @since  2.2.7
 * @param  int $user_id The User ID being saved
 * @return void         Saves to Usermeta
 */
function ppp_tw_save_profile( $user_id ) {
	global $ppp_social_settings;

	if ( ! isset( $ppp_social_settings['twitter'] ) || is_null( $ppp_social_settings['twitter'] ) ) {
		return;
	}

	$share_on_publish 			= ! empty( $_POST['share_on_publish'] ) ? true : false;
	$share_scheduled  			= ! empty( $_POST['share_scheduled'] )  ? true : false;
	$share_others_on_publish 	= ! empty( $_POST['share_others_on_publish'] ) ? true : false;
	$share_others_scheduled  	= ! empty( $_POST['share_others_scheduled'] )  ? true : false;

	update_user_meta( $user_id, '_ppp_share_on_publish', $share_on_publish );
	update_user_meta( $user_id, '_ppp_share_scheduled', $share_scheduled  );
	update_user_meta( $user_id, '_ppp_share_others_on_publish', $share_others_on_publish );
	update_user_meta( $user_id, '_ppp_share_others_scheduled', $share_others_scheduled  );

}
add_action( 'personal_options_update', 'ppp_tw_save_profile' );
add_action( 'edit_user_profile_update', 'ppp_tw_save_profile' );

function ppp_tw_calendar_on_publish_event( $events, $post_id ) {
	$share_on_publish = get_post_meta( $post_id, '_ppp_share_on_publish', true );

	if ( ! empty( $share_on_publish ) ) {
		$share_text = get_post_meta( $post_id, '_ppp_share_on_publish_text', true );
		$events[] = array(
			'id' => $post_id . '-share-on-publish',
			'title' => ( ! empty( $share_text ) ) ? $share_text : ppp_tw_generate_share_content( $post_id, null, false ),
			'start'     => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'end'       => date_i18n( 'Y-m-d/TH:i:s', strtotime( get_the_date( null, $post_id ) . ' ' . get_the_time( null, $post_id ) ) + 1 ),
			'className' => 'ppp-calendar-item-tw cal-post-' . $post_id,
			'belongsTo' => $post_id,
		);
	}

	return $events;
}
add_filter( 'ppp_calendar_on_publish_event', 'ppp_tw_calendar_on_publish_event', 10, 2 );

function ppp_tw_get_post_shares( $items, $post_id ) {
	$tweets = get_post_meta( $post_id, '_ppp_tweets', true );
	if ( empty( $tweets ) ) { return $items; }

	foreach ( $tweets as $key => $tweet ) {
		$items[] = array( 'id' => $key, 'service' => 'tw' );
	}
	return $items;
}
add_filter( 'ppp_get_post_scheduled_shares', 'ppp_tw_get_post_shares', 10, 2 );
