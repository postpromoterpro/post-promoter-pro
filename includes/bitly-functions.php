<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return if bitly account is found
 * @return bool If the Bitly object exists
 */
function ppp_bitly_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['bitly'] ) && !empty( $ppp_social_settings['bitly'] ) ) {
		return true;
	}

	return false;
}

/**
 * Adds the Bit.ly Shortener to the list of available shorteners
 * @param  string $selected_shortener The currently selected url shortener
 * @return void
 */
function ppp_add_bitly_shortener( $selected_shortener ) {
	?><option value="bitly" <?php selected( $selected_shortener, 'bitly', true ); ?>>Bit.ly</option><?php
}
add_action( 'ppp_url_shorteners', 'ppp_add_bitly_shortener', 10, 1 );

/**
 * Displays the bitly settings area when bitly is selected as the URL shortener
 * @return void
 */
function ppp_display_bitly_settings() {
	global $ppp_bitly_oauth, $ppp_social_settings;
	?>
	<p>
		<?php if ( !ppp_bitly_enabled() ) : ?>
		<form autocomplete="off">
			<input id="bitly-username" name="ppp-bitly-username" autocomplete="off" type="text" value="" placeholder="Bit.ly Username" size="25" /><br />
			<input id="bitly-password" name="ppp-bitly-password" autocomplete="off" type="password" value="" placeholder="Bit.ly Password" size="25" /><br />
			<span id="ppp-bitly-invalid-login" style="color: #993333; display: none;"><?php _e( 'Invalid Login or Password', 'ppp-txt' ); ?></span><br />
			<div id="ppp-bitly-login-form-submit">
				<a href="#" id="bitly-login" class="button-primary">Connect To Bit.ly</a><span class="spinner"></span>
			</div>
			<input type="hidden" id="bitly-redirect-url" value="<?php echo admin_url( 'admin.php?page=ppp-social-settings' ); ?>" />
		</form>
		<?php endif; ?>
		<?php if ( ppp_bitly_enabled() ) : ?>
			<div class="ppp-social-profile ppp-bitly-profile">
				<div class="ppp-bitly-info">
					<?php _e( 'Signed in as', 'ppp-txt' ); ?>:<br /><?php echo $ppp_social_settings['bitly']['login']; ?><br />
					<?php _e( 'Access Token: ', 'ppp-txt' ); ?><code><?php echo $ppp_social_settings['bitly']['access_token']; ?></code>
				</div>
			</div>
			<p>
				<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=bitly' ); ?>" ><?php _e( 'Disconnect from Bit.ly', 'ppp-txt' ); ?></a>
			</p>
		<?php endif; ?>
	</p>
	<?php
}
add_action( 'ppp_shortener_settings-bitly', 'ppp_display_bitly_settings', 10 );

/**
 * The steps to run when clicking to deactivate bit.ly
 * @return void
 */
function ppp_disconnect_bitly() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['bitly'] ) ) {
		unset( $ppp_social_settings['bitly'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
	}
}
add_action( 'ppp_disconnect-bitly', 'ppp_disconnect_bitly', 10 );

function ppp_set_bitly_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'bitly' ) ) {
		define( 'bitly_clientid', $social_tokens->bitly->client_id );
		define( 'bitly_secret', $social_tokens->bitly->client_secret );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_bitly_token_constants', 10, 1 );

/**
 * Convert a link to bitly
 * @param  string $link The link, before shortening
 * @return string       The link, after being sent to bitly, if successful
 */
function ppp_apply_bitly( $link ) {
	global $ppp_bitly_oauth;

	$result = $ppp_bitly_oauth->ppp_make_bitly_link( $link );
	$result = json_decode( $result, true );

	if ( isset ( $result['status_code'] ) && $result['status_code'] == 200 ) {
		return $result['data']['url'];
	} else {
		return $link;
	}
}
add_filter( 'ppp_apply_shortener-bitly', 'ppp_apply_bitly', 10, 1 );

/**
 * Auth a user with bit.ly
 * @return mixed 1 if successful, 0 if general error, 'INVALID_LOGIN' if failed creds
 */
function ppp_get_bitly_auth() {
	global $ppp_bitly_oauth;
	ppp_set_social_tokens();
	$url = 'https://api-ssl.bitly.com/oauth/access_token';
	$username = $_POST['username'];
	$password = $_POST['password'];
	$headers = array( 'Authorization' => 'Basic ' . base64_encode( bitly_clientid . ':' . bitly_secret ) );
	$body = 'grant_type=password&username=' . $username . '&password=' . $password;
	$result = wp_remote_post( $url, array( 'headers' => $headers, 'body' => $body ) );
	$body = wp_remote_retrieve_body( $result );
	$data = json_decode( $body );

	if ( isset( $data->access_token ) ) {
		global $ppp_social_settings;
		$ppp_social_settings['bitly']['access_token'] = $data->access_token;

		$user_data = json_decode( $ppp_bitly_oauth->ppp_bitly_user_info(), true );

		$ppp_social_settings['bitly']['login'] = $user_data['data']['login'];

		update_option( 'ppp_social_settings', $ppp_social_settings );
		echo 1;
	} elseif ( $body == 'INVALID_LOGIN' ) {
		echo 'INVALID_LOGIN';
	} else {
		echo 0;
	}

	die(); // this is required to return a proper result
}
add_action( 'wp_ajax_ppp_bitly_connect', 'ppp_get_bitly_auth' );
