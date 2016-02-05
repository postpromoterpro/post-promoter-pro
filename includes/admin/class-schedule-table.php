<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Creates the Schedule List table for Post Promoter Pro
 */
class PPP_Schedule_Table extends WP_List_Table {

	/**
	 * Generate the Class from it's parent
	 */
	public function __construct() {
		global $status, $page;

		parent::__construct( array(
				'singular'  => __( 'Scheduled Share', 'ppp-txt' ),
				'plural'    => __( 'Scheduled Shares', 'ppp-txt' ),
				'ajax'      => false,
			) );
	}

	/**
	 * What to show if no items are found
	 * @return void
	 */
	public function no_items() {
		printf( __( 'No shares scheduled. Go <a href="%s">write something</a>!', 'ppp-txt' ), admin_url( 'post-new.php' ) );
	}

	/**
	 * The Default columns
	 * @param  array $item        The Item being displayed
	 * @param  string $column_name The column we're currently in
	 * @return string              The Content to display
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		case 'date':
		case 'post_title':
			return $item[ $column_name ];
		case 'content':
			$content = $item[ $column_name ];
			return $content;
		case 'image':
			if ( ! empty( $item['image_url'] ) ) {
				$content = '<img src="' . $item['image_url'] . '" />';
			} else {
				$content = __( 'None', 'ppp-txt' );
			}
			return $content;
		default:
			return;
		}
	}

	/**
	 * The columns for our list view
	 * @return array Columns shown on the Schedule page
	 */
	public function get_columns() {
		$columns = array(
			'post_title'     => __( 'Post Title', 'ppp-txt' ),
			'date'           => __( 'Scheduled Date', 'ppp-txt' ),
			'content'        => __( 'Share Message', 'ppp-txt' ),
			'image'          => __( 'Image', 'ppp-txt' )
		);

		return $columns;
	}

	/**
	 * Specifies how to display the post_id column on the schedule page
	 * @param  array $item The Item being displayed
	 * @return string       The HTML to display for this column.
	 */
	public function column_post_title( $item ) {
		$actions = array(
			'edit'          => sprintf( __( '<a href="%s">Edit</a>', 'ppp-txt' ), admin_url( 'post.php?post=' . $item['post_id'] . '&action=edit#ppp_schedule_metabox' ) ),
			'delete'        => sprintf( __( '<a href="%s">Delete</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=delete_item&post_id=' . $item['post_id'] . '&name=' . $item['name'] . '&index=' . $item['index'] ) ),
			'share'         => sprintf( __( '<a href="%s">Share Now</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=share_now&post_id=' . $item['post_id'] . '&name=' . $item['name'] ) ),
			'share_delete'  => sprintf( __( '<a href="%s">Share Now & Delete</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=share_now&post_id=' . $item['post_id'] . '&name=' . $item['name'] . '&index=' . $item['index'] . '&delete_too=true' ) ),
		);

		return sprintf( '<span class="dashicons icon-ppp-' . $item['service'] . '"></span>&nbsp;%1$s %2$s', $item['post_title'], $this->row_actions( $actions ) );
	}

	/**
	 * Specifies how to display the date columon on the schedule page
	 * @param  array $item The Item being displayed
	 * @return string       The HTML to display the date
	 */
	public function column_date( $item ) {
		$date = date_i18n( get_option('date_format') . ' @ ' . get_option('time_format'), $item['date'] );
		if ( $item['conflict'] ) {
			$date .= '<br /><small style="color: red">' . __( 'Warning: Multiple items scheduled at this time.', 'ppp-txt' ) . '</small>';
		}

		return $date;
	}

	/**
	 * Prepare the data for the WP List Table
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$data                  = array();
		$sortable              = false;
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = 25;

		$crons = ppp_get_shceduled_crons();
		$cron_tally = array();
		foreach ( $crons as $key => $cron ) {
			$ppp_data = $cron;
			$timestamp = $ppp_data['timestamp'];

			$cron_tally[$timestamp] = isset( $cron_tally[$timestamp] ) ? $cron_tally[$timestamp] + 1 : 1;

			$name_parts = explode( '_', $ppp_data['args'][1] );
			$index      = $name_parts[1];
			$service    = isset( $name_parts[3] ) ? $name_parts[3] : 'tw';
			$builder    = 'ppp_' . $service . '_build_share_message';
			$post_meta  = apply_filters( 'ppp_get_scheduled_items_' . $service, array(), $ppp_data['args'][0] );
			$image_url  = '';

			if ( ! empty( $post_meta[$index]['attachment_id'] ) ) {
				$image_url = ppp_post_has_media( $ppp_data['args'][0], $service, true, $post_meta[ $index ]['attachment_id'] );
			} elseif ( ! empty( $post_meta[ $index ]['image'] ) ) {
				$image_url = $post_meta[ $index ]['image'];
			}

			$conflict   = $cron_tally[ $timestamp ] > 1 ? true : false;

			$data[ $key ] = array(
				'post_id'      => $ppp_data['args'][0],
				'post_title'   => get_the_title( $ppp_data['args'][0] ),
				'service'      => $service,
				'index'        => $index,
				'date'         => $timestamp + ( get_option( 'gmt_offset' ) * 3600 ),
				'content'      => function_exists( $builder ) ? $builder( $ppp_data['args'][0], $ppp_data['args'][1], false ) : '',
				'name'         => 'sharedate_' . $index . '_' . $ppp_data['args'][0],
				'conflict'     => $conflict,
			);

			if ( ! empty( $image_url ) ) {
				$data[ $key ]['image_url'] = $image_url;
			}

		}

		$total_items = count( $data );

		$offset = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;

		$data = array_slice( $data, ( $offset - 1 ) * $per_page, $per_page, true );
		$this->items = $data;


		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			) );

		$this->items = $data;
	}
}

$ppp_schedule_table = new PPP_Schedule_Table();
