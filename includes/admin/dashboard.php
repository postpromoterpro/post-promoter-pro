<?php
/**
 * Add a widget to the dashboard.
 *
 * @since  2.2.3
 */
class PPP_Dashboard_Shares {

	/**
	 * The ID of this widget.
	 */
	const wid = 'ppp_dashboard_shares';

	/**
	 * Hook to wp_dashboard_setup to add the widget.
	 *
	 * @since  2.2.3
	 */
	public static function init() {
		if ( ! current_user_can( PostPromoterPro::get_manage_capability() ) ) {
			return;
		}

		wp_add_dashboard_widget(
			self::wid,
			__( 'Upcoming Shares', 'ppp-tweets' ),
			array( 'PPP_Dashboard_Shares', 'widget' ),
			array( 'PPP_Dashboard_Shares', 'config' )
		);
	}

	/**
	 * Load the widget code
	 *
	 * @since  2.2.3
	 */
	public static function widget() {
		$number = self::get_count();
		$shares = ppp_get_shceduled_crons();

		if ( ! empty( $shares ) ) {
			$limited_shares = array_slice( $shares, 0, $number, true );
			?>
			<div id="future-tweets" class="activity-block">
				<h4><?php _e( 'Post-Related shares', 'ppp-tweets' ); ?></h4>
				<ul>
				<?php
				foreach ( $limited_shares as $key => $share ) {
					$ppp_data = $share;
					$timestamp = $ppp_data['timestamp'];

					$name_parts = explode( '_', $ppp_data['args'][1] );
					$service    = isset( $name_parts[3] ) ? $name_parts[3] : 'tw';
					$builder    = 'ppp_' . $service . '_build_share_message';

					$post_id    = $ppp_data['args'][0];
					$date       = $timestamp + ( get_option( 'gmt_offset' ) * 3600 );
					$content    = '';
					if ( function_exists( $builder ) ) {
						$content    = $builder( $ppp_data['args'][0], $ppp_data['args'][1], false );
					}

					$regex   = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@";
					$content = preg_replace( $regex, '', $content );
					?>
					<li>
						<span class="meta"><i class="dashicons icon-ppp-<?php echo $service; ?>"></i>&nbsp;<?php echo date_i18n( 'M jS, ' . get_option( 'time_format' ), $date ); ?></span>
						<a href="<?php echo admin_url( 'post.php?post=' . $post_id . '&action=edit' ); ?>"><?php echo $content; ?></a>
					</li>
				<?php } ?>
				</ul>
				<p>
					<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=ppp-schedule-info' ); ?>"><?php _e( 'View Full Schedule', 'ppp-txt' ); ?></a>
				</p>
			</div>
			<?php
		} else {
			$args = array(
				'numberposts' => 1,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => ppp_allowed_post_types(),
				'post_status' => array( 'draft', 'publish', 'future' ),
			);

			$recent_posts   = wp_get_recent_posts( $args, ARRAY_A );
			$recent_post    = $recent_posts[0];
			$post_type      = get_post_type_object( $recent_post['post_type'] );
			$post_type_name = $post_type->labels->singular_name;
			$edit_url       = admin_url( 'post.php?post=' . $recent_post['ID'] . '&action=edit' );

			switch( $recent_post['post_status'] ) {
				case 'draft':
					$relative_time = __( '<a href="%s">Configure them</a> for your draft ' . $post_type_name, 'ppp-txt' );
					break;
				case 'future':
					$relative_time = __( '<a href="%s">Schedule one</a> for your upcoming ' . $post_type_name, 'ppp-txt' );
					break;
				case 'publish':
				default:
					$relative_time = __( '<a href="%s">Schedule one</a> for your most recent ' . $post_type_name, 'ppp-txt' );
					break;

			}
			?><span><em>
				<?php _e( 'No scheduled shares at this time.', 'ppp-txt' ); ?>
				<?php printf( $relative_time, $edit_url ); ?>
			</em></span><?php
		}

		do_action( 'ppp_dashboard_shares' );
	}

	/**
	 * Load widget config code.
	 *
	 * This is what will display when an admin clicks
	 *
	 * @since  2.2.3
	 */
	public static function config() {
		if ( ! empty( $_POST['number_of_tweets'] ) ) {
			update_option( 'ppp_dashboard_twitter_count', absint( $_POST['number_of_tweets'] ) );
		}

		$number = self::get_count();
		?>
		<p><input type="number" size="3" min="1" max="99" step="1" value="<?php echo $number; ?>" id="ppp-number-of-tweets" name="number_of_tweets" />&nbsp;<label for="ppp-number-of-tweets"><?php _e( 'Number of Tweets to Show.', 'ppp-txt' ); ?></label></p>
	<?php

	}

	/**
	 * Gets the count of tweets to show
	 *
	 * @since  2.2.3
	 * @return int The Number of tweets to show
	 */
	private static function get_count() {
		$stored_count = get_option( 'ppp_dashboard_shares_count' );

		$stored_count = empty( $stored_count ) || ! is_numeric( $stored_count ) ? 5 : absint( $stored_count );

		return ! empty( $stored_count ) ? $stored_count : 5;
	}

}
add_action('wp_dashboard_setup', array('PPP_Dashboard_Shares','init') );
