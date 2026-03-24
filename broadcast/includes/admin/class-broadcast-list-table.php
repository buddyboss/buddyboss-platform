<?php
/**
 * Broadcast_List_Table — announcement list table for WP admin.
 *
 * Extends WP_List_Table (confirmed pattern from BuddyBoss Platform
 * class-bp-messages-notices-list-table.php).
 */

defined( 'ABSPATH' ) || exit;

// WP_List_Table is not auto-loaded — require explicitly (Pitfall 3).
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Broadcast_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'announcement',
			'plural'   => 'announcements',
			'ajax'     => false,
		) );
	}

	/**
	 * Column definitions.
	 *
	 * @return array Column slugs => display labels.
	 */
	public function get_columns(): array {
		return array(
			'cb'          => '<input type="checkbox">',
			'name'        => __( 'Name', 'broadcast' ),
			'type'        => __( 'Type', 'broadcast' ),
			'status'      => __( 'Status', 'broadcast' ),
			'impressions' => __( 'Impressions', 'broadcast' ),
			'cta_clicks'  => __( 'CTA Clicks', 'broadcast' ),
		);
	}

	/**
	 * Fetch announcements with analytics counts in one JOIN query (no N+1).
	 */
	public function prepare_items(): void {
		global $wpdb;

		$this->_column_headers = array( $this->get_columns(), array(), array() );

		// Single JOIN query: announcements + DISTINCT impression count + CTA click count.
		$results = $wpdb->get_results(
			"SELECT a.*,
			  COALESCE(imp.cnt, 0) AS impression_count,
			  COALESCE(cta.cnt, 0) AS cta_click_count
			FROM {$wpdb->prefix}broadcast_announcements a
			LEFT JOIN (
			  SELECT announcement_id, COUNT(DISTINCT user_id) AS cnt
			  FROM {$wpdb->prefix}broadcast_analytics_events
			  WHERE event_type = 'impression'
			  GROUP BY announcement_id
			) imp ON imp.announcement_id = a.id
			LEFT JOIN (
			  SELECT announcement_id, COUNT(*) AS cnt
			  FROM {$wpdb->prefix}broadcast_analytics_events
			  WHERE event_type = 'cta_click'
			  GROUP BY announcement_id
			) cta ON cta.announcement_id = a.id
			ORDER BY a.id DESC"
		);

		$this->items = $results ?: array();

		$this->set_pagination_args( array(
			'total_items' => count( $this->items ),
			'per_page'    => 20,
		) );
	}

	/**
	 * Checkbox column for bulk actions.
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="announcement[]" value="%d">',
			(int) $item->id
		);
	}

	/**
	 * Name column with row actions.
	 */
	protected function column_name( $item ): string {
		$base_url   = admin_url( 'admin.php?page=broadcast' );
		$edit_url   = add_query_arg( array( 'action' => 'edit', 'id' => $item->id ), $base_url );
		$delete_url = add_query_arg( array( 'action' => 'delete', 'id' => $item->id, '_wpnonce' => wp_create_nonce( 'broadcast_delete_' . $item->id ) ), $base_url );

		$view_url = add_query_arg( array( 'action' => 'view', 'id' => $item->id ), $base_url );

		$actions = array(
			'view'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $view_url ),
				esc_html__( 'View', 'broadcast' )
			),
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'broadcast' )
			),
			'delete' => sprintf(
				'<a href="%s" class="broadcast-delete-link" style="color:#d63638" data-confirm="%s">%s</a>',
				esc_url( $delete_url ),
				esc_attr__( 'Delete announcement?', 'broadcast' ),
				esc_html__( 'Delete Announcement', 'broadcast' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $view_url ),
			esc_html( $item->name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Type column.
	 */
	protected function column_type( $item ): string {
		return esc_html( ucfirst( $item->type ) );
	}

	/**
	 * Status column: badge + enable/disable toggle.
	 */
	protected function column_status( $item ): string {
		$status = broadcast_get_announcement_status( $item );
		$labels = array(
			'active'    => __( 'Active', 'broadcast' ),
			'scheduled' => __( 'Scheduled', 'broadcast' ),
			'ended'     => __( 'Ended', 'broadcast' ),
			'disabled'  => __( 'Disabled', 'broadcast' ),
		);
		$label      = $labels[ $status ] ?? ucfirst( $status );
		$badge_html = sprintf(
			'<span class="broadcast-status-badge broadcast-status-%s"><span class="screen-reader-text">%s</span> %s</span>',
			esc_attr( $status ),
			esc_html__( 'Status:', 'broadcast' ),
			esc_html( $label )
		);

		$is_enabled  = (int) $item->enabled;
		$toggle_html = sprintf(
			'<button type="button" class="broadcast-toggle" role="switch" aria-checked="%s" data-id="%d" data-nonce="%s" title="%s">
				<span class="screen-reader-text">%s</span>
			</button>',
			$is_enabled ? 'true' : 'false',
			(int) $item->id,
			esc_attr( wp_create_nonce( 'broadcast_toggle' ) ),
			$is_enabled ? esc_attr__( 'Disable announcement', 'broadcast' ) : esc_attr__( 'Enable announcement', 'broadcast' ),
			$is_enabled ? esc_html__( 'Enabled', 'broadcast' ) : esc_html__( 'Disabled', 'broadcast' )
		);

		return $badge_html . ' ' . $toggle_html;
	}

	/**
	 * Impressions column — unique users who saw the announcement.
	 */
	protected function column_impressions( $item ): string {
		return '<span style="display:block;text-align:right">' . number_format_i18n( (int) $item->impression_count ) . '</span>';
	}

	/**
	 * CTA Clicks column.
	 */
	protected function column_cta_clicks( $item ): string {
		return '<span style="display:block;text-align:right">' . number_format_i18n( (int) $item->cta_click_count ) . '</span>';
	}

	/**
	 * Default column handler.
	 */
	protected function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '' );
	}

	/**
	 * Empty state message.
	 */
	public function no_items(): void {
		echo '<strong>' . esc_html__( 'No announcements yet', 'broadcast' ) . '</strong>';
		echo '<p>' . esc_html__( 'Create your first announcement to start reaching the right community members. Click "Add New Announcement" to get started.', 'broadcast' ) . '</p>';
	}
}
