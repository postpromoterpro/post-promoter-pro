<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the General settings tab
 * @return void
 */
function ppp_admin_page() {
	global $ppp_options;
	$license    = get_option( '_ppp_license_key' );
	$status     = get_option( '_ppp_license_key_status' );

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div><h1><?php _e( 'Post Promoter Pro', 'ppp-txt' ); ?></h1>
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'ppp-options' ); ?>
			<table class="form-table">

				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'License Key', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'Enter your license key', 'ppp-txt' ); ?></span>
					</th>
					<td>
						<input id="ppp_license_key" name="_ppp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" /><?php if( $status !== false && $status == 'valid' ) { ?>
						<span style="color:green;">&nbsp;<?php _e( 'active', 'ppp-txt' ); ?></span><?php } ?>
					</td>
				</tr>

				<?php if( false !== $license ) { ?>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e( 'Activate License', 'ppp-txt' ); ?>
						</th>
						<td>
							<?php if( $status !== false && $status == 'valid' ) { ?>
								<?php wp_nonce_field( 'ppp_deactivate_nonce', 'ppp_deactivate_nonce' ); ?>
								<input type="submit" class="button-secondary" name="ppp_license_deactivate" value="<?php _e( 'Deactivate License', 'ppp-txt' ); ?>"/>
							<?php } else {
								wp_nonce_field( 'ppp_activate_nonce', 'ppp_activate_nonce' ); ?>
								<input type="submit" class="button-secondary" name="ppp_license_activate" value="<?php _e( 'Activate License', 'ppp-txt' ); ?>"/>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>

				<tr valign="top">
					<th scope="row"><?php _e( 'Default Share Text', 'ppp-txt' ); ?><br />
						<span style="font-size: x-small;"><a href="#" onclick="jQuery('#ppp-text-helper').toggle(); return false;"><?php _e( 'Default Text Tips', 'ppp-txt' ); ?></a></span>
					</th>
					<td>
						<?php $default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : ''; ?>
						<input type="text" class="regular-text" name="ppp_options[default_text]" value="<?php echo $default_text; ?>" placeholder="Post Title will be used if empty" size="50" />
						<p id="ppp-text-helper" style="display: none">
							<small>
							<?php _e( 'The typical length of a link shortened on Twitter is 23 characters, so keep that in mind when writing your default text.', 'ppp-txt' ); ?>
							<br />
							<?php _e( 'Status updates over 140 charcters will fail to post.', 'ppp-txt' ); ?>
							<br />
							<?php _e( 'Possible Replacements:', 'ppp-txt' ); ?>
							<br />
							<?php foreach( ppp_get_text_tokens() as $token ): ?>
								<code>{<?php echo $token['token']; ?>}</code> - <?php echo $token['description']; ?><br />
							<?php endforeach; ?>
							</small>
						</p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Post Types', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'What post types do you want to schedule for?', 'ppp-txt' ); ?></span></th>
					<td>
						<?php $post_types = get_post_types( array( 'public' => true, 'publicly_queryable' => true ), NULL, 'and' ); ?>
						<?php if ( array_key_exists( 'attachment', $post_types ) ) { unset( $post_types['attachment'] ); } ?>
						<?php foreach ( $post_types as $post_type => $type_data ): ?>
							<?php $value = ( isset( $ppp_options['post_types'] ) && isset( $ppp_options['post_types'][$post_type] ) ) ? true : false; ?>
							<input type="checkbox" name="ppp_options[post_types][<?php echo $post_type; ?>]" value="1" id="<?php echo $post_type; ?>" <?php checked( true, $value, true ); ?> />&nbsp;
							<label for="<?php echo $post_type; ?>"><?php echo $type_data->labels->name; ?></label></br />
						<?php endforeach; ?>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Advanced', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'Tools for troubleshooting and advanced usage', 'ppp-txt' ); ?></span></th>
					<td>
						<p>
						<?php $debug_enabled = isset( $ppp_options['enable_debug'] ) ? true : false; ?>
						<input type="checkbox" name="ppp_options[enable_debug]" <?php checked( true, $debug_enabled, true ); ?> value="1" /> <?php _e( 'Enable Debug', 'ppp-txt' ); ?>
						</p>
						<p>
						<?php $delete_on_uninstall = isset( $ppp_options['delete_on_uninstall'] ) ? true : false; ?>
						<input type="checkbox" name="ppp_options[delete_on_uninstall]" <?php checked( true, $delete_on_uninstall, true ); ?> value="1" /> <?php _e( 'Delete All Data On Uninstall', 'ppp-txt' ); ?>
						</p>
					</td>
				</tr>

				<?php do_action( 'ppp_general_settings_after' ); ?>

				<input type="hidden" name="action" value="update" />
				<?php $page_options = apply_filters( 'ppp_settings_page_options', array( 'ppp_options', '_ppp_license_key' ) ); ?>
				<input type="hidden" name="page_options" value="<?php echo implode( ',', $page_options ); ?>" />
				<?php settings_fields( 'ppp-options' ); ?>
			</table>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ppp-txt' ) ?>" />
		</form>
	</div>
	<?php
}


/**
* Display the Social tab
* @return void
*/
function ppp_display_social() {
	do_action( 'ppp_social_settings_pre_form' );

	$ppp_share_settings = get_option( 'ppp_share_settings' );
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div><h1><?php _e( 'Post Promoter Pro - Social Settings', 'ppp-txt' ); ?></h1>
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'ppp-share-settings' ); ?>
			<h3><?php _e( 'Social Media Accounts', 'ppp-txt' ); ?></h3>
			<?php
			require_once PPP_PATH . 'includes/admin/class-accounts-table.php';

			$accounts_table = new PPP_Accounts_Table();
			$accounts_table->prepare_items();

			$accounts_table->display();
			?>
			<table class="form-table">
				<?php $analytics_option = isset( $ppp_share_settings['analytics'] ) ? $ppp_share_settings['analytics'] : 0; ?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'Analytics', 'ppp-txt' ); ?></span>
					</th>
					<td id="ppp-analytics-options">
						<p>
							<input id="ppp_no_tracking"
							       name="ppp_share_settings[analytics]"
							       type="radio"
							       value="none"
							       <?php checked( 'none', $analytics_option, true ); ?>
							/>&nbsp;<label for="ppp_no_tracking"><?php _e( 'None', 'ppp-txt' ); ?></label>
						</p>
						<br />
						<p>
							<input id="ppp_unique_links"
							       name="ppp_share_settings[analytics]"
							       type="radio"
							       value="unique_links"
							       <?php checked( 'unique_links', $analytics_option, true ); ?>
							/>&nbsp;<label for="ppp_unique_links"><?php _e( 'Simple Tracking', 'ppp-txt' ); ?></label><br />
							<small><?php _e( 'Appends a query string to shared links for analytics.', 'ppp-txt' ); ?></small>
						</p>
						<br />
						<p>
							<input id="ppp_ga_tags"
							       name="ppp_share_settings[analytics]"
							       type="radio"
							       value="google_analytics"
							       <?php checked( 'google_analytics', $analytics_option, true ); ?>
							/>&nbsp;<label for="ppp_ga_tags"><?php _e( 'Google Analytics Tags', 'ppp-txt' ); ?></label><br />
							<small><?php _e( 'Results can be seen in the Acquisition Menu under "Campaigns"', 'ppp-txt' ); ?></small>
						</p>
						<?php do_action( 'ppp-settings-analytics-radio' ); ?>
						<p id="ppp-link-example">
						<hr />
						<small><?php _e( 'Here is an example of what your link will look like', 'ppp-txt' ); ?>: <br />
							<?php $post = wp_get_recent_posts( array( 'numberposts' => 1 ) ); ?>
							<?php if( count( $post ) > 0 ): ?>
								<code><?php echo ppp_generate_link( $post[0]['ID'], 'sharedate_1_' . $post[0]['ID'], false ); ?></code></small>
							<?php else: ?>
								<em><?php _e( 'No posts available to generate link from.', 'ppp-txt' ); ?></em>
							<?php endif; ?>
						</p>
					</td>
				</tr>

				<?php
				$tw_sop = ! empty( $ppp_share_settings['twitter']['share_on_publish'] ) ? true : false;
				$fb_sop = ! empty( $ppp_share_settings['facebook']['share_on_publish'] ) ? true : false;
				$li_sop = ! empty( $ppp_share_settings['linkedin']['share_on_publish'] ) ? true : false;
				?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'Share on Publish Defaults', 'ppp-txt' ); ?></span><br />
						<small><em><?php _e( 'Enabled sharing on pubish by default', 'ppp-txt' ); ?></em></small>
					</th>
					<td id="ppp-share-on-publish-wrapper">
						<?php if ( ppp_twitter_enabled() ) : ?>
						<p>
							<input type="checkbox" id="twitter-share-on-publish" value="1" <?php checked( true, $tw_sop, true ); ?> name="ppp_share_settings[twitter][share_on_publish]" />
							<label for="twitter-share-on-publish"><?php _e( 'Twitter', 'ppp-txt' ); ?></label>
						</p>
						<?php endif; ?>

						<?php if ( ppp_facebook_enabled() ) : ?>
						<p>
							<input type="checkbox" id="facebook-share-on-publish" value="1" <?php checked( true, $fb_sop, true ); ?> name="ppp_share_settings[facebook][share_on_publish]" />
							<label for="facebook-share-on-publish"><?php _e( 'Facebook', 'ppp-txt' ); ?></label>
						</p>
						<?php endif; ?>

						<?php if ( ppp_linkedin_enabled() ) : ?>
						<p>
							<input type="checkbox" id="linkedin-share-on-publish" value="1" <?php checked( true, $li_sop, true ); ?> name="ppp_share_settings[linkedin][share_on_publish]" />
							<label for="linkedin-share-on-publish"><?php _e( 'LinkedIn', 'ppp-txt' ); ?></label>
						</p>
						<?php endif; ?>
					</td>
				</tr>

				<?php $twitter_cards_enabled = ppp_tw_cards_enabled(); ?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'Twitter Settings', 'ppp-txt' ); ?></span>
					</th>
					<td id="ppp-twitter-cards-wrapper">
						<p>
							<input id="ppp-twitter-cards"
							       name="ppp_share_settings[twitter][cards_enabled]"
							       type="checkbox"
							       value="1"
							       <?php checked( true, $twitter_cards_enabled, true ); ?>
							/>&nbsp;<label for="ppp-twitter-cards"><?php _e( 'Enable Twitter Cards', 'ppp-txt' ); ?></label>
						</p>
					</td>
				</tr>

				<?php
				$shortener = isset( $ppp_share_settings['shortener'] ) ? $ppp_share_settings['shortener'] : false;
				?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'URL Shortener', 'ppp-txt' ); ?></span>
					</th>
					<td id="ppp-shortener-options">
						<p>
							<select name="ppp_share_settings[shortener]">
								<option value="-1"><?php _e( 'Select a Service', 'ppp-txt' ); ?></option>
								<?php do_action( 'ppp_url_shorteners', $shortener ); ?>
							</select>
						</p>
						<?php if ( $shortener ) : ?>
							<?php do_action( 'ppp_shortener_settings-' . $shortener ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<?php settings_fields( 'ppp-share-settings' ); ?>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="ppp_share_settings,ppp_social_settings" />


			</table>

			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ppp-txt' ) ?>" />

		</form>
	</div>
	<?php
}

/**
* Display the List Table tab
* @return void
*/
function ppp_display_schedule() {
	?>
	<style type="text/css">
		.wp-list-table .column-day { width: 5%; }
		.wp-list-table .column-date { width: 20%; }
		.wp-list-table .column-post_title { width: 25%; }
		.wp-list-table .column-content { width: 50%; }
	</style>
	<?php
	require_once PPP_PATH . 'includes/admin/class-schedule-table.php';
	$schedule_table = new PPP_Schedule_Table();
	$schedule_table->prepare_items();
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div><h1><?php _e( 'Post Promoter Pro - Scheduled Shares', 'ppp-txt' ); ?></h1>
		<?php $schedule_table->display() ?>
	</div>
	<?php if ( ppp_is_shortener_enabled() ): ?>
	<p>
		<small><?php _e( 'NOTICE: Schedule view does not show shortened links, they will be shortened at the time of sharing', 'ppp-txt' ); ?></small>
	</p>
	<?php endif; ?>
	<?php
}

/**
 * Display the System Info Tab
 * @return void
 */
function ppp_display_sysinfo() {
	global $wpdb;
	global $ppp_options;
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div><h1><?php _e( 'Post Promoter Pro - System Info', 'ppp-txt' ); ?></h1>
		<textarea style="font-family: Menlo, Monaco, monospace; white-space: pre" onclick="this.focus();this.select()" readonly cols="150" rows="35">
	SITE_URL:                 <?php echo site_url() . "\n"; ?>
	HOME_URL:                 <?php echo home_url() . "\n"; ?>

	PPP Version:             <?php echo PPP_VERSION . "\n"; ?>
	WordPress Version:        <?php echo get_bloginfo( 'version' ) . "\n"; ?>

	PPP SETTINGS:
	<?php
	foreach ( $ppp_options as $name => $value ) {
	if ( $value == false ) {
		$value = 'false';
	}

	if ( $value == '1' ) {
		$value = 'true';
	}

	echo $name . ': ' . maybe_serialize( $value ) . "\n";
	}
	?>

	ACTIVE PLUGINS:
	<?php
	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		// If the plugin isn't active, don't show it.
		if ( ! in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

	echo $plugin['Name']; ?>: <?php echo $plugin['Version'] ."\n";

	}
	?>

	CURRENT THEME:
	<?php
	if ( get_bloginfo( 'version' ) < '3.4' ) {
		$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
		echo $theme_data['Name'] . ': ' . $theme_data['Version'];
	} else {
		$theme_data = wp_get_theme();
		echo $theme_data->Name . ': ' . $theme_data->Version;
	}
	?>


	Multi-site:               <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

	ADVANCED INFO:
	PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
	MySQL Version:            <?php echo mysql_get_server_info() . "\n"; ?>
	Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

	PHP Memory Limit:         <?php echo ini_get( 'memory_limit' ) . "\n"; ?>
	PHP Post Max Size:        <?php echo ini_get( 'post_max_size' ) . "\n"; ?>
	PHP Time Limit:           <?php echo ini_get( 'max_execution_time' ) . "\n"; ?>

	WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

	WP Table Prefix:          <?php echo "Length: ". strlen( $wpdb->prefix ); echo " Status:"; if ( strlen( $wpdb->prefix )>16 ) {echo " ERROR: Too Long";} else {echo " Acceptable";} echo "\n"; ?>

	Show On Front:            <?php echo get_option( 'show_on_front' ) . "\n" ?>
	Page On Front:            <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>
	Page For Posts:           <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>

	Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
	Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
	Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
	Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
	Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
	Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>

	UPLOAD_MAX_FILESIZE:      <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'upload_max_filesize' ); ?><?php echo "\n"; ?>
	POST_MAX_SIZE:            <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'post_max_size' ); ?><?php echo "\n"; ?>
	WordPress Memory Limit:   <?php echo WP_MEMORY_LIMIT; ?><?php echo "\n"; ?>
	DISPLAY ERRORS:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
	FSOCKOPEN:                <?php echo ( function_exists( 'fsockopen' ) ) ? __( 'Your server supports fsockopen.', 'ppp-txt' ) : __( 'Your server does not support fsockopen.', 'ppp-txt' ); ?><?php echo "\n"; ?>
		</textarea>
	</div>
	<?php
}
