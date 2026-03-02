<?php
/**
 * BuddyBoss Moderation Report admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation_Report
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Moderation report component admin page.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Report_List_Table extends WP_List_Table {

	/**
	 * What type of view is being displayed?
	 *
	 * E.g. "Blocked", "Reported"
	 *
	 * @since BuddyBoss 2.1.1
	 * @var string $view
	 */
	public $view = 'reported';

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.5.6
	 * @param strring $view type of view.
	 */
	public function __construct( $view = 'reported' ) {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct(
			array(
				'ajax'     => false,
				'plural'   => 'reports',
				'singular' => 'report',
			)
		);
		$this->view = $view;
	}

	/**
	 * Get an array of all the columns on the page.
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'reporter';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function no_items() {
		$tab = bb_filter_input_string( INPUT_GET, 'tab' );
		if ( ! empty( $tab ) && 'reported-content' === $tab ) {
			esc_html_e( 'This member has not been reported by any members.', 'buddyboss' );
		} else {
			if ( 'blocked' === $this->view ) {
				esc_html_e( 'This member has not been blocked by any members.', 'buddyboss' );
			} else {
				esc_html_e( 'This member has not been reported by any members.', 'buddyboss' );
			}
		}
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function prepare_items() {

		$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
		$moderation_content_type = bb_filter_input_string( INPUT_GET, 'content_type' );
		$moderation_request_data = new BP_Moderation( $moderation_id, $moderation_content_type );

		if ( empty( $moderation_request_data->id ) ) {
			$moderation_request_data = new BP_Moderation( $moderation_id, BP_Moderation_Members::$moderation_type_report );
		}

		// Set current page.
		$page = $this->get_pagenum();
		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$args = 'user' === $moderation_content_type ? array( 'user_repoted' => true ) : array();
		if ( 'blocked' === $this->view ) {
			$args = array( 'user_repoted' => false );
		}
		$reporters = BP_Moderation::get_moderation_reporters( $moderation_request_data->id, $args );

		$total_item  = ( ! empty( $reporters ) ) ? count( $reporters ) : 0;
		$total_pages = ceil( $total_item / $per_page );
		$page        = max( $page, 1 );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;

		if ( $offset < 0 ) {
			$offset = 0;
		}

		$this->items = array_slice( $reporters, $offset, $per_page );

		// Store information needed for handling table pagination.
		$this->set_pagination_args(
			array(
				'per_page'    => $per_page,
				'total_items' => $total_item,
				'total_pages' => ceil( $total_item / $per_page ),
			)
		);
	}

	/**
	 * Output the Moderation report data table.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function display() {
		?>
		<h2 class="screen-reader-text">
			<?php
			/* translators: accessibility text */
			esc_html_e( 'Moderation Request list', 'buddyboss' );
			?>
		</h2>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<?php if ( $this->has_items() ) { ?>
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>
			<?php } ?>

			<tbody id="the-moderation-report-list">
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<?php if ( $this->has_items() ) { ?>
			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
			<?php } ?>
		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Get the table column titles.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return array Array of column titles.
	 * @see   WP_List_Table::single_row_columns()
	 */
	public function get_columns() {

		$tab = bb_filter_input_string( INPUT_GET, 'tab' );
		if ( ! empty( $tab ) && 'reported-content' === $tab ) {
			$columns = array(
				'reporter' => esc_html__( 'Reporter', 'buddyboss' ),
				'category' => esc_html__( 'Category', 'buddyboss' ),
				'date'     => esc_html__( 'Date Reported', 'buddyboss' ),
			);
		} else {
			if ( 'blocked' === $this->view ) {
				$columns = array(
					'reporter' => esc_html__( 'Member', 'buddyboss' ),
					'date'     => esc_html__( 'Date Blocked', 'buddyboss' ),
				);
			} else {
				$columns = array(
					'reporter' => esc_html__( 'Reporter', 'buddyboss' ),
					'category' => esc_html__( 'Category', 'buddyboss' ),
					'date'     => esc_html__( 'Date Reported', 'buddyboss' ),
				);
			}
		}

		/**
		 * Filters the titles for the columns for the moderation report list table.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters( 'bp_moderation_report_list_table_get_columns', $columns );
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $item The current item.
	 */
	public function single_row( $item ) {
		$item = (array) $item;
		echo '<tr>';
		$single_row_columns = $this->single_row_columns( $item );
		if ( ! empty( $single_row_columns ) ) {
			wp_kses_post( $single_row_columns );
		}
		echo '</tr>';
	}

	/**
	 * Function to item reporter.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $item loop item.
	 */
	public function column_reporter( $item = array() ) {
		printf( '<strong><a target="_blank" href="%s">%s %s</a></strong>', esc_url( BP_Moderation_Members::get_permalink( $item['user_id'] ) ), get_avatar( $item['user_id'], '32' ), esc_html( bp_core_get_userlink( $item['user_id'], true ) ) );

	}

	/**
	 * Function to item category.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $item loop item.
	 */
	public function column_category( $item = array() ) {
		$term_data        = get_term( $item['category_id'] );
		$term_name        = ( ! is_wp_error( $term_data ) && ! empty( $term_data->name ) ) ? $term_data->name : esc_html__( 'Other', 'buddyboss' );
		$term_description = ( ! is_wp_error( $term_data ) && ! empty( $term_data->description ) ) ? $term_data->description : $item['content'];
		printf( '<strong class="bp-cat-name">%s</strong><p class="description">%s</p>', esc_html( $term_name ), wp_kses_post( $term_description ) );
	}

	/**
	 * Function to show the item date.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $item item data.
	 */
	public function column_date( $item = array() ) {
		echo esc_html(
			date_i18n(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				strtotime( $item['date_created'] )
			)
		);
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since BuddyBoss 1.5.6
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
		 * @since BuddyBoss 1.5.6
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current moderation report item in the loop.
		 */
		return apply_filters( 'bp_moderation_admin_get_custom_column', '', $column_name, $item );
	}
}
