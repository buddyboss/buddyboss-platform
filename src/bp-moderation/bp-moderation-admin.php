<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
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
 * Register the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		__( 'Moderation', 'buddyboss' ),
		__( 'Moderation', 'buddyboss' ),
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
 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_admin_load() {
	global $bp_moderation_list_table;

	$doaction                = bp_admin_list_table_current_bulk_action();
	$moderation_id           = filter_input( INPUT_REQUEST, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_REQUEST, 'content_type', FILTER_SANITIZE_STRING );

	if ( 'view' === $doaction && ! empty( $moderation_id ) && ! empty( $moderation_content_type ) && array_key_exists( $moderation_content_type, bp_moderation_content_types() ) ) {

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-moderation-view-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . __( 'View moderation Overview line 1.', 'buddyboss' ) . '</p>' .
					'<p>' . __( 'View moderation Overview line 2', 'buddyboss' ) . '</p>',
			)
		);
		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
		);
	} else {
		/**
		 * Fires at top of Moderation admin page.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $doaction Current $_GET action being performed in admin screen.
		 */
		do_action( 'bp_moderation_admin_load', $doaction );

		// Create the Moderation screen list table.
		$bp_moderation_list_table = new BP_Moderation_List_Table();

		// The per_page screen option.
		add_screen_option( 'per_page', array( 'label' => __( 'Moderation Request', 'buddyboss' ) ) );

		// Help panel - overview text.
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-moderation-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . __( 'Moderation overview line 1', 'buddyboss' ) . '</p>' .
					'<p>' . __( 'Moderation overview line 2', 'buddyboss' ) . '</p>',
			)
		);

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
		);

		// Add accessible hidden heading and text for Activity screen pagination.
		get_current_screen()->set_screen_reader_content(
			array(
				/* translators: accessibility text */
				'heading_pagination' => __( 'Moderation list navigation', 'buddyboss' ),
			)
		);
	}
}

/**
 * Select the appropriate Moderation admin screen, and output it.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_admin() {
	// Added navigation tab on top.
	if ( bp_core_get_moderation_admin_tabs() ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) {
					bp_core_admin_moderation_tabs( __( 'Blocked Members', 'buddyboss' ) );
				} elseif ( ! empty( $_GET['tab'] ) && 'report-categories' === $_GET['tab'] ) {
					bp_core_admin_moderation_tabs( __( 'Report Categories', 'buddyboss' ) );
				} else {
					bp_core_admin_moderation_tabs( __( 'Reported Content', 'buddyboss' ) );
				}
				?>
			</h2>
		</div>
		<?php
	}

	// Decide whether to load the index or edit screen.
	$doaction                = filter_input( INPUT_REQUEST, 'action', FILTER_SANITIZE_STRING );
	$moderation_id           = filter_input( INPUT_REQUEST, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_REQUEST, 'content_type', FILTER_SANITIZE_STRING );

	// Display the single activity edit screen.
	if ( 'view' == $doaction && ! empty( $moderation_id ) && ! empty( $moderation_content_type ) && array_key_exists( $moderation_content_type, bp_moderation_content_types() ) ) {
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
 * @since BuddyBoss 1.5.4
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
			} elseif ( ! empty( $_GET['tab'] ) && 'report-categories' === $_GET['tab'] ) {
				esc_html_e( 'Report Categories', 'buddyboss' );
			} else {
				esc_html_e( 'Reported Content', 'buddyboss' );
			}
			?>
		</h1>

		<?php
		if ( ! empty( $_GET['tab'] ) && 'report-categories' === $_GET['tab'] ) {
			echo 'report categories';
		} else {
			$bp_moderation_list_table->views();
			?>

			<form id="bp-moseration-form" action="" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>"/>
				<?php $bp_moderation_list_table->display(); ?>
			</form>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Handle save/update of screen options for the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.4
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

	$moderation_id           = filter_input( INPUT_REQUEST, 'mid', FILTER_SANITIZE_NUMBER_INT );
	$moderation_content_type = filter_input( INPUT_REQUEST, 'content_type', FILTER_SANITIZE_STRING );
	$moderation_request_data = new BP_Moderation( $moderation_id, $moderation_content_type );

	/**
	 * Fires before moderation edit form is displays so plugins can modify the activity.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $value Array holding single activity object that was passed by reference.
	 */
	do_action_ref_array( 'bp_moderation_admin_edit', array( &$moderation_request_data ) );
	?>
	<div class="wrap">
		<h1>
			<?php
			/* translators: accessibility text */
			printf( esc_html__( 'Editing Moderation (ID #%s)', 'buddyboss' ), esc_html( number_format_i18n( (int) $moderation_id ) ) );
			?>
		</h1>

		<?php
		if ( ! empty( $moderation_request_data ) ) :
			?>
			<div id="poststuff">
				<div id="post-body"
					 class="metabox-holder columns-<?php echo 1 === (int) get_current_screen()->get_columns() ? '1' : '2'; ?>">
					<div id="post-body-content">
						<div id="postdiv">
							<div id="bp_moderation_action" class="postbox">
								<h2>
									<?php esc_html_e( 'Details', 'buddyboss' ); ?>
								</h2>
								<div class="inside">
									<table class="form-table">
										<tbody>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Item ID', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( $moderation_request_data->item_id );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Item Type', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( bp_get_moderation_content_type( $moderation_request_data->item_type ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Hidden Sitewide', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												$hide_sitewide = ( 1 === (int) $moderation_request_data->hide_sitewide ) ? esc_html__( 'Yes', 'buddyboss' ) : esc_html__( 'No', 'buddyboss' );
												echo esc_html( $hide_sitewide );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Content Owner', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												$user_id = bp_moderation_get_content_owner_id( $moderation_request_data->item_id, $moderation_request_data->item_type );
												printf( '<strong>%s %s</strong>', wp_kses_post( get_avatar( $user_id, '32' ) ), wp_kses_post( bp_core_get_userlink( $user_id ) ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Last reported By', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												printf( '<strong>%s %s</strong>', wp_kses_post( get_avatar( $moderation_request_data->updated_by, '32' ) ), wp_kses_post( bp_core_get_userlink( $moderation_request_data->updated_by ) ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Last reported', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( bbp_get_time_since( bbp_convert_date( $moderation_request_data->date_updated ) ) );
												?>
											</td>
										</tr>
										</tbody>
									</table>
								</div>
							</div>

							<div id="bp_moderation_action" class="postbox">
								<h2>
									<?php esc_html_e( 'Reports', 'buddyboss' ); ?>
								</h2>
								<div class="inside">
									<?php
									$bp_moderation_report_list_table = new BP_Moderation_Report_List_Table();
									// Prepare the group items for display.
									$bp_moderation_report_list_table->prepare_items();
									$bp_moderation_report_list_table->views();
									$bp_moderation_report_list_table->display();
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<p>
				<?php
				printf(
					'%1$s <a href="%2$s">%3$s</a>',
					esc_html__( 'No moderation found with this ID.', 'buddyboss' ),
					esc_url( bp_get_admin_url( 'admin.php?page=bp-moderation' ) ),
					esc_html__( 'Go back and try again.', 'buddyboss' )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
