<?php
/**
 * BuddyBoss Events Admin.
 *
 * @package BuddyBoss\Events\Admin
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
		'none',
		3.3
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
		__( 'Add New Event', 'buddyboss' ),
		__( 'Add New', 'buddyboss' ),
		'manage_options',
		'bp-events-new',
		'bp_events_admin_new_page'
	);

	add_submenu_page(
		'bp-events',
		__( 'Settings', 'buddyboss' ),
		__( 'Settings', 'buddyboss' ),
		'manage_options',
		'bp-events-settings',
		'bp_events_admin_settings_page'
	);

	// Revenue menu hidden until Stripe Connect is implemented.
	// add_submenu_page( 'bp-events', __( 'Events Revenue', 'buddyboss' ), __( 'Revenue', 'buddyboss' ), 'manage_options', 'bp-events-revenue', 'bp_events_admin_revenue_page' );
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

	wp_enqueue_script(
		'bp-events-create',
		plugins_url( 'src/bp-events/assets/js/bp-events-create.js', BP_EVENTS_PLUGIN_FILE ),
		array( 'jquery' ),
		BP_EVENTS_VERSION,
		true
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Add New Event', 'buddyboss' ); ?></h1>
		<div id="bb-rl-event-create-form"></div>
		<script type="text/javascript">
			window.bpEventsCreate = {
				nonce:       '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
				restUrl:     '<?php echo esc_js( esc_url_raw( rest_url( 'buddyboss/v1/events' ) ) ); ?>',
				currentUser: <?php echo (int) get_current_user_id(); ?>,
				groupId:     0,
				timezones:   <?php echo wp_json_encode( timezone_identifiers_list() ); ?>,
				moderation:  <?php echo wp_json_encode( bp_events_moderation_enabled() ); ?>,
				i18n:        {
					step1Title:    '<?php echo esc_js( __( 'Event Type', 'buddyboss' ) ); ?>',
					step2Title:    '<?php echo esc_js( __( 'Basic Details', 'buddyboss' ) ); ?>',
					step3Title:    '<?php echo esc_js( __( 'Date & Time', 'buddyboss' ) ); ?>',
					step4Title:    '<?php echo esc_js( __( 'Location / Virtual', 'buddyboss' ) ); ?>',
					step5Title:    '<?php echo esc_js( __( 'Recurrence', 'buddyboss' ) ); ?>',
					step6Title:    '<?php echo esc_js( __( 'RSVP Settings', 'buddyboss' ) ); ?>',
					step7Title:    '<?php echo esc_js( __( 'Review & Publish', 'buddyboss' ) ); ?>',
					submitPending: '<?php echo esc_js( __( 'Event submitted for review.', 'buddyboss' ) ); ?>',
					submitPublish: '<?php echo esc_js( __( 'Event created successfully!', 'buddyboss' ) ); ?>',
				}
			};
		</script>
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

// ---------------------------------------------------------------------------
// Settings — tab definitions
// ---------------------------------------------------------------------------

/**
 * Return the available settings tabs.
 *
 * @return array  slug => label
 */
function bp_events_admin_settings_tabs() {
	return array(
		'general'     => __( 'General', 'buddyboss' ),
		'display'     => __( 'Display', 'buddyboss' ),
		'directory'   => __( 'Directory', 'buddyboss' ),
		'permissions' => __( 'Permissions', 'buddyboss' ),
		'email'       => __( 'Email', 'buddyboss' ),
		'advanced'    => __( 'Advanced', 'buddyboss' ),
	);
}

// ---------------------------------------------------------------------------
// Settings — registration (one option-group per tab)
// ---------------------------------------------------------------------------

/**
 * Register settings for all tabs via the WordPress Settings API.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_register_settings() {

	// ---- GENERAL -----------------------------------------------------------
	$page = 'bp-events-settings-general';

	add_settings_section( 'bp_events_general', '', '__return_false', $page );

	register_setting( $page, 'bb-events-enabled' );
	add_settings_field( 'bb-events-enabled', __( 'Enable Events', 'buddyboss' ), 'bp_events_setting_events_enabled', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_root_slug', 'sanitize_title' );
	add_settings_field( 'bb_events_root_slug', __( 'Events Directory Slug', 'buddyboss' ), 'bp_events_setting_root_slug', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_default_calendar_view', 'sanitize_text_field' );
	add_settings_field( 'bb_events_default_calendar_view', __( 'Default Calendar View', 'buddyboss' ), 'bp_events_setting_calendar_view', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_date_format', 'sanitize_text_field' );
	add_settings_field( 'bb_events_date_format', __( 'Date Format', 'buddyboss' ), 'bp_events_setting_date_format', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_time_format', 'sanitize_text_field' );
	add_settings_field( 'bb_events_time_format', __( 'Time Format', 'buddyboss' ), 'bp_events_setting_time_format', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_primary_color', 'sanitize_hex_color' );
	add_settings_field( 'bb_events_primary_color', __( 'Primary Color', 'buddyboss' ), 'bp_events_setting_primary_color', $page, 'bp_events_general' );

	register_setting( $page, 'bb_events_secondary_color', 'sanitize_hex_color' );
	add_settings_field( 'bb_events_secondary_color', __( 'Secondary Color', 'buddyboss' ), 'bp_events_setting_secondary_color', $page, 'bp_events_general' );

	// ---- DISPLAY -----------------------------------------------------------
	$page = 'bp-events-settings-display';

	add_settings_section( 'bp_events_display', '', '__return_false', $page );

	$display_toggles = array(
		'bb_events_show_date'           => __( 'Event Date', 'buddyboss' ),
		'bb_events_show_time'           => __( 'Event Time', 'buddyboss' ),
		'bb_events_show_location'       => __( 'Event Location', 'buddyboss' ),
		'bb_events_show_attendee_count' => __( 'Attendee Count', 'buddyboss' ),
		'bb_events_show_countdown'      => __( 'Countdown Timer', 'buddyboss' ),
		'bb_events_show_related'        => __( 'Related Events', 'buddyboss' ),
		'bb_events_schema_markup'       => __( 'Schema Mark-up', 'buddyboss' ),
		'bb_events_show_social_share'   => __( 'Social Media Sharing', 'buddyboss' ),
	);
	foreach ( $display_toggles as $key => $label ) {
		register_setting( $page, $key );
		add_settings_field( $key, $label, 'bp_events_setting_display_toggle', $page, 'bp_events_display', array( 'key' => $key, 'label' => $label ) );
	}

	register_setting( $page, 'bb_events_related_per_page', 'absint' );
	add_settings_field( 'bb_events_related_per_page', __( 'Related Events Per Page', 'buddyboss' ), 'bp_events_setting_related_per_page', $page, 'bp_events_display' );

	// ---- DIRECTORY ---------------------------------------------------------
	$page = 'bp-events-settings-directory';

	add_settings_section( 'bp_events_directory', '', '__return_false', $page );

	register_setting( $page, 'bb_events_per_page', 'absint' );
	add_settings_field( 'bb_events_per_page', __( 'Events Per Page', 'buddyboss' ), 'bp_events_setting_per_page', $page, 'bp_events_directory' );

	register_setting( $page, 'bb_events_include_search' );
	add_settings_field( 'bb_events_include_search', __( 'Include in Search', 'buddyboss' ), 'bp_events_setting_include_search', $page, 'bp_events_directory' );

	register_setting( $page, 'bb_events_sort_by', 'sanitize_text_field' );
	add_settings_field( 'bb_events_sort_by', __( 'Sort Events By', 'buddyboss' ), 'bp_events_setting_sort_by', $page, 'bp_events_directory' );

	register_setting( $page, 'bb_events_order', 'sanitize_text_field' );
	add_settings_field( 'bb_events_order', __( 'Order', 'buddyboss' ), 'bp_events_setting_order', $page, 'bp_events_directory' );

	// ---- PERMISSIONS -------------------------------------------------------
	$page = 'bp-events-settings-permissions';

	add_settings_section( 'bp_events_permissions', '', '__return_false', $page );

	register_setting( $page, 'bb_events_creation_roles', 'bp_events_sanitize_creation_roles' );
	add_settings_field( 'bb_events_creation_roles', __( 'Who Can Create Events', 'buddyboss' ), 'bp_events_setting_creation_roles', $page, 'bp_events_permissions' );

	register_setting( $page, 'bb_events_moderation_enabled' );
	add_settings_field( 'bb_events_moderation_enabled', __( 'Event Moderation', 'buddyboss' ), 'bp_events_setting_moderation', $page, 'bp_events_permissions' );

	// ---- EMAIL -------------------------------------------------------------
	$page = 'bp-events-settings-email';

	add_settings_section( 'bp_events_email', '', '__return_false', $page );

	register_setting( $page, 'bb_events_rsvp_email_enabled' );
	add_settings_field( 'bb_events_rsvp_email_enabled', __( 'RSVP Invitation Email', 'buddyboss' ), 'bp_events_setting_rsvp_email_enabled', $page, 'bp_events_email' );

	register_setting( $page, 'bb_events_rsvp_email_from', 'sanitize_email' );
	add_settings_field( 'bb_events_rsvp_email_from', __( 'From Email', 'buddyboss' ), 'bp_events_setting_rsvp_email_from', $page, 'bp_events_email' );

	register_setting( $page, 'bb_events_rsvp_email_subject', 'sanitize_text_field' );
	add_settings_field( 'bb_events_rsvp_email_subject', __( 'Email Subject', 'buddyboss' ), 'bp_events_setting_rsvp_email_subject', $page, 'bp_events_email' );

	register_setting( $page, 'bb_events_rsvp_email_body', 'wp_kses_post' );
	add_settings_field( 'bb_events_rsvp_email_body', __( 'Email Body', 'buddyboss' ), 'bp_events_setting_rsvp_email_body', $page, 'bp_events_email' );

	// ---- ADVANCED ----------------------------------------------------------
	$page = 'bp-events-settings-advanced';

	add_settings_section( 'bp_events_advanced', '', '__return_false', $page );

	register_setting( $page, 'bb_events_public_group_site_calendar' );
	add_settings_field( 'bb_events_public_group_site_calendar', __( 'Public Group Site Calendar', 'buddyboss' ), 'bp_events_admin_setting_callback_public_group_site_calendar', $page, 'bp_events_advanced' );

	register_setting( $page, 'bb_events_attendees_registration' );
	add_settings_field( 'bb_events_attendees_registration', __( 'Attendees Registration', 'buddyboss' ), 'bp_events_setting_attendees_registration', $page, 'bp_events_advanced' );

	register_setting( $page, 'bb_events_speaker_show_email' );
	add_settings_field( 'bb_events_speaker_show_email', __( 'Show Speaker Email', 'buddyboss' ), 'bp_events_setting_speaker_show_email', $page, 'bp_events_advanced' );

	register_setting( $page, 'bb_events_speaker_user_notification' );
	add_settings_field( 'bb_events_speaker_user_notification', __( 'Speaker User Notification', 'buddyboss' ), 'bp_events_setting_speaker_user_notification', $page, 'bp_events_advanced' );
}
add_action( 'admin_init', 'bp_events_admin_register_settings' );

// ---------------------------------------------------------------------------
// Settings page renderer
// ---------------------------------------------------------------------------

/**
 * Render the tabbed Settings admin page.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs        = bp_events_admin_settings_tabs();
	$current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? sanitize_key( $_GET['tab'] ) : 'general';
	$option_group = 'bp-events-settings-' . $current_tab;
	$base_url    = admin_url( 'admin.php?page=bp-events-settings' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Events Settings', 'buddyboss' ); ?></h1>

		<nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom:20px;">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
				   class="nav-tab<?php echo $current_tab === $slug ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="options.php">
			<?php
			settings_fields( $option_group );
			do_settings_sections( $option_group );
			submit_button( __( 'Save Changes', 'buddyboss' ) );
			?>
		</form>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Settings field callbacks
// ---------------------------------------------------------------------------

function bp_events_setting_events_enabled() {
	?>
	<label>
		<input name="bb-events-enabled" id="bb-events-enabled" type="checkbox" value="1"
			<?php checked( bp_is_active( 'events' ) ); ?> />
		<?php esc_html_e( 'Enable the Events component for your community', 'buddyboss' ); ?>
	</label>
	<?php
}

function bp_events_setting_root_slug() {
	?>
	<input name="bb_events_root_slug" id="bb_events_root_slug" type="text"
		value="<?php echo esc_attr( bp_get_option( 'bb_events_root_slug', 'events' ) ); ?>"
		class="regular-text" />
	<p class="description">
		<?php printf( esc_html__( 'e.g. %s', 'buddyboss' ), '<code>' . esc_url( trailingslashit( home_url() ) ) . 'events</code>' ); ?>
	</p>
	<?php
}

function bp_events_setting_calendar_view() {
	$current = bb_get_events_default_calendar_view();
	$options = array( 'month' => __( 'Month', 'buddyboss' ), 'week' => __( 'Week', 'buddyboss' ), 'list' => __( 'List', 'buddyboss' ) );
	?>
	<select name="bb_events_default_calendar_view" id="bb_events_default_calendar_view">
		<?php foreach ( $options as $v => $l ) : ?>
			<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $current, $v ); ?>><?php echo esc_html( $l ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

function bp_events_setting_date_format() {
	$value = get_option( 'bb_events_date_format', get_option( 'date_format' ) );
	?>
	<input name="bb_events_date_format" type="text" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php printf( esc_html__( 'Preview: %s', 'buddyboss' ), '<code>' . date_i18n( $value ) . '</code>' ); ?></p>
	<?php
}

function bp_events_setting_time_format() {
	$value = get_option( 'bb_events_time_format', get_option( 'time_format' ) );
	?>
	<input name="bb_events_time_format" type="text" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php printf( esc_html__( 'Preview: %s', 'buddyboss' ), '<code>' . date_i18n( $value ) . '</code>' ); ?></p>
	<?php
}

function bp_events_setting_primary_color() {
	$value = get_option( 'bb_events_primary_color', '' );
	?>
	<input name="bb_events_primary_color" type="color" value="<?php echo esc_attr( $value ?: '#0073aa' ); ?>" />
	<?php
}

function bp_events_setting_secondary_color() {
	$value = get_option( 'bb_events_secondary_color', '' );
	?>
	<input name="bb_events_secondary_color" type="color" value="<?php echo esc_attr( $value ?: '#005177' ); ?>" />
	<?php
}

function bp_events_setting_display_toggle( $args ) {
	$key   = $args['key'];
	$label = $args['label'];
	$value = get_option( $key, '1' );
	$descriptions = array(
		'bb_events_show_date'           => __( 'Show event date on event page.', 'buddyboss' ),
		'bb_events_show_time'           => __( 'Show event time on event page.', 'buddyboss' ),
		'bb_events_show_location'       => __( 'Show event location on event page.', 'buddyboss' ),
		'bb_events_show_attendee_count' => __( 'Show attendee count on event page.', 'buddyboss' ),
		'bb_events_show_countdown'      => __( 'Show countdown timer on event page.', 'buddyboss' ),
		'bb_events_show_related'        => __( 'Show related events on event page.', 'buddyboss' ),
		'bb_events_schema_markup'       => __( 'Generate JSON-LD schema markup for search engines.', 'buddyboss' ),
		'bb_events_show_social_share'   => __( 'Show social media share icons on event page.', 'buddyboss' ),
	);
	?>
	<label>
		<input name="<?php echo esc_attr( $key ); ?>" type="checkbox" value="1" <?php checked( $value, '1' ); ?> />
		<?php echo esc_html( $descriptions[ $key ] ?? '' ); ?>
	</label>
	<?php
}

function bp_events_setting_related_per_page() {
	$value = (int) get_option( 'bb_events_related_per_page', 3 );
	?>
	<input name="bb_events_related_per_page" type="number" value="<?php echo esc_attr( $value ); ?>" min="1" max="20" class="small-text" />
	<?php
}

function bp_events_setting_per_page() {
	$value = (int) get_option( 'bb_events_per_page', 12 );
	?>
	<input name="bb_events_per_page" type="number" value="<?php echo esc_attr( $value ); ?>" min="1" max="100" class="small-text" />
	<p class="description"><?php esc_html_e( 'Number of events to show on the directory page.', 'buddyboss' ); ?></p>
	<?php
}

function bp_events_setting_include_search() {
	$value = get_option( 'bb_events_include_search', '1' );
	?>
	<label style="margin-right:16px;">
		<input type="radio" name="bb_events_include_search" value="1" <?php checked( $value, '1' ); ?> />
		<?php esc_html_e( 'Yes — include events in search results', 'buddyboss' ); ?>
	</label>
	<label>
		<input type="radio" name="bb_events_include_search" value="0" <?php checked( $value, '0' ); ?> />
		<?php esc_html_e( 'No — exclude events from search results', 'buddyboss' ); ?>
	</label>
	<?php
}

function bp_events_setting_sort_by() {
	$value = get_option( 'bb_events_sort_by', 'upcoming' );
	$options = array(
		'upcoming' => __( 'Upcoming', 'buddyboss' ),
		'expired'  => __( 'Expired', 'buddyboss' ),
	);
	?>
	<select name="bb_events_sort_by">
		<?php foreach ( $options as $v => $l ) : ?>
			<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $value, $v ); ?>><?php echo esc_html( $l ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Filter directory events based on upcoming or expired date.', 'buddyboss' ); ?></p>
	<?php
}

function bp_events_setting_order() {
	$value = get_option( 'bb_events_order', 'ASC' );
	?>
	<label style="margin-right:16px;">
		<input type="radio" name="bb_events_order" value="ASC" <?php checked( $value, 'ASC' ); ?> />
		<?php esc_html_e( 'Ascending', 'buddyboss' ); ?>
	</label>
	<label>
		<input type="radio" name="bb_events_order" value="DESC" <?php checked( $value, 'DESC' ); ?> />
		<?php esc_html_e( 'Descending', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Sanitize creation roles array.
 */
function bp_events_sanitize_creation_roles( $input ) {
	$allowed = array( 'admins', 'organizers', 'members' );
	if ( ! is_array( $input ) ) {
		return array( 'admins' );
	}
	return array_values( array_intersect( $input, $allowed ) );
}

function bp_events_setting_creation_roles() {
	$saved = get_option( 'bb_events_creation_roles', array( 'admins' ) );
	if ( ! is_array( $saved ) ) {
		$saved = array( $saved );
	}
	$options = array(
		'admins'      => __( 'Site admins', 'buddyboss' ),
		'organizers'  => __( 'Group owners and moderators', 'buddyboss' ),
		'members'     => __( 'All members', 'buddyboss' ),
	);
	?>
	<fieldset>
		<?php foreach ( $options as $value => $label ) : ?>
			<label style="display:block;margin-bottom:6px;">
				<input type="checkbox" name="bb_events_creation_roles[]"
					value="<?php echo esc_attr( $value ); ?>"
					<?php checked( in_array( $value, $saved, true ) ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>
	<p class="description"><?php esc_html_e( 'Select all roles that are allowed to create events.', 'buddyboss' ); ?></p>
	<?php
}

function bp_events_setting_moderation() {
	$value = bp_events_moderation_enabled() ? '1' : '0';
	$options = array(
		'0' => __( 'Auto-publish — events go live immediately', 'buddyboss' ),
		'1' => __( 'Requires review — admin must approve before publishing', 'buddyboss' ),
	);
	?>
	<select name="bb_events_moderation_enabled">
		<?php foreach ( $options as $v => $l ) : ?>
			<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $value, $v ); ?>><?php echo esc_html( $l ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

function bp_events_setting_rsvp_email_enabled() {
	$value = get_option( 'bb_events_rsvp_email_enabled', '0' );
	?>
	<label>
		<input name="bb_events_rsvp_email_enabled" type="checkbox" value="1" <?php checked( $value, '1' ); ?> />
		<?php esc_html_e( 'Send RSVP invitation emails to attendees', 'buddyboss' ); ?>
	</label>
	<?php
}

function bp_events_setting_rsvp_email_from() {
	$value = get_option( 'bb_events_rsvp_email_from', get_option( 'admin_email' ) );
	?>
	<input name="bb_events_rsvp_email_from" type="email" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<?php
}

function bp_events_setting_rsvp_email_subject() {
	$value = get_option( 'bb_events_rsvp_email_subject', __( 'RSVP request', 'buddyboss' ) );
	?>
	<input name="bb_events_rsvp_email_subject" type="text" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
	<?php
}

function bp_events_setting_rsvp_email_body() {
	$value = get_option( 'bb_events_rsvp_email_body', __( 'We received your RSVP request.', 'buddyboss' ) );
	?>
	<textarea name="bb_events_rsvp_email_body" rows="6" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Available placeholders:', 'buddyboss' ); ?>
		<code>{%site_name%}</code> <code>{%site_link%}</code> <code>{%event_title%}</code> <code>{%event_link%}</code>
	</p>
	<?php
}

function bp_events_setting_attendees_registration() {
	$value = get_option( 'bb_events_attendees_registration', '0' );
	?>
	<label>
		<input name="bb_events_attendees_registration" type="checkbox" value="1" <?php checked( $value, '1' ); ?> />
		<?php esc_html_e( 'Allow attendance tracking and unique ticket management', 'buddyboss' ); ?>
	</label>
	<?php
}

function bp_events_setting_speaker_show_email() {
	$value = get_option( 'bb_events_speaker_show_email', '0' );
	?>
	<label>
		<input name="bb_events_speaker_show_email" type="checkbox" value="1" <?php checked( $value, '1' ); ?> />
		<?php esc_html_e( 'Show speaker email address on the speaker details page', 'buddyboss' ); ?>
	</label>
	<?php
}

function bp_events_setting_speaker_user_notification() {
	$value = get_option( 'bb_events_speaker_user_notification', '0' );
	?>
	<label>
		<input name="bb_events_speaker_user_notification" type="checkbox" value="1" <?php checked( $value, '1' ); ?> />
		<?php esc_html_e( 'Send new speakers an email notification about their account', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Render the public group site calendar toggle (used in Advanced tab).
 */
function bp_events_admin_setting_callback_public_group_site_calendar() {
	?>
	<label>
		<input name="bb_events_public_group_site_calendar" id="bb_events_public_group_site_calendar" type="checkbox" value="1"
			<?php checked( bb_events_allow_public_group_site_calendar() ); ?> />
		<?php esc_html_e( 'Allow public groups to show their events on the site-wide calendar', 'buddyboss' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Private and hidden group events are never shown on the site calendar regardless of this setting.', 'buddyboss' ); ?>
	</p>
	<?php
}

/** Category Icon Upload ******************************************************/

/**
 * Render icon upload field on Add New Event Category form.
 *
 * @since BuddyBoss Events 2.0.0
 */
function bp_events_category_add_icon_field() {
	?>
	<div class="form-field">
		<label><?php esc_html_e( 'Category Icon', 'buddyboss' ); ?></label>
		<div id="bb-event-cat-icon-preview"></div>
		<input type="hidden" name="bb_event_cat_icon_id" id="bb-event-cat-icon-id" value="" />
		<button type="button" class="button" id="bb-event-cat-icon-upload">
			<?php esc_html_e( 'Upload Icon', 'buddyboss' ); ?>
		</button>
		<button type="button" class="button" id="bb-event-cat-icon-remove" style="display:none;">
			<?php esc_html_e( 'Remove Icon', 'buddyboss' ); ?>
		</button>
		<p class="description"><?php esc_html_e( 'Optional icon or image for this category.', 'buddyboss' ); ?></p>
	</div>
	<?php
}
add_action( 'bb_event_category_add_form_fields', 'bp_events_category_add_icon_field' );

/**
 * Render icon upload field on Edit Event Category form.
 *
 * @since BuddyBoss Events 2.0.0
 *
 * @param WP_Term $term Current term object.
 */
function bp_events_category_edit_icon_field( $term ) {
	$icon_id  = get_term_meta( $term->term_id, '_bb_event_cat_icon_id', true );
	$icon_url = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
	?>
	<tr class="form-field">
		<th scope="row"><label><?php esc_html_e( 'Category Icon', 'buddyboss' ); ?></label></th>
		<td>
			<div id="bb-event-cat-icon-preview">
				<?php if ( $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" style="max-width:80px;max-height:80px;" />
				<?php endif; ?>
			</div>
			<input type="hidden" name="bb_event_cat_icon_id" id="bb-event-cat-icon-id" value="<?php echo esc_attr( $icon_id ); ?>" />
			<button type="button" class="button" id="bb-event-cat-icon-upload">
				<?php esc_html_e( 'Upload Icon', 'buddyboss' ); ?>
			</button>
			<button type="button" class="button" id="bb-event-cat-icon-remove" style="<?php echo $icon_id ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Remove Icon', 'buddyboss' ); ?>
			</button>
			<p class="description"><?php esc_html_e( 'Optional icon or image for this category.', 'buddyboss' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'bb_event_category_edit_form_fields', 'bp_events_category_edit_icon_field' );

/**
 * Save category icon attachment ID as term meta.
 *
 * @since BuddyBoss Events 2.0.0
 *
 * @param int $term_id Term ID.
 */
function bp_events_save_category_icon( $term_id ) {
	if ( isset( $_POST['bb_event_cat_icon_id'] ) ) {
		$icon_id = absint( $_POST['bb_event_cat_icon_id'] );
		if ( $icon_id ) {
			update_term_meta( $term_id, '_bb_event_cat_icon_id', $icon_id );
		} else {
			delete_term_meta( $term_id, '_bb_event_cat_icon_id' );
		}
	}
}
add_action( 'created_bb_event_category', 'bp_events_save_category_icon' );
add_action( 'edited_bb_event_category', 'bp_events_save_category_icon' );

/**
 * Enqueue WP media uploader on event category admin screens.
 *
 * @since BuddyBoss Events 2.0.0
 *
 * @param string $hook Current admin page hook.
 */
function bp_events_category_admin_scripts( $hook ) {
	if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'bb_event_category' !== $screen->taxonomy ) {
		return;
	}

	wp_enqueue_media();

	$inline_js = "
	(function($){
		$(document).ready(function(){
			var frame;
			$('#bb-event-cat-icon-upload').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: '" . esc_js( __( 'Select Category Icon', 'buddyboss' ) ) . "',
					button: { text: '" . esc_js( __( 'Use as Icon', 'buddyboss' ) ) . "' },
					multiple: false
				});
				frame.on('select', function(){
					var attachment = frame.state().get('selection').first().toJSON();
					$('#bb-event-cat-icon-id').val(attachment.id);
					$('#bb-event-cat-icon-preview').html('<img src=\"' + attachment.url + '\" style=\"max-width:80px;max-height:80px;\" />');
					$('#bb-event-cat-icon-remove').show();
				});
				frame.open();
			});
			$('#bb-event-cat-icon-remove').on('click', function(e){
				e.preventDefault();
				$('#bb-event-cat-icon-id').val('');
				$('#bb-event-cat-icon-preview').html('');
				$(this).hide();
			});
		});
	})(jQuery);
	";

	wp_add_inline_script( 'media-editor', $inline_js );
}
add_action( 'admin_enqueue_scripts', 'bp_events_category_admin_scripts' );

// ---------------------------------------------------------------------------
// Admin menu icon — bb-icons glyph, matching BuddyBoss Platform style.
// Output on every admin page so the icon shows in the sidebar everywhere.
// ---------------------------------------------------------------------------

/**
 * Render the bb-icons glyph for the BB Events menu item.
 *
 * Uses the same \edc8 codepoint and font-family as BuddyBoss Platform and
 * BuddyBoss CRM. The icon is 'none' in add_menu_page so WordPress does not
 * output a dashicon; this CSS replaces it with the bb-icons glyph.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_menu_icon() {
	?>
	<style type="text/css">
		#adminmenu li.toplevel_page_bp-events .wp-menu-image:before {
			content: "\edc8";
			font-family: "bb-icons";
			font-style: normal;
			font-weight: 300;
			speak: none;
			display: inline-block;
			text-decoration: inherit;
			width: 1em;
			margin-right: 0.2em;
			text-align: center;
			font-variant: normal;
			text-transform: none;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'bp_events_admin_menu_icon' );

// Legacy aliases kept so existing code that calls the old callback names still works.
function bp_events_admin_setting_callback_events_enabled()       { bp_events_setting_events_enabled(); }
function bp_events_admin_setting_callback_root_slug()            { bp_events_setting_root_slug(); }
function bp_events_admin_setting_callback_creation_permission()  { bp_events_setting_creation_roles(); }
function bp_events_admin_setting_callback_moderation()           { bp_events_setting_moderation(); }
function bp_events_admin_setting_callback_default_calendar_view(){ bp_events_setting_calendar_view(); }
