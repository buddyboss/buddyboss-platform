<?php
/**
 * BuddyBoss Moderation admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Moderation component admin page.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) ? 'blocked_member' : 'content_type';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array Array of column titles.
	 * @see   WP_List_Table::single_row_columns()
	 */
	public function get_columns() {
		if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
			$columns = array(
				'blocked_member' => esc_html__( 'Blocked Member', 'buddyboss' ),
				'blocked'        => esc_html__( 'Block (Count)', 'buddyboss' ),
				'actions'        => '',
			);
		} else {
			$columns = array(
				'content_type'    => esc_html__( 'Content Type', 'buddyboss' ),
				'content_id'      => esc_html__( 'Content ID', 'buddyboss' ),
				'content_excerpt' => esc_html__( 'Content Excerpt', 'buddyboss' ),
				'content_owner'   => esc_html__( 'Content Owner', 'buddyboss' ),
				'reported'        => esc_html__( 'Reported (Count)', 'buddyboss' ),
				'actions'         => '',
			);
		}

		/**
		 * Filters the titles for the columns for the moderation list table.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters( 'bp_moderation_list_table_get_columns', $columns );
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * Function to show content type
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_content_type( $item = array() ) {
		printf( '<strong>%s</strong> %s', esc_html( bp_moderation_get_content_type( $item['item_type'] ) ), wp_kses_post( $this->actions( $item ) ) );
	}

	/**
	 * Function to blocked member
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop ite.
	 */
	public function column_blocked_member( $item = array() ) {
		$user_id = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );
		printf( '<strong>%s</strong> %s', wp_kses_post( bp_core_get_userlink( $user_id ) ), wp_kses_post( $this->actions( $item ) ) );
	}

	/**
	 * Function content id
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_content_id( $item = array() ) {
		echo esc_html( $item['item_id'] );
	}

	/**
	 * Function to content owner
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_content_owner( $item = array() ) {
		$user_id = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );
		printf( '<strong>%s</strong>', wp_kses_post( bp_core_get_userlink( $user_id ) ) );
	}

	/**
	 * Function to show content excerpt
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_content_excerpt( $item = array() ) {
		$content_excerpt = bp_moderation_get_content_excerpt( $item['item_id'], $item['item_type'] );
		echo wp_kses_post( substr( $content_excerpt, 0, 100 ) );
	}

	/**
	 * Function to show reported count
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_reported( $item = array() ) {
		/* translators: accessibility text */
		printf( _n( '%s time', '%s times', $item['count'], 'buddyboss' ), esc_html( number_format_i18n( $item['count'] ) ) );
	}

	/**
	 * Function to blocked count
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_blocked( $item = array() ) {
		/* translators: accessibility text */
		printf( _n( '%s time', '%s times', $item['count'], 'buddyboss' ), esc_html( number_format_i18n( $item['count'] ) ) );
	}

	/**
	 * Function to show table actions
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_actions( $item = array() ) {

		$view_url_query_arg = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
			$view_url_query_arg['tab'] = 'blocked-members';
		}

		$action_type  = ( 1 === (int) $item['hide_sitewide'] ) ? 'unhide' : 'hide';
		$action_label = ( 'unhide' === $action_type ) ? esc_html__( 'Unhide', 'buddyboss' ) : esc_html__( 'Hide', 'buddyboss' );
		$user_id      = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );

		$user_action_type  = 'hide';
		$user_action_label = esc_html__( 'Hide', 'buddyboss' );
		$user_data         = BP_Moderation::get_specific_moderation( $user_id, 'user' );

		if ( ! empty( $user_data ) ) {
			$user_action_type  = ( 1 === (int) $user_data->hide_sitewide ) ? 'unhide' : 'hide';
			$user_action_label = ( 'unhide' === $user_action_type ) ? esc_html__( 'Unhide', 'buddyboss' ) : esc_html__( 'Hide', 'buddyboss' );
		}

		// Build actions URL.
		$view_url = add_query_arg( $view_url_query_arg, bp_get_admin_url( 'admin.php' ) );

		// Rollover actions.
		// View.
		printf( '<a href="%s" title="%s">View Report / </a>', esc_url( $view_url ), esc_attr__( 'View', 'buddyboss' ) );
		if ( ! isset( $_GET['tab'] ) || 'blocked-members' !== $_GET['tab'] ) {
			printf( '<a href="javascript:void(0);" class="bp-hide-request" data-id="%s" data-type="%s" data-nonce="%s" data-action="%s" title="%s">Hide Content / </a>', esc_attr( $item['item_id'] ), esc_attr( $item['item_type'] ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $action_type ), esc_attr( $action_label ) );
		}
		printf( '<a href="javascript:void(0);" class="bp-block-user" data-id="%s" data-type="user" data-nonce="%s" data-action="%s" title="%s">Suspend Content Owner</a>', esc_attr( $user_id ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $user_action_type ), esc_attr( $user_action_label ) );
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array  $item        Information about the current row.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item = array(), $column_name = '' ) {

		/**
		 * Filters a string to allow plugins to add custom column content.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current moderation item in the loop.
		 */
		return apply_filters( 'bp_moderation_admin_get_custom_column', '', $column_name, $item );
	}

}
