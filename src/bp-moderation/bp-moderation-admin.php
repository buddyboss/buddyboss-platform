<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Per_page screen option. Has to be hooked in extremely early.
if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-moderation' === $_REQUEST['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	add_filter( 'set-screen-option', 'bp_moderation_admin_screen_options', 10, 3 );
}

/**
 * Function to hook the admin screen.
 *
 * @since BuddyBoss 1.5.6
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
					'confirm_msg'            => esc_js( __( 'Please confirm you want to hide this content. It will be hidden from all members in your network.', 'buddyboss' ) ),
					'unhide_confirm_msg'     => esc_js( __( 'Please confirm you want to unhide this content. It will be open for all members in your network.', 'buddyboss' ) ),
					'hide_label'             => esc_js( __( 'Hide Content', 'buddyboss' ) ),
					'unhide_label'           => esc_js( __( 'Unhide Content', 'buddyboss' ) ),
					'suspend_label'          => esc_js( __( 'Suspend Member', 'buddyboss' ) ),
					'unsuspend_label'        => esc_js( __( 'Unsuspend Member', 'buddyboss' ) ),
					'suspend_author_label'   => esc_js( __( 'Suspend Owner', 'buddyboss' ) ),
					'unsuspend_author_label' => esc_js( __( 'Unsuspend Owner', 'buddyboss' ) ),
				),
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'bp_moderation_admin_scripts' );

/**
 * Register the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.6
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
 * @since BuddyBoss 1.5.6
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
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_admin_load() {
	global $bp_moderation_list_table;

	$doaction                = bp_admin_list_table_current_bulk_action();
	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = bb_filter_input_string( INPUT_GET, 'content_type' );

	$request_url            = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
	$_SERVER['REQUEST_URI'] = remove_query_arg(
		array(
			'unhide',
			'hidden',
			'suspended',
			'unsuspended',
		),
		$request_url
	);

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
		 * @since BuddyBoss 1.5.6
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
	 * Handle bulk actions for moderation.
	 */
	if ( ! empty( $doaction ) && ! in_array( $doaction, array( '-1', 'edit', 'save', 'view' ), true ) ) {

		// Build redirection URL.
		$redirect_to = remove_query_arg(
			array(
				'mid',
				'deleted',
				'error',
				'unhide',
				'hidden',
				'suspended',
				'unsuspended',
			),
			wp_get_referer()
		);

		$redirect_to = add_query_arg( 'paged', $bp_moderation_list_table->get_pagenum(), $redirect_to );

		// Get moderation IDs.
		$moderation_ids = isset( $_REQUEST['mid'] ) ? $_REQUEST['mid'] : array(); // phpcs:ignore
		$moderation_ids = array_map( 'absint', (array) $moderation_ids );

		/**
		 * Filters list of IDs being hide/unhide.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $moderation_ids Activity IDs to spam/un-spam/delete.
		 */
		$moderation_ids = apply_filters( 'bp_moderation_admin_action_moderation_ids', $moderation_ids );

		// Is this a bulk request?
		if ( 'bulk_' === substr( $doaction, 0, 5 ) && ! empty( $_REQUEST['mid'] ) ) {

			// Check this is a valid form submission.
			check_admin_referer( 'bulk-moderations' );

			// Trim 'bulk_' off the action name to avoid duplicating a ton of code.
			$doaction = substr( $doaction, 5 );
		}

		$content_count = 0;
		$user_count    = 0;
		$admins        = array_map( 'intval', get_users( array( 'role' => 'administrator', 'fields' => 'ID', ) ) );

		foreach ( $moderation_ids as $moderation_id ) {
			$moderation_obj     = new BP_Moderation();
			$moderation_obj->id = $moderation_id;
			$moderation_obj->populate();

			if ( BP_Moderation_Members::$moderation_type === $moderation_obj->item_type ) {
				if ( ! in_array( $moderation_obj->item_id, $admins, true ) ) {
					if ( 'hide' === $doaction ) {
						BP_Suspend_Member::suspend_user( $moderation_obj->item_id );
					} else {
						BP_Suspend_Member::unsuspend_user( $moderation_obj->item_id );
					}
					$user_count ++;
				}
				continue;
			} else {
				if ( 'hide' === $doaction ) {
					$moderation = bp_moderation_hide(
						array(
							'content_id'   => $moderation_obj->item_id,
							'content_type' => $moderation_obj->item_type,
						)
					);
					if ( 1 === $moderation->hide_sitewide ) {
						$content_count ++;
					}
				} else {
					$moderation = bp_moderation_unhide(
						array(
							'content_id'   => $moderation_obj->item_id,
							'content_type' => $moderation_obj->item_type,
						)
					);
					if ( 0 === $moderation->hide_sitewide ) {
						$content_count ++;
					}
				}
			}
		}

		/**
		 * Fires before redirect for plugins to do something with moderation.
		 *
		 * Passes an moderation array counts how many were hide, unhide, and IDs that were errors.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array  $value          Array holding hide, unhide, error IDs.
		 * @param string $redirect_to    URL to redirect to.
		 * @param array  $moderation_ids Original array of activity IDs.
		 */
		do_action(
			'bp_moderation_admin_action_after',
			array(
				$content_count,
				$user_count,
			),
			$redirect_to,
			$moderation_ids
		);

		// Add arguments to the redirect URL so that on page reload, we can easily display what we've just done.
		if ( $content_count && 'hide' === $doaction ) {
			$redirect_to = add_query_arg( 'hidden', $content_count, $redirect_to );
		}

		if ( $content_count && 'unhide' === $doaction ) {
			$redirect_to = add_query_arg( 'unhide', $content_count, $redirect_to );
		}

		if ( $user_count && 'hide' === $doaction ) {
			$redirect_to = add_query_arg( 'suspended', $user_count, $redirect_to );
		}

		if ( $user_count && 'unhide' === $doaction ) {
			$redirect_to = add_query_arg( 'unsuspended', $user_count, $redirect_to );
		}

		/**
		 * Filters redirect URL after moderation hide/unhide.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param string $redirect_to URL to redirect to.
		 */
		wp_safe_redirect( apply_filters( 'bp_moderation_admin_action_redirect', $redirect_to ) );
		exit;
	}

	/**
	 * Fires at top of Moderation admin page.
	 *
	 * @since BuddyBoss 1.5.6
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
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_admin() {
	// Added navigation tab on top.
	if ( bp_core_get_moderation_admin_tabs() ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				$current_tab = bb_filter_input_string( INPUT_GET, 'tab' );
				$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

				if ( 'reported-content' === $current_tab ) {
					bp_core_admin_moderation_tabs( esc_html__( 'Reported Content', 'buddyboss' ) );
				} else {
					bp_core_admin_moderation_tabs( esc_html__( 'Flagged Members', 'buddyboss' ) );
				}
				?>
			</h2>
		</div>
		<?php
	}

	// Decide whether to load the index or edit screen.
	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$doaction                = bb_filter_input_string( INPUT_GET, 'action' );
	$moderation_content_type = bb_filter_input_string( INPUT_GET, 'content_type' );

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
 * @since BuddyBoss 1.5.6
 *
 * @global BP_Moderation_List_Table $bp_moderation_list_table Moderation screen list table.
 * @global string                   $plugin_page              Currently viewed plugin page.
 */
function bp_moderation_admin_index() {
	global $bp_moderation_list_table, $plugin_page;

	$messages = array();

	// If the user has just made a change to an Reported item, build status messages.
	if ( ! empty( $_REQUEST['hidden'] ) || ! empty( $_REQUEST['unhide'] ) || ! empty( $_REQUEST['suspended'] ) || ! empty( $_REQUEST['unsuspended'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hidden      = ! empty( $_REQUEST['hidden'] ) ? (int) $_REQUEST['hidden'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$unhide      = ! empty( $_REQUEST['unhide'] ) ? (int) $_REQUEST['unhide'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$suspended   = ! empty( $_REQUEST['suspended'] ) ? (int) $_REQUEST['suspended'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$unsuspended = ! empty( $_REQUEST['unsuspended'] ) ? (int) $_REQUEST['unsuspended'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $hidden > 0 ) {
			// translators:  number of items.
			$messages[] = __( 'Content hidden successfully.', 'buddyboss' );
		}

		if ( $unhide > 0 ) {
			// translators:  number of items.
			$messages[] = __( 'Content unhidden successfully.', 'buddyboss' );
		}

		if ( $suspended > 0 ) {
			// translators:  number of items.
			$messages[] = _n( 'Member suspended successfully', 'Members suspended successfully', $suspended, 'buddyboss' );
		}

		if ( $unsuspended > 0 ) {
			// translators:  number of items.
			$messages[] = _n( 'Member unsuspended successfully.', 'Members unsuspended successfully.', $unsuspended, 'buddyboss' );
		}
	}

	$current_tab = bb_filter_input_string( INPUT_GET, 'tab' );
	$current_tab = ( ! bp_is_moderation_member_blocking_enable() ) ? 'reported-content' : $current_tab;

	// Prepare the group items for display.
	$bp_moderation_list_table->prepare_items();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php
			if ( 'reported-content' === $current_tab ) {
				esc_html_e( 'Reported Content', 'buddyboss' );
			} else {
				esc_html_e( 'Flagged Members', 'buddyboss' );
			}
			?>
		</h1>
		<hr class="wp-header-end">
		<?php if ( ! empty( $messages ) ) : ?>
			<div id="moderation" class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?>"> <?php //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<p><?php echo wp_kses_post( implode( "<br/>\n", $messages ) ); ?></p>
			</div>
		<?php endif; ?>
		<div class="bp-moderation-ajax-msg hidden notice notice-success">
			<p></p>
		</div>
		<?php $bp_moderation_list_table->views(); ?>
		<form id="bp-moseration-form" action="" method="get">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>"/>
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>"/>
			<?php $bp_moderation_list_table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * Handle save/update of screen options for the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.6
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
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_admin_view() {

	$messages = array();

	// If the user has just made a change to an moderation item, build status messages.
	if ( ! empty( $_REQUEST['hidden'] ) || ! empty( $_REQUEST['unhide'] ) || ! empty( $_REQUEST['suspended'] ) || ! empty( $_REQUEST['unsuspended'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hidden      = ! empty( $_REQUEST['hidden'] ) ? (int) $_REQUEST['hidden'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$unhide      = ! empty( $_REQUEST['unhide'] ) ? (int) $_REQUEST['unhide'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$suspended   = ! empty( $_REQUEST['suspended'] ) ? (int) $_REQUEST['suspended'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$unsuspended = ! empty( $_REQUEST['unsuspended'] ) ? (int) $_REQUEST['unsuspended'] : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $hidden > 0 ) {
			// translators:  number of items.
			$messages[] = __( 'Content hidden successfully.', 'buddyboss' );
		}

		if ( $unhide > 0 ) {
			// translators:  number of items.
			$messages[] = __( 'Content unhidden successfully.', 'buddyboss' );
		}

		if ( $suspended > 0 ) {
			// translators:  number of items.
			$messages[] = _n( 'Member suspended successfully', 'Members suspended successfully', $suspended, 'buddyboss' );
		}

		if ( $unsuspended > 0 ) {
			// translators:  number of items.
			$messages[] = _n( 'Member unsuspended successfully', 'Members suspended successfully', $unsuspended, 'buddyboss' );
		}
	}

	$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = bb_filter_input_string( INPUT_GET, 'content_type' );
	$moderation_request_data = new BP_Moderation( $moderation_id, $moderation_content_type );

	if ( empty( $moderation_request_data->id ) ) {
		$moderation_request_data = new BP_Moderation( $moderation_id, BP_Moderation_Members::$moderation_type_report );
	}

	/**
	 * Fires before moderation edit form is displays so plugins can modify the activity.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $value Array holding single activity object that was passed by reference.
	 */
	do_action_ref_array( 'bp_moderation_admin_edit', array( &$moderation_request_data ) );

	include 'screens/single/admin/report-single.php';
}

/**
 * Add Navigation tab on top of the page BuddyBoss > Moderation > Reporting Categories
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_admin_category_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( 'edit-tags.php' === $pagenow || 'term.php' === $pagenow ) && ( 'bpm_category' === $current_screen->taxonomy ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_moderation_tabs( esc_html__( 'Reporting Categories', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'bp_moderation_admin_category_listing_add_tab' );

