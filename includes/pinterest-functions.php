<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Pinterest metabox tab
 * @param  array $tabs The tabs
 * @return array       The tabs with Pinterest added
 */
function ppp_pt_add_meta_tab( $tabs ) {
	$tabs['pt'] = array( 'name' => __( 'Pinterest', 'ppp-txt' ), 'class' => 'icon-ppp-pt' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_pt_add_meta_tab', 10, 1 );

/**
 * Register the metabox content for Pinterest
 * @param  array $content The existing metabox tokens
 * @return array          The metabox tokens with Pinterest added
 */
function ppp_pt_register_metabox_content( $content ) {
	$content[] = 'pt';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_pt_register_metabox_content', 10, 1 );

/**
 * The callback that adds Pinterest metabox content
 * @param  object $post The post object
 * @return void         Displays the metabox content
 */
function ppp_pt_add_metabox_content( $post ) {
	$pinterest_data = get_post_meta( $post->ID, '_ppp_pt_media', true );
	$defaults = array(
		'attachment_id' => '',
		'image'         => '',
	);

	$pinterest_data = wp_parse_args( $pinterest_data, $defaults );
	?>
	<div class="ppp-post-override-wrap">
	<p><h3><?php _e( 'Pinterest Data', 'ppp-txt' ); ?></h3></p>
		<div id="ppp-pinterest-fields" class="ppp-pinterest-fields">
			<div id="ppp-pinterest-fields" class="ppp-meta-table-wrap">
				<table class="widefat ppp-repeatable-table" width="100%" cellpadding="0" cellspacing="0">
					<thead>
					<tr>
						<th style="width: 80%"><?php _e( 'Pinterest Image', 'ppp-txt' ); ?></th>
					</tr>
					</thead>
					<tbody>
						<tr class="ppp-pinterest-wrapper ppp-repeatable-row on-publish-row">
							<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
								<div class="ppp-repeatable-upload-field-container">
									<input type="hidden" name="_ppp_pinterest_data[attachment_id]" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $pinterest_data['attachment_id'] ) ); ?>"/>
									<input type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_pinterest_data[image]" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo esc_attr( $pinterest_data['image'] ); ?>" />

									<span class="ppp-upload-file">
								<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
									<span class="dashicons dashicons-upload"></span>
								</a>
							</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'ppp_generate_metabox_content-pt', 'ppp_pt_add_metabox_content', 10, 1 );

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 */
function ppp_pt_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$pinterest_data = $_POST['_ppp_pinterest_data'];

	update_post_meta( $post_id, '_ppp_pt_media', $pinterest_data );

}
add_action( 'save_post', 'ppp_pt_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Output the Pinterest OG Data
 *
 * @since  2.2
 * @return void
 */
function ppp_pt_open_graph_meta() {

	if ( ! is_single() ) {
		return;
	}

	global $post, $ppp_options;

	if ( ! array_key_exists( $post->post_type, $ppp_options['post_types'] ) ) {
		return;
	}

	echo ppp_pt_get_open_graph_meta();
}
add_action( 'wp_head', 'ppp_pt_open_graph_meta', 10 );

/**
 * Generates the Pinterest OG Content
 *
 * @since  2.2
 * @return string The Pinterest OG tags
 */
function ppp_pt_get_open_graph_meta() {

	$return = '';

	if ( ! is_single() ) {
		return $return;
	}

	global $post;


	if ( empty( $post ) ) {
		return;
	}

	$elements = ppp_pt_default_meta_elements();
	foreach ( $elements as $name => $content ) {
		$return .= '<meta name="' . $name . '" content="' . $content . '" />' . "\n";
	}

	return apply_filters( 'ppp_pt_og_data', $return );

}

/**
 * Sets an array of names and content for Pinterest OG Meta
 * for easy filtering by devs
 *
 * @since  2.2
 * @return array The array of keys and values for the Pinterest Meta
 */
function ppp_pt_default_meta_elements() {
	global $post;

	$elements       = array();
	$pinterest_data = get_post_meta( $post->ID, '_ppp_pt_media', true );

	if ( ! empty( $pinterest_data['image'] ) ) {
		$elements['og:image'] = $pinterest_data['image'];

		$thumb_id = ! empty( $pinterest_data['attachment_id'] ) ? (int) $pinterest_data['attachment_id'] : ppp_get_attachment_id_from_image_url( $pinterest_data['image'] ) ;

		if ( ! empty( $thumb_id ) ) {
			$alt_text = ppp_get_attachment_alt_text( $thumb_id );
			// When adding media via the WP Uploader, any 'alt text' supplied will be used as the accessible alt text.
			if ( ! empty( $alt_text ) ) {
				$elements['og:image:alt'] = esc_attr( $alt_text );
			}
		}
	}

	$elements['og:referenced'] = get_the_permalink( $post );

	return apply_filters( 'ppp_pt_og_elements', $elements );
}