<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the metaboxes for Post Promoter Pro
 * @return void
 */
function ppp_register_meta_boxes() {
	global $post, $ppp_options;

	if ( !isset( $ppp_options['post_types'] ) || !is_array( $ppp_options['post_types'] ) ) {
		return;
	}

	foreach ( $ppp_options['post_types'] as $post_type => $value ) {
		add_meta_box( 'ppp_schedule_metabox', 'Post Promoter Pro', 'ppp_schedule_callback', $post_type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'ppp_register_meta_boxes', 12 );

/**
 * Display the Metabox for Post Promoter Pro
 * @return void
 */
function ppp_schedule_callback() {
	global $post, $ppp_options;
	?>
	<div id="ppp-tabs">
		<ul class="category-tabs">
			<?php do_action( 'ppp_metabox_tabs_display' ); ?>
		</ul>
		<?php do_action( 'ppp_metabox_content_display', $post ); ?>
	</div>
	<input type="hidden" name="ppp_post_edit" value="1" />
	<?php
}


