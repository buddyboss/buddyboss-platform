<?php
/**
 * BuddyBoss Events Admin List Table.
 *
 * @package BuddyBoss\Events\Admin
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Events list table for WP Admin → Events.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_Events_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => false,
		) );
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Event', 'buddyboss' ),
			'organizer'  => __( 'Organizer', 'buddyboss' ),
			'group'      => __( 'Group', 'buddyboss' ),
			'start_date' => __( 'Date', 'buddyboss' ),
			'status'     => __( 'Status', 'buddyboss' ),
			'attendees'  => __( 'Attendees', 'buddyboss' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'      => array( 'title', false ),
			'start_date' => array( 'start_date', true ),
			'status'     => array( 'status', false ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$search       = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$status       = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : null;

		$result = bp_events_get_events( array(
			'status'   => $status,
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $current_page,
			'orderby'  => isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'start_date',
			'order'    => isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC',
		) );

		$this->items = $result['events'];

		$this->set_pagination_args( array(
			'total_items' => $result['total'],
			'per_page'    => $per_page,
		) );

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Render checkbox column.
	 */
	protected function column_cb( $event ) {
		return sprintf( '<input type="checkbox" name="event_ids[]" value="%d" />', $event->id );
	}

	/**
	 * Render title column with row actions.
	 */
	protected function column_title( $event ) {
		$permalink = bp_get_event_permalink( $event );
		$edit_url  = $permalink . 'edit/';

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'buddyboss' ) ),
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $permalink ), __( 'View', 'buddyboss' ) ),
			'delete' => sprintf(
				'<a href="#" class="bp-events-delete" data-event-id="%d">%s</a>',
				$event->id,
				__( 'Delete', 'buddyboss' )
			),
		);

		if ( 'published' === $event->status ) {
			$actions['cancel'] = sprintf(
				'<a href="#" class="bp-events-cancel" data-event-id="%d">%s</a>',
				$event->id,
				__( 'Cancel', 'buddyboss' )
			);
		} elseif ( 'pending' === $event->status ) {
			$actions['approve'] = sprintf(
				'<a href="#" class="bp-events-approve-btn" data-event-id="%d" data-nonce="%s" data-action="bp_events_approve">%s</a>',
				$event->id,
				esc_attr( wp_create_nonce( 'bp_events_admin_action' ) ),
				__( 'Approve', 'buddyboss' )
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $event->title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render organizer column.
	 */
	protected function column_organizer( $event ) {
		$user = get_user_by( 'id', $event->organizer_id );
		return $user ? esc_html( $user->display_name ) : '&mdash;';
	}

	/**
	 * Render group column.
	 */
	protected function column_group( $event ) {
		if ( empty( $event->group_id ) ) {
			return '&mdash;';
		}
		$group = groups_get_group( $event->group_id );
		return $group ? esc_html( $group->name ) : '&mdash;';
	}

	/**
	 * Render start date column.
	 */
	protected function column_start_date( $event ) {
		if ( empty( $event->start_date ) ) {
			return '&mdash;';
		}
		return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->start_date ) ) );
	}

	/**
	 * Render status column.
	 */
	protected function column_status( $event ) {
		$labels = array(
			'draft'     => '<span class="bp-events-status bp-events-status--draft">' . __( 'Draft', 'buddyboss' ) . '</span>',
			'pending'   => '<span class="bp-events-status bp-events-status--pending">' . __( 'Pending', 'buddyboss' ) . '</span>',
			'published' => '<span class="bp-events-status bp-events-status--published">' . __( 'Published', 'buddyboss' ) . '</span>',
			'cancelled' => '<span class="bp-events-status bp-events-status--cancelled">' . __( 'Cancelled', 'buddyboss' ) . '</span>',
		);

		return isset( $labels[ $event->status ] ) ? $labels[ $event->status ] : esc_html( $event->status );
	}

	/**
	 * Render attendees column.
	 */
	protected function column_attendees( $event ) {
		global $wpdb;
		$bp    = buddypress();
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND status = 'registered'",
			$event->id
		) );
		return $count;
	}

	/**
	 * Get views (status filter tabs).
	 *
	 * @return array
	 */
	protected function get_views() {
		return array(
			'all'       => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=bp-events' ) ), __( 'All', 'buddyboss' ) ),
			'published' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=bp-events&status=published' ) ), __( 'Published', 'buddyboss' ) ),
			'pending'   => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=bp-events&status=pending' ) ), __( 'Pending Review', 'buddyboss' ) ),
			'draft'     => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=bp-events&status=draft' ) ), __( 'Draft', 'buddyboss' ) ),
			'cancelled' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=bp-events&status=cancelled' ) ), __( 'Cancelled', 'buddyboss' ) ),
		);
	}

	/**
	 * Output "no items" message.
	 */
	public function no_items() {
		esc_html_e( 'No events found.', 'buddyboss' );
	}

	/**
	 * Display the list table and output the inline JS for the approve action.
	 *
	 * Overrides WP_List_Table::display() to append a small jQuery snippet
	 * that handles clicks on .bp-events-approve-btn via AJAX.
	 */
	public function display() {
		parent::display();
		$this->print_approve_script();
	}

	/**
	 * Output the inline JS block for the approve AJAX action.
	 *
	 * Only outputs on the bp-events admin page. Uses jQuery (always available
	 * in WP Admin). Follows WordPress JS Coding Standards.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	protected function print_approve_script() {
		?>
		<script type="text/javascript">
		/* global ajaxurl */
		( function( $ ) {
			$( document ).on( 'click', '.bp-events-approve-btn', function( e ) {
				e.preventDefault();

				var $btn     = $( this );
				var eventId  = $btn.data( 'event-id' );
				var nonce    = $btn.data( 'nonce' );
				var action   = $btn.data( 'action' );
				var confirmed = window.confirm( '<?php echo esc_js( __( 'Approve this event and publish it?', 'buddyboss' ) ); ?>' );

				if ( ! confirmed ) {
					return;
				}

				$.post(
					ajaxurl,
					{
						action:   action,
						event_id: eventId,
						nonce:    nonce
					},
					function( response ) {
						if ( response.success ) {
							window.location.reload();
						} else {
							var message = ( response.data ) ? response.data : '<?php echo esc_js( __( 'Could not approve event.', 'buddyboss' ) ); ?>';
							window.alert( message );
						}
					}
				);
			} );
		}( jQuery ) );
		</script>
		<?php
	}
}
