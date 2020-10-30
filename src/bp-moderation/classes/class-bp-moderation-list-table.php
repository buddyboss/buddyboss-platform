<?php
/**
 * BuddyBoss Moderation admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Moderation component admin page.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct(
			array(
				'ajax'     => false,
				'plural'   => 'moderations',
				'singular' => 'moderation',
			)
		);
	}

	/**
	 * Get an array of all the columns on the page.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return array Column headers.
	 */
	public function get_column_info() {

		$screen         = get_current_screen();
		$hidden_columns = get_hidden_columns( $screen );
		$hidden_columns = ( ! empty( $hidden_columns ) ) ? $hidden_columns : array();

		$this->_column_headers = array(
			$this->get_columns(),
			$hidden_columns,
			$this->get_sortable_columns(),
			$this->get_default_primary_column_name(),
		);

		return $this->_column_headers;
	}

	/**
	 * Get name of default primary column
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'reporter';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function no_items() {
		esc_html__( 'No moderation requests found.', 'buddyboss' );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function prepare_items() {

		// Set current page.
		$page = $this->get_pagenum();

		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$moderation_request_args = array(
			'page'        => $page,
			'per_page'    => $per_page,
			'count_total' => true,
		);

		if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
			$moderation_request_args['in_types'] = array( 'user' );
		} else {
			$moderation_request_args['exclude_types'] = array( 'user' );
		}

		$moderation_requests = BP_Moderation::get( $moderation_request_args );

		// Set raw data to display.
		$this->items = $moderation_requests['moderations'];

		// Store information needed for handling table pagination.
		$this->set_pagination_args(
			array(
				'per_page'    => $per_page,
				'total_items' => $moderation_requests['total'],
				'total_pages' => ceil( $moderation_requests['total'] / $per_page ),
			)
		);
	}

	/**
	 * Output the Moderation data table.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function display() {
		$this->display_tablenav( 'top' ); ?>

		<h2 class="screen-reader-text">
			<?php
			/* translators: accessibility text */
			esc_html_e( 'Moderation Request list', 'buddyboss' );
			?>
		</h2>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-moderation-request-list">
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Get the table column titles.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return array Array of column titles.
	 * @see   WP_List_Table::single_row_columns()
	 */
	public function get_columns() {

		/**
		 * Filters the titles for the columns for the moderation list table.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters(
			'bp_moderation_list_table_get_columns',
			array(
				'reporter'      => __( 'Reporter', 'buddyboss' ),
				'content_owner' => __( 'Content Owner', 'buddyboss' ),
				'content_type'  => __( 'Content Type', 'buddyboss' ),
				'content_id'    => __( 'Content ID', 'buddyboss' ),
				'report'        => __( 'Report', 'buddyboss' ),
				'date'          => __( 'Last Reported', 'buddyboss' ),
			)
		);
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param object $item The current item.
	 */
	public function single_row( $item ) {
		$item = (array) $item;
		echo '<tr>';
		wp_kses_post( $this->single_row_columns( $item ) );
		echo '</tr>';
	}

	/**
	 * Name column, and "quick admin" rollover actions.
	 *
	 * Called "comment" in the CSS so we can re-use some WP core CSS.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $item A singular item (one full row).
	 *
	 * @see   WP_List_Table::single_row_columns()
	 */
	public function column_reporter( $item = array() ) {

		// Preorder items: View.
		$actions = array(
			'view' => '',
		);

		$view_url_query_arg = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
			$view_url_query_arg['tab'] = 'blocked-members';
		}

		// Build actions URL.
		$view_url = add_query_arg( $view_url_query_arg, bp_get_admin_url( 'admin.php' ) );

		// Rollover actions.
		// View.
		$actions['view'] = sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'buddyboss' ) );

		printf( '<strong>%s %s</strong> %s', wp_kses_post( get_avatar( $item['updated_by'], '32' ) ), wp_kses_post( bp_core_get_userlink( $item['updated_by'] ) ), wp_kses_post( $this->row_actions( $actions ) ) );
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array  $item        Information about the current row.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item = array(), $column_name = '' ) {

		if ( 'content_type' === $column_name ) {
			echo esc_html( bp_get_moderation_content_type( $item['item_type'] ) );
		}

		if ( 'content_id' === $column_name ) {
			echo esc_html( $item['item_id'] );
		}

		if ( 'content_owner' === $column_name ) {
			$user_id = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );
			printf( '<strong>%s %s</strong>', wp_kses_post( get_avatar( $user_id, '32' ) ), wp_kses_post( bp_core_get_userlink( $user_id ) ) );
		}

		if ( 'report' === $column_name ) {
			echo 'report category';
		}

		if ( 'date' === $column_name ) {
			echo esc_html( bbp_get_time_since( bbp_convert_date( $item['date_updated'] ) ) );
		}

		/**
		 * Filters a string to allow plugins to add custom column content.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current moderation item in the loop.
		 */
		return apply_filters( 'bp_moderation_admin_get_custom_column', '', $column_name, $item );
	}

}
