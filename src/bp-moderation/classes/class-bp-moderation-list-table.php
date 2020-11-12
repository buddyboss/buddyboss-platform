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
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		return ( 'reported-content' === $current_tab ) ? 'content_type' : 'blocked_member';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function no_items() {
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		if ( 'reported-content' === $current_tab ) {
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
		$current_tab       = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab       = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		// Set current page.
		$page = $this->get_pagenum();

		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$moderation_request_args = array(
			'page'        => $page,
			'per_page'    => $per_page,
			'count_total' => true,
		);

		if ( 'reported-content' === $current_tab ) {
			$moderation_request_args['exclude_types'] = array( BP_Moderation_Members::$moderation_type );
		} else {
			$moderation_request_args['in_types'] = array( BP_Moderation_Members::$moderation_type );
		}

		if ( 'reported-content' === $current_tab ) {
			if ( 'active' === $moderation_status ) {
				$this->view                        = 'active';
				$moderation_request_args['filter'] = array( 'hide_sitewide' => 0 );
			} elseif ( 'hidden' === $moderation_status ) {
				$this->view                        = 'hidden';
				$moderation_request_args['filter'] = array( 'hide_sitewide' => 1 );
			} else {
				$this->view = 'all';
			}
		} else {
			if ( 'suspended' === $moderation_status ) {
				$this->view                        = 'suspended';
				$moderation_request_args['filter'] = array( 'hide_sitewide' => 1 );
			} else {
				$this->view = 'all';
			}
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
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		if ( 'reported-content' === $current_tab ) {
			$columns = array(
				'content_excerpt' => esc_html__( 'Content Excerpt', 'buddyboss' ),
				'content_type'    => esc_html__( 'Content Type', 'buddyboss' ),
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
		$current_tab               = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab               = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;
		$blocked_members_url_base  = add_query_arg( array( 'page' => 'bp-moderation', ), bp_get_admin_url( 'admin.php' ) );
		$reported_content_url_base = ( bp_is_moderation_member_blocking_enable() ) ? add_query_arg( array( 'tab' => 'reported-content', ), $blocked_members_url_base ) : $blocked_members_url_base;

		$moderation_views = apply_filters( 'bp_moderation_get_views', array(
			'blocked-members'  => array(
				'all'       => array(
					'name' => esc_html__( 'All', 'buddyboss' ),
					'link' => $blocked_members_url_base
				),
				'suspended' => array(
					'name' => esc_html__( 'Suspended', 'buddyboss' ),
					'link' => add_query_arg( array( 'moderation_status' => 'suspended' ), $blocked_members_url_base )
				)
			),
			'reported-content' => array(
				'all'    => array(
					'name' => esc_html__( 'All', 'buddyboss' ),
					'link' => $reported_content_url_base
				),
				'active' => array(
					'name' => esc_html__( 'Active', 'buddyboss' ),
					'link' => add_query_arg( array( 'moderation_status' => 'active' ), $reported_content_url_base )
				),
				'hidden' => array(
					'name' => esc_html__( 'Hidden', 'buddyboss' ),
					'link' => add_query_arg( array( 'moderation_status' => 'hidden' ), $reported_content_url_base )
				),
			)
		) );
		?>
        <ul class="subsubsub">
			<?php
			if ( 'reported-content' === $current_tab && ! empty( $moderation_views['reported-content'] ) ) {
				$total_count = count( $moderation_views['reported-content'] );
				$count       = 1;
				foreach ( $moderation_views['reported-content'] as $key => $moderation_view ) {
					?>
                    <li class="<?php echo esc_attr( $key ); ?>">
                        <a href="<?php echo esc_url( $moderation_view['link'] ); ?>"
                           class="<?php echo ( $key === $this->view ) ? 'current' : ''; ?>">
							<?php echo esc_html( $moderation_view['name'] ); ?>
                        </a>
						<?php
						if ( $count !== (int) $total_count ) {
							echo " | ";
						}
						?>
                    </li>
					<?php
					$count ++;
				}
			} else {
				$total_count = count( $moderation_views['blocked-members'] );
				$count       = 1;
				foreach ( $moderation_views['blocked-members'] as $key => $moderation_view ) {
					?>
                    <li class="<?php echo esc_attr( $key ); ?>">
                        <a href="<?php echo esc_url( $moderation_view['link'] ); ?>"
                           class="<?php echo ( $key === $this->view ) ? 'current' : ''; ?>">
							<?php echo esc_html( $moderation_view['name'] ); ?>
                        </a>
						<?php
						if ( $count !== (int) $total_count ) {
							echo " | ";
						}
						?>
                    </li>
					<?php
					$count ++;
				}
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
	 * @since BuddyBoss 2.0.0
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
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		$actions = array(
			'view_report' => '',
			'suspend'     => '',
		);

		$moderation_args = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( 'reported-content' === $current_tab ) {
			$moderation_args['tab'] = 'reported-content';
		}

		$user_action_type = ( 1 === (int) $item['hide_sitewide'] ) ? 'unhide' : 'hide';
		$action_label     = ( 'unhide' === $user_action_type ) ? esc_html__( 'Unsuspend', 'buddyboss' ) : esc_html__( 'Suspend', 'buddyboss' );
		$user_id          = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );

		// Build actions URL.
		$view_url               = add_query_arg( $moderation_args, bp_get_admin_url( 'admin.php' ) );
		$actions['view_report'] = sprintf( '<a href="%s" title="%s"> %s </a>', esc_url( $view_url ), esc_attr__( 'View', 'buddyboss' ), esc_html__( 'View Reports', 'buddyboss' ) );
		$actions['suspend']     = sprintf( '<a href="#" class="bp-block-user" data-id="%s" data-type="user" data-nonce="%s" data-action="%s" title="%s">%s</a>', esc_attr( $user_id ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $user_action_type ), esc_attr( $action_label ), esc_html( $action_label ) );
		printf( '%s <strong>%s</strong> %s', get_avatar( $user_id, '32' ), wp_kses_post( bp_core_get_userlink( $user_id ) ), wp_kses_post( $this->row_actions( $actions ) ) );
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
		printf( '%s <strong>%s</strong>', get_avatar( $user_id, '32' ), wp_kses_post( bp_core_get_userlink( $user_id ) ) );
	}

	/**
	 * Function to show content excerpt
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_content_excerpt( $item = array() ) {
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

		$actions = array(
			'view_report' => '',
			'hide'        => '',
			'suspend'     => '',
		);

		$moderation_args = array(
			'page'         => 'bp-moderation',
			'mid'          => $item['item_id'],
			'content_type' => $item['item_type'],
			'action'       => 'view',
		);

		if ( 'reported-content' === $current_tab ) {
			$moderation_args['tab'] = 'reported-content';
		}

		$action_type  = ( 1 === (int) $item['hide_sitewide'] ) ? 'unhide' : 'hide';
		$action_label = ( 'unhide' === $action_type ) ? esc_html__( 'Unhide Content', 'buddyboss' ) : esc_html__( 'Hide Content', 'buddyboss' );
		$user_id      = bp_moderation_get_content_owner_id( $item['item_id'], $item['item_type'] );

		$user_action_type  = 'hide';
		$user_action_label = esc_html__( 'Suspend', 'buddyboss' );
		$user_data         = BP_Moderation::get_specific_moderation( $user_id, 'user' );

		if ( ! empty( $user_data ) ) {
			$user_action_type  = ( 1 === (int) $user_data->hide_sitewide ) ? 'unhide' : 'hide';
			$user_action_label = ( 'unhide' === $user_action_type ) ? esc_html__( 'Unsuspend', 'buddyboss' ) : esc_html__( 'Suspend', 'buddyboss' );
		}

		$content_excerpt        = bp_moderation_get_content_excerpt( $item['item_id'], $item['item_type'] );
		$view_url               = add_query_arg( $moderation_args, bp_get_admin_url( 'admin.php' ) );
		$actions['view_report'] = sprintf( '<a href="%s" title="%s"> %s </a>', esc_url( $view_url ), esc_attr__( 'View', 'buddyboss' ), esc_html__( 'View Reports', 'buddyboss' ) );
		$actions['hide']        = sprintf( '<a href="javascript:void(0);" class="bp-hide-request" data-id="%s" data-type="%s" data-nonce="%s" data-action="%s" title="%s">%s</a>', esc_attr( $item['item_id'] ), esc_attr( $item['item_type'] ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $action_type ), esc_attr( $action_label ), esc_html( $action_label ) );
		$actions['suspend']     = sprintf( '<a href="javascript:void(0);" class="bp-block-user delete" data-id="%s" data-type="user" data-nonce="%s" data-action="%s" title="%s">%s</a>', esc_attr( $user_id ), esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ), esc_attr( $user_action_type ), esc_attr( $user_action_label ), esc_html( $user_action_label ) );
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
