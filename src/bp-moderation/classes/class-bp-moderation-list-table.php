<?php
/**
 * BuddyBoss Moderation admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
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
	 * What type of view is being displayed?
	 *
	 * E.g. "all", "active", "hidden", "blocked"...
	 *
	 * @since BuddyBoss 2.0.0
	 * @var string $view
	 */
	public $view = 'all';

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
		return ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) ? 'content_type' : 'blocked_member';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function no_items() {
		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
			esc_html_e( 'Sorry, no reported content was found.', 'buddyboss' );
		} else {
			esc_html_e( 'Sorry, no blocked members were found.', 'buddyboss' );
		}
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

		$moderation_status = filter_input( INPUT_GET, 'moderation_status', FILTER_SANITIZE_STRING );

		// Set current page.
		$page = $this->get_pagenum();

		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$moderation_request_args = array(
			'page'        => $page,
			'per_page'    => $per_page,
			'count_total' => true,
		);

		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
			$moderation_request_args['exclude_types'] = array( 'user' );
		} else {
			$moderation_request_args['in_types'] = array( 'user' );
		}

		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] && 'active' === $moderation_status ) {
			$this->view                        = 'active';
			$moderation_request_args['filter'] = array( 'hide_sitewide' => 0 );
		} else if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] && 'hidden' === $moderation_status ) {
			$this->view                        = 'hidden';
			$moderation_request_args['filter'] = array( 'hide_sitewide' => 1 );
		} elseif ( 'suspended' === $moderation_status ) {
			$this->view                        = 'suspended';
			$moderation_request_args['filter'] = array( 'hide_sitewide' => 1 );
		} else {
			$this->view = 'all';
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
		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
			$columns = array(
				'content_excerpt' => esc_html__( 'Content Excerpt', 'buddyboss' ),
				'content_type'    => esc_html__( 'Content Type', 'buddyboss' ),
				'content_id'      => esc_html__( 'Content ID', 'buddyboss' ),
				'content_owner'   => esc_html__( 'Content Owner', 'buddyboss' ),
				'reported'        => esc_html__( 'Times Reported', 'buddyboss' ),
			);
		} else {
			$columns = array(
				'blocked_member' => esc_html__( 'Blocked Member', 'buddyboss' ),
				'blocked'        => esc_html__( 'Times Blocked', 'buddyboss' ),
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
	 * Get the list of views available on this table (e.g. "all", "spam").
	 *
	 * @since BuddyPress 2.0.0
	 */
	function get_views() {
		?>
        <ul class="subsubsub">
			<?php
			if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
				$url_base = add_query_arg( array(
					'page' => 'bp-moderation',
					'tab'  => 'reported-content'
				), bp_get_admin_url( 'admin.php' ) );
				?>
                <li class="all">
                    <a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( 'all' === $this->view ) {
						echo 'current';
					} ?>">
						<?php _e( 'All', 'buddyboss' ); ?>
                    </a> |
                </li>
                <li class="active">
                    <a href="<?php echo esc_url( add_query_arg( array( 'moderation_status' => 'active' ), $url_base ) ); ?>"
                       class="<?php if ( 'active' === $this->view ) {
						   echo 'current';
					   } ?>">
						<?php _e( 'Active', 'buddyboss' ); ?>
                    </a> |
                </li>
                <li class="hidden">
                    <a href="<?php echo esc_url( add_query_arg( array( 'moderation_status' => 'hidden' ), $url_base ) ); ?>"
                       class="<?php if ( 'hidden' === $this->view ) {
						   echo 'current';
					   } ?>">
						<?php _e( 'Hidden', 'buddyboss' ); ?>
                    </a>
                </li>
				<?php
			} else {
				$url_base = add_query_arg( array(
					'page' => 'bp-moderation',
				), bp_get_admin_url( 'admin.php' ) );
				?>
                <li class="all">
                    <a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( 'all' === $this->view ) {
						echo 'current';
					} ?>">
						<?php _e( 'All', 'buddyboss' ); ?>
                    </a> |
                </li>
                <li class="blocked">
                    <a href="<?php echo esc_url( add_query_arg( array( 'moderation_status' => 'suspended' ), $url_base ) ); ?>"
                       class="<?php if ( 'suspended' === $this->view ) {
						   echo 'current';
					   } ?>">
						<?php _e( 'Suspended', 'buddyboss' ); ?>
                    </a>
                </li>
				<?php
			}
			?>
        </ul>
		<?php
	}

	/**
	 * Override WP_List_Table::row_actions().
	 *
	 * Basically a duplicate of the row_actions() method, but removes the
	 * unnecessary <button> addition.
	 *
	 * @since BuddyBoss 2.0.0
	 * @since BuddyPress 2.0.0 Visibility set to public for compatibility with WP < 4.0.0.
	 *
	 * @param array $actions        The list of actions.
	 * @param bool  $always_visible Whether the actions should be always visible.
	 *
	 * @return string
	 */
	public function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			$cls = ( 'suspend' === $action ) ? 'delete' : $action;
			++ $i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$cls'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
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

		$actions = array(
			'view_report' => '',
			'suspend'     => '',
		);

		$view_url_query_arg = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
			$view_url_query_arg['tab'] = 'reported-content';
		}

		$action_type  = ( 1 === (int) $item['hide_sitewide'] ) ? 'unhide' : 'hide';
		$action_label = ( 'unhide' === $action_type ) ? esc_html__( 'Unhide', 'buddyboss' ) : esc_html__( 'Hide', 'buddyboss' );
		$user_id      = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );

		$user_action_type  = 'hide';
		$user_action_label = esc_html__( 'Suspend', 'buddyboss' );
		$user_data         = BP_Moderation::get_specific_moderation( $user_id, 'user' );

		if ( ! empty( $user_data ) ) {
			$user_action_type  = ( 1 === (int) $user_data->hide_sitewide ) ? 'unhide' : 'hide';
			$user_action_label = ( 'unhide' === $user_action_type ) ? esc_html__( 'Unsuspend', 'buddyboss' ) : esc_html__( 'Suspend', 'buddyboss' );
		}

		// Build actions URL.
		$view_url               = add_query_arg( $view_url_query_arg, bp_get_admin_url( 'admin.php' ) );
		$actions['view_report'] = sprintf( '<a href="%s" title="%s"> %s </a>', esc_url( $view_url ), esc_attr__( 'View', 'buddyboss' ), esc_html__( 'View Reports', 'buddyboss' ) );
		$actions['suspend']     = sprintf( '<a href="#" class="bp-block-user" data-id="%s" data-type="user" data-nonce="%s" data-action="%s" title="%s">%s</a>', esc_attr( $user_id ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $user_action_type ), esc_attr( $user_action_label ), esc_html( $user_action_label ) );
		printf( '%s <strong>%s</strong> %s', get_avatar( $user_id, '32' ), wp_kses_post( bp_core_get_userlink( $user_id ) ), wp_kses_post( $this->row_actions( $actions ) ) );
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

		$actions = array(
			'view_report' => '',
			'hide'        => '',
			'suspend'     => '',
		);

		$view_url_query_arg = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( ! empty( $_GET['tab'] ) && 'reported-content' === $_GET['tab'] ) {
			$view_url_query_arg['tab'] = 'reported-content';
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
		$content_excerpt        = bp_moderation_get_content_excerpt( $item['item_id'], $item['item_type'] );
		$view_url               = add_query_arg( $view_url_query_arg, bp_get_admin_url( 'admin.php' ) );
		$actions['view_report'] = sprintf( '<a href="%s" title="%s"> %s </a>', esc_url( $view_url ), esc_attr__( 'View', 'buddyboss' ), esc_html__( 'View Reports', 'buddyboss' ) );
		$actions['hide']        = sprintf( '<a href="javascript:void(0);" class="bp-hide-request" data-id="%s" data-type="%s" data-nonce="%s" data-action="%s" title="%s">Hide Content / </a>', esc_attr( $item['item_id'] ), esc_attr( $item['item_type'] ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $action_type ), esc_attr( $action_label ) );
		$actions['suspend']     = sprintf( '<a href="javascript:void(0);" class="bp-block-user delete" data-id="%s" data-type="user" data-nonce="%s" data-action="%s" title="%s">Suspend</a>', esc_attr( $user_id ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $user_action_type ), esc_attr( $user_action_label ) );
		echo wp_kses_post( substr( $content_excerpt, 0, 100 ) ) . ' ' . $this->row_actions( $actions );
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
