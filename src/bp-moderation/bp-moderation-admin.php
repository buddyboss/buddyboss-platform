<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Per_page screen option. Has to be hooked in extremely early.
if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-moderation' === $_REQUEST['page'] ) {
	add_filter( 'set-screen-option', 'bp_moderation_admin_screen_options', 10, 3 );
}

/**
 * Function to hook the admin screen.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $hook page name.
 */
function bp_moderation_admin_scripts( $hook ) {
	if ( 'buddyboss_page_bp-moderation' === $hook ) {
		wp_enqueue_script(
			'bp-moderation',
			buddypress()->plugin_url . 'bp-core/admin/js/moderation-page.js',
			array( 'jquery' ),
			buddypress()->version,
			true
		);

		wp_localize_script(
			'bp-moderation',
			'Bp_Moderation',
			array(
				'strings' => array(
					'confirm_msg'            => esc_js( __( 'Are you sure you?', 'buddyboss' ) ),
					'hide_label'             => esc_js( __( 'Hide', 'buddyboss' ) ),
					'unhide_label'           => esc_js( __( 'Unhide', 'buddyboss' ) ),
					'suspend_author_label'   => esc_js( __( 'Suspend Content Author', 'buddyboss' ) ),
					'unsuspend_author_label' => esc_js( __( 'Unsuspend Content Author', 'buddyboss' ) ),
					'suspend_member_label'   => esc_js( __( 'Suspend Member', 'buddyboss' ) ),
					'unsuspend_member_label' => esc_js( __( 'Unsuspend Member', 'buddyboss' ) ),
				),
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'bp_moderation_admin_scripts' );

/**
 * Register the Moderation component admin screen.
 *
 * @since BuddyBoss 2.0.0
 */
function bp_moderation_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		esc_html__( 'Moderation', 'buddyboss' ),
		esc_html__( 'Moderation', 'buddyboss' ),
		'bp_moderate',
		'bp-moderation',
		'bp_moderation_admin'
	);

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_moderation_admin_load' );
}

add_action( bp_core_admin_hook(), 'bp_moderation_add_admin_menu', 100 );

/**
 * Add moderation component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Moderation page to the array of these menu items.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param array $custom_menus The list of top-level BP menu items.
 *
 * @return array $custom_menus List of top-level BP menu items, with Moderation added.
 */
function bp_moderation_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-moderation' );

	return $custom_menus;
}

add_filter( 'bp_admin_menu_order', 'bp_moderation_admin_menu_order' );


/**
 * Set up the Moderation admin page.
 *
 * @since BuddyBoss 2.0.0
 */
function bp_moderation_admin_load() {
	global $bp_moderation_list_table;

	$doaction                = bp_admin_list_table_current_bulk_action();
	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_GET, 'content_type', FILTER_SANITIZE_STRING );

	if ( 'view' === $doaction && ! empty( $moderation_id ) && ! empty( $moderation_content_type ) && array_key_exists( $moderation_content_type, bp_moderation_content_types() ) ) {

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-moderation-view-overview',
				'title'   => esc_html__( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . esc_html__( 'View moderation Overview line 1.', 'buddyboss' ) . '</p>' .
					'<p>' . esc_html__( 'View moderation Overview line 2', 'buddyboss' ) . '</p>',
			)
		);
		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p><a href="https://www.buddyboss.com/resources/">' . esc_html__( 'Documentation', 'buddyboss' ) . '</a></p>'
		);
	} else {
		/**
		 * Fires at top of Moderation admin page.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param string $doaction Current $_GET action being performed in admin screen.
		 */
		do_action( 'bp_moderation_admin_load', $doaction );

		// Create the Moderation screen list table.
		$bp_moderation_list_table = new BP_Moderation_List_Table();

		// The per_page screen option.
		add_screen_option( 'per_page', array( 'label' => esc_html__( 'Moderation Request', 'buddyboss' ) ) );

		// Help panel - overview text.
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-moderation-overview',
				'title'   => esc_html__( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . esc_html__( 'Moderation overview line 1', 'buddyboss' ) . '</p>' .
					'<p>' . esc_html__( 'Moderation overview line 2', 'buddyboss' ) . '</p>',
			)
		);

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p><a href="https://www.buddyboss.com/resources/">' . esc_html__( 'Documentation', 'buddyboss' ) . '</a></p>'
		);

		// Add accessible hidden heading and text for Activity screen pagination.
		get_current_screen()->set_screen_reader_content(
			array(
				/* translators: accessibility text */
				'heading_pagination' => esc_html__( 'Moderation list navigation', 'buddyboss' ),
			)
		);
	}

	/**
	 * Fires at top of Moderation admin page.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $doaction Current $_GET action being performed in admin screen.
	 */
	do_action( 'bp_moderation_admin_load', $doaction );

	// Create the Moderation screen list table.
	$bp_moderation_list_table = new BP_Moderation_List_Table();

	// The per_page screen option.
	add_screen_option( 'per_page', array( 'label' => esc_html__( 'Moderation Request', 'buddyboss' ) ) );

	// Help panel - overview text.
	get_current_screen()->add_help_tab(
		array(
			'id'      => 'bp-moderation-overview',
			'title'   => esc_html__( 'Overview', 'buddyboss' ),
			'content' =>
				'<p>' . esc_html__( 'Moderation overview line 1', 'buddyboss' ) . '</p>' .
				'<p>' . esc_html__( 'Moderation overview line 2', 'buddyboss' ) . '</p>',
		)
	);

	// Help panel - sidebar links.
	get_current_screen()->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For more information:', 'buddyboss' ) . '</strong></p>' .
		'<p><a href="https://www.buddyboss.com/resources/">' . esc_html__( 'Documentation', 'buddyboss' ) . '</a></p>'
	);

	// Add accessible hidden heading and text for Activity screen pagination.
	get_current_screen()->set_screen_reader_content(
		array(
			/* translators: accessibility text */
			'heading_pagination' => esc_html__( 'Moderation list navigation', 'buddyboss' ),
		)
	);
}

/**
 * Select the appropriate Moderation admin screen, and output it.
 *
 * @since BuddyBoss 2.0.0
 */
function bp_moderation_admin() {
	// Added navigation tab on top.
	if ( bp_core_get_moderation_admin_tabs() ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
					bp_core_admin_moderation_tabs( esc_html__( 'Blocked Members', 'buddyboss' ) );
				} else {
					bp_core_admin_moderation_tabs( esc_html__( 'Reported Content', 'buddyboss' ) );
				}
				?>
			</h2>
		</div>
		<?php
	}

	// Decide whether to load the index or edit screen.
	$doaction                = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_GET, 'content_type', FILTER_SANITIZE_STRING );

	// Display the single activity edit screen.
	if ( 'view' === $doaction && ! empty( $moderation_id ) && ! empty( $moderation_content_type ) && array_key_exists( $moderation_content_type, bp_moderation_content_types() ) ) {
		bp_moderation_admin_view();
	} else {
		bp_moderation_admin_index();
	}
}

/**
 * Display the Moderation admin index screen.
 *
 * This screen contains a list of all BuddyBoss Moderation requests.
 *
 * @since BuddyBoss 2.0.0
 *
 * @global BP_Moderation_List_Table $bp_moderation_list_table Moderation screen list table.
 * @global string                   $plugin_page              Currently viewed plugin page.
 */
function bp_moderation_admin_index() {
	global $bp_moderation_list_table, $plugin_page;

	// Prepare the group items for display.
	$bp_moderation_list_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php
			if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
				esc_html_e( 'Blocked Members', 'buddyboss' );
			} else {
				esc_html_e( 'Reported Content', 'buddyboss' );
			}
			?>
		</h1>
		<div class="bp-moderation-ajax-msg hidden notice notice-success">
			<p></p>
		</div>
		<?php $bp_moderation_list_table->views(); ?>
		<form id="bp-moseration-form" action="" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>"/>
			<?php $bp_moderation_list_table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * Handle save/update of screen options for the Moderation component admin screen.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 *
 * @return string|int Option value. False to abandon update.
 */
function bp_moderation_admin_screen_options( $value, $option, $new_value ) {

	if ( 'buddyboss_page_bp_moderation_per_page' !== $option && 'buddyboss_page_bp_activity_network_per_page' !== $option ) {
		return $value;
	}

	// Per page.
	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Display the single moderation edit screen.
 *
 * @since BuddyPress 1.6.0
 */
function bp_moderation_admin_view() {

	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_GET, 'content_type', FILTER_SANITIZE_STRING );
	$moderation_request_data = new BP_Moderation( $moderation_id, $moderation_content_type );

	/**
	 * Fires before moderation edit form is displays so plugins can modify the activity.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $value Array holding single activity object that was passed by reference.
	 */
	do_action_ref_array( 'bp_moderation_admin_edit', array( &$moderation_request_data ) );

	include 'screens/single/admin/report-single.php';
}

/**
 * Add Navigation tab on top of the page BuddyBoss > Moderation > Reporting Categories
 *
 * @since BuddyBoss 1.0.0
 */
function bp_moderation_admin_category_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( 'edit-tags.php' === $pagenow || 'term.php' === $pagenow ) && ( 'bpm_category' === $current_screen->taxonomy ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_moderation_tabs( esc_html__( 'Report Categories', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'bp_moderation_admin_category_listing_add_tab' );
