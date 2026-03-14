<?php
/**
 * BuddyBoss Events Admin.
 *
 * @package BuddyBoss\Events\Admin
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Register top-level admin menu.
add_action( 'bp_admin_menu', 'bp_events_admin_menu', 20 );

/**
 * Register the Events admin menu.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_menu() {
	add_menu_page(
		__( 'Events', 'buddyboss' ),
		__( 'Events', 'buddyboss' ),
		'manage_options',
		'bp-events',
		'bp_events_admin_page',
		'dashicons-calendar-alt',
		59
	);

	add_submenu_page(
		'bp-events',
		__( 'All Events', 'buddyboss' ),
		__( 'All Events', 'buddyboss' ),
		'manage_options',
		'bp-events',
		'bp_events_admin_page'
	);

	add_submenu_page(
		'bp-events',
		__( 'Add New', 'buddyboss' ),
		__( 'Add New', 'buddyboss' ),
		'manage_options',
		'bp-events-new',
		'bp_events_admin_new_page'
	);

	add_submenu_page(
		'bp-events',
		__( 'Revenue', 'buddyboss' ),
		__( 'Revenue', 'buddyboss' ),
		'manage_options',
		'bp-events-revenue',
		'bp_events_admin_revenue_page'
	);
}

/**
 * Render the All Events admin list table.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$list_table = new BP_Events_List_Table();
	$list_table->prepare_items();

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Events', 'buddyboss' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bp-events-new' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add New', 'buddyboss' ); ?>
		</a>
		<hr class="wp-header-end">
		<?php $list_table->views(); ?>
		<form method="get">
			<input type="hidden" name="page" value="bp-events" />
			<?php $list_table->search_box( __( 'Search Events', 'buddyboss' ), 'event' ); ?>
			<?php $list_table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render the Add New Event admin page.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_new_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Add New Event', 'buddyboss' ); ?></h1>
		<div id="bp-events-admin-create">
			<p><?php esc_html_e( 'Loading event editor...', 'buddyboss' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Render the Revenue admin page.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_revenue_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$stats = bp_events_admin_get_event_counts();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Events Revenue', 'buddyboss' ); ?></h1>
		<div id="bp-events-revenue-dashboard">
			<div class="bb-events-stats">
				<ul>
					<li><?php printf( /* translators: %d: count */ esc_html__( 'Total Events: %d', 'buddyboss' ), esc_html( $stats['total'] ) ); ?></li>
					<li><?php printf( /* translators: %d: count */ esc_html__( 'Published: %d', 'buddyboss' ), esc_html( $stats['published'] ) ); ?></li>
					<li><?php printf( /* translators: %d: count */ esc_html__( 'Pending Approval: %d', 'buddyboss' ), esc_html( $stats['pending'] ) ); ?></li>
					<li><?php printf( /* translators: %d: count */ esc_html__( 'Draft: %d', 'buddyboss' ), esc_html( $stats['draft'] ) ); ?></li>
				</ul>
			</div>
			<p class="description">
				<?php esc_html_e( 'Revenue dashboard will be available in Phase 2 when Stripe Connect is configured.', 'buddyboss' ); ?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Retrieve event count statistics for the admin revenue page.
 *
 * Counts are broken down by status and aggregated into a total.
 * Results are cached in object cache for 5 minutes to avoid repeated
 * database queries on every admin page load.
 *
 * Only top-level (non-child) events are counted — child occurrence rows
 * (parent_event_id IS NOT NULL) are excluded from all counts.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @return array {
 *     Event count statistics.
 *     @type int $total     Total top-level events (all statuses).
 *     @type int $published Count of published events.
 *     @type int $pending   Count of pending events.
 *     @type int $draft     Count of draft events.
 * }
 */
function bp_events_admin_get_event_counts() {
	$cache_key   = 'bb_events_admin_stats';
	$cache_group = 'bp_events';

	$cached = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	$bp = buddypress();

	// Aggregate counts by status, excluding child occurrence rows.
	$rows = $wpdb->get_results(
		"SELECT status, COUNT(*) AS count
		FROM {$bp->events->table_name}
		WHERE parent_event_id IS NULL
		GROUP BY status"
	);

	$stats = array(
		'total'     => 0,
		'published' => 0,
		'pending'   => 0,
		'draft'     => 0,
	);

	if ( ! empty( $rows ) ) {
		foreach ( $rows as $row ) {
			$count = (int) $row->count;
			$stats['total'] += $count;

			if ( isset( $stats[ $row->status ] ) ) {
				$stats[ $row->status ] = $count;
			}
		}
	}

	wp_cache_set( $cache_key, $stats, $cache_group, 300 );

	return $stats;
}

/**
 * Settings callbacks — called by bp-admin-setting-events.php.
 */

/**
 * Render the Events component enable/disable field.
 */
function bp_events_admin_setting_callback_events_enabled() {
	?>
	<input name="bb-events-enabled" id="bb-events-enabled" type="checkbox" value="1"
		<?php checked( bp_is_active( 'events' ) ); ?> />
	<label for="bb-events-enabled">
		<?php esc_html_e( 'Enable the Events component for your community', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Render the events directory slug field.
 */
function bp_events_admin_setting_callback_root_slug() {
	?>
	<input name="bb_events_root_slug" id="bb_events_root_slug" type="text"
		value="<?php echo esc_attr( bp_get_option( 'bb_events_root_slug', 'events' ) ); ?>"
		class="regular-text" />
	<p class="description">
		<?php
		printf(
			/* translators: %s: site URL */
			esc_html__( 'The slug used for the events directory, e.g. %s', 'buddyboss' ),
			'<code>' . esc_url( trailingslashit( home_url() ) ) . '<strong>events</strong></code>'
		);
		?>
	</p>
	<?php
}

/**
 * Render the event creation permission field.
 */
function bp_events_admin_setting_callback_creation_permission() {
	$current = bb_get_events_creation_permission();
	$options = array(
		'admins'      => __( 'Site Administrators only', 'buddyboss' ),
		'organizers'  => __( 'Group Admins and Moderators only', 'buddyboss' ),
		'members'     => __( 'All logged-in members', 'buddyboss' ),
	);
	?>
	<select name="bb_events_creation_permission" id="bb_events_creation_permission">
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'Determine who is allowed to create events on your community.', 'buddyboss' ); ?>
	</p>
	<?php
}

/**
 * Render the moderation queue toggle field.
 */
function bp_events_admin_setting_callback_moderation() {
	?>
	<input name="bb_events_moderation_enabled" id="bb_events_moderation_enabled" type="checkbox" value="1"
		<?php checked( bp_events_moderation_enabled() ); ?> />
	<label for="bb_events_moderation_enabled">
		<?php esc_html_e( 'Require admin approval before events go live', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Render the public group site calendar toggle.
 */
function bp_events_admin_setting_callback_public_group_site_calendar() {
	?>
	<input name="bb_events_public_group_site_calendar" id="bb_events_public_group_site_calendar" type="checkbox" value="1"
		<?php checked( bb_events_allow_public_group_site_calendar() ); ?> />
	<label for="bb_events_public_group_site_calendar">
		<?php esc_html_e( 'Allow public groups to show their events on the site-wide calendar', 'buddyboss' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Private and hidden group events are never shown on the site calendar regardless of this setting.', 'buddyboss' ); ?>
	</p>
	<?php
}

/**
 * Render the default calendar view field.
 */
function bp_events_admin_setting_callback_default_calendar_view() {
	$current = bb_get_events_default_calendar_view();
	$options = array(
		'month' => __( 'Month', 'buddyboss' ),
		'week'  => __( 'Week', 'buddyboss' ),
		'list'  => __( 'List', 'buddyboss' ),
	);
	?>
	<select name="bb_events_default_calendar_view" id="bb_events_default_calendar_view">
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
