<?php
/**
 * BuddyPress Admin Component Functions.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the Component Setup admin panel.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_core_admin_components_settings() {
?>

	<div class="wrap">

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Components', 'buddyboss' ) ); ?></h2>
		<form action="" method="post" id="bp-admin-component-form">

			<?php bp_core_admin_components_options(); ?>

			<?php wp_nonce_field( 'bp-admin-component-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Creates reusable markup for component setup on the Components and Pages dashboard panel.
 *
 * @since BuddyPress 1.6.0
 *
 * @todo Use settings API
 */
function bp_core_admin_components_options() {

	// Declare local variables.
	$deactivated_components = array();

	/**
	 * Filters the array of available components.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param mixed $value Active components.
	 */
	$active_components      = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

	$default_components  = bp_core_admin_get_components( 'default' ); // The default components (if none are previously selected).
	$optional_components = bp_core_admin_get_components( 'optional' );
	$required_components = bp_core_admin_get_components( 'required' );

	if ( isset( $optional_components['blogs'] ) ) {
	    unset( $optional_components['blogs'] );
    }

	// Merge optional and required together.
	$all_components = $required_components + $optional_components;

	// If this is an upgrade from before BuddyPress 1.5, we'll have to convert
	// deactivated components into activated ones.
	if ( empty( $active_components ) ) {
		$deactivated_components = bp_get_option( 'bp-deactivated-components' );
		if ( !empty( $deactivated_components ) ) {

			// Trim off namespace and filename.
			$trimmed = array();
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
			}

			// Loop through the optional components to create an active component array.
			foreach ( array_keys( (array) $optional_components ) as $ocomponent ) {
				if ( !in_array( $ocomponent, $trimmed ) ) {
					$active_components[$ocomponent] = 1;
				}
			}
		}
	}

	// On new install, set active components to default.
	if ( empty( $active_components ) ) {
		$active_components = $default_components;
	}

	$inactive_components       = array_diff( array_keys( $all_components ) , array_keys( $active_components ) );

	/** Display **************************************************************
	 */

	// Get the total count of all plugins.
	$all_count = count( $all_components );
	$page      = bp_core_do_network_admin()  ? 'settings.php' : 'admin.php';
	$action    = !empty( $_GET['action'] ) ? $_GET['action'] : 'all';

	switch( $action ) {
		case 'all' :
			$current_components = $all_components;
			break;
		case 'active' :
			foreach ( array_keys( $active_components ) as $component ) {
				$current_components[$component] = $all_components[$component];
			}
			break;
		case 'inactive' :
			foreach ( $inactive_components as $component ) {
				$current_components[$component] = $all_components[$component];
			}
			break;
		case 'mustuse' :
			$current_components = $required_components;
			break;
	} ?>

	<h3 class="screen-reader-text"><?php
		/* translators: accessibility text */
		_e( 'Filter components list', 'buddyboss' );
	?></h3>

	<ul class="subsubsub">
		<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bp-components', 'action' => 'all'      ), bp_get_admin_url( $page ) ) ); ?>" <?php if ( $action === 'all'      ) : ?>class="current"<?php endif; ?>><?php printf( _n( 'All <span class="count">(%s)</span>',      'All <span class="count">(%s)</span>',      $all_count, 'buddyboss' ), number_format_i18n( $all_count                    ) ); ?></a> | </li>
		<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bp-components', 'action' => 'active'   ), bp_get_admin_url( $page ) ) ); ?>" <?php if ( $action === 'active'   ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Active <span class="count">(%s)</span>',   'Active <span class="count">(%s)</span>',   count( $active_components   ), 'buddyboss' ), number_format_i18n( count( $active_components   ) ) ); ?></a> | </li>
		<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bp-components', 'action' => 'inactive' ), bp_get_admin_url( $page ) ) ); ?>" <?php if ( $action === 'inactive' ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', count( $inactive_components ), 'buddyboss' ), number_format_i18n( count( $inactive_components ) ) ); ?></a> | </li>
		<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bp-components', 'action' => 'mustuse'  ), bp_get_admin_url( $page ) ) ); ?>" <?php if ( $action === 'mustuse'  ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Required <span class="count">(%s)</span>', 'Required <span class="count">(%s)</span>', count( $required_components ), 'buddyboss' ), number_format_i18n( count( $required_components ) ) ); ?></a></li>
	</ul>

	<h3 class="screen-reader-text"><?php
		/* translators: accessibility text */
		_e( 'Components list', 'buddyboss' );
	?></h3>

	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
			<select name="action" id="bulk-action-selector-top">
				<option value="">Bulk Actions</option>
				<option value="active" class="hide-if-no-js">Activate</option>
				<option value="inactive">Deactivate</option>
			</select>
			<input type="submit" id="doaction" class="button action" name="bp-admin-component-submit" value="Apply">
		</div>
	</div>
	<div class="wp-list-table widefat plugin-install">
		<!--
		<thead>
			<tr>
				<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox" <?php checked( empty( $inactive_components ) ); ?>>
					<label class="screen-reader-text" for="cb-select-all-1"><?php
					/* translators: accessibility text */
					_e( 'Enable or disable all optional components in bulk', 'buddyboss' );
				?></label></td>
				<th scope="col" id="name" class="manage-column column-title column-primary"><?php _e( 'Component', 'buddyboss' ); ?></th>
			</tr>
		</thead>
		-->

		<div id="the-list">

			<?php if ( !empty( $current_components ) ) : ?>

				<?php foreach ( $current_components as $name => $labels ) : ?>

					<?php
						if ( in_array( $name, array( 'blogs' ) ) ) :
							$class = isset( $active_components[esc_attr( $name )] ) ? 'active hidden' : 'inactive hidden';
						elseif ( !in_array( $name, array( 'core', 'members', 'xprofile' ) ) ) :
							$class = isset( $active_components[esc_attr( $name )] ) ? 'active' : 'inactive';
						else :
							$class = 'active';
						endif;
					?>

					<div class="plugin-card <?php echo esc_attr( $name ) . ' ' . esc_attr( $class ); ?>">
						<div class="plugin-card-top">

							<!--
							<?php if ( !in_array( $name, array( 'core', 'members', 'xprofile' ) ) ) : ?>

								<input type="checkbox" id="<?php echo esc_attr( "bp_components[$name]" ); ?>" name="<?php echo esc_attr( "bp_components[$name]" ); ?>" value="1"<?php checked( isset( $active_components[esc_attr( $name )] ) ); ?> /><label for="<?php echo esc_attr( "bp_components[$name]" ); ?>" class="screen-reader-text"><?php
									/* translators: accessibility text */
									printf( __( 'Select %s', 'buddyboss' ), esc_html( $labels['title'] ) ); ?></label>

							<?php endif; ?>
							-->

							<div class="name column-name">
								<h3>
									<?php echo esc_html( $labels['title'] ); ?>
									<img src="http://localhost:8888/buddyboss-theme/wp-content/plugins/buddyboss-platform/src/bp-core/images/mystery-man.jpg" class="plugin-icon" alt="">
								</h3>
							</div>
							<div class="action-links">
								<ul class="plugin-action-buttons">
								<?php if ( in_array( $name, array( 'core', 'members', 'xprofile' ) ) ) : ?>
									<li>
										<span class="button disabled">
		                                    <?php _e( 'Required', 'buddyboss' ); ?>
		                                </span>
		                            </li>
								<?php elseif ( ! in_array( $name, array( 'core', 'members', 'xprofile' ) ) ) : ?>
	                                <?php if ( isset( $active_components[esc_attr( $name )] ) ) : ?>
	                                    <li>
		                                    <a class="button" href="<?php echo wp_nonce_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components', 'action' => $action, 'bp_component' => $name, 'do_action' => 'deactivate' ) , $page ) ), 'bp-admin-component-activation' ); ?>">
		                                        <?php _e( 'Deactivate', 'buddyboss' ); ?>
		                                    </a>
		                                </li>
	                                <?php else: ?>
	                                    <li>
		                                    <a class="button button-primary" href="<?php echo wp_nonce_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components', 'action' => $action, 'bp_component' => $name, 'do_action' => 'activate' ) , $page ) ), 'bp-admin-component-activation' ); ?>">
		                                        <?php _e( 'Activate', 'buddyboss' ); ?>
		                                    </a>
		                                </li>
	                                <?php endif; ?>
								<?php endif; ?>
			                    <?php if ( isset( $active_components[esc_attr( $name )] ) && ! empty( $labels['settings'] ) ) : ?>
	                                <li>
	                                    <a class="thickbox open-plugin-details-modal" href="<?php echo esc_url( $labels['settings'] ); ?>">
	                                        <?php
		                                        if ( 'xprofile' === $name ) {
		                                            _e( 'Edit Fields', 'buddyboss' );
		                                        } else {
		                                            _e( 'Settings', 'buddyboss' );
		                                        }
	                                        ?>
	                                    </a>
	                                </li>
			                    <?php endif; ?>
								</ul>
							</div>
							<div class="desc column-description">
								<p><?php echo $labels['description']; ?></p>
							</div>

	                	</div>
                    </div>

				<?php endforeach ?>

			<?php else : ?>

				<tr class="no-items">
					<td class="colspanchange" colspan="2"><?php _e( 'No components found.', 'buddyboss' ); ?></td>
				</tr>

			<?php endif; ?>

		</div>

		<--
		<tfoot>
			<tr>
				<td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox" <?php checked( empty( $inactive_components ) ); ?>>
					<label class="screen-reader-text" for="cb-select-all-2"><?php
					/* translators: accessibility text */
					_e( 'Enable or disable all optional components in bulk', 'buddyboss' );
				?></label></td>
				<th class="manage-column column-title column-primary"><?php _e( 'Component', 'buddyboss' ); ?></th>
			</tr>
		</tfoot>
		-->

	</div>

	<input type="hidden" name="bp_components[members]" value="1" />
	<input type="hidden" name="bp_components[xprofile]" value="1" />

	<div class="tablenav bottom">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action', 'buddyboss' ); ?></label>
			<select name="action2" id="bulk-action-selector-top">
				<option value=""><?php _e( 'Bulk Actions', 'buddyboss' ); ?></option>
				<option value="active" class="hide-if-no-js"><?php _e( 'Activate', 'buddyboss' ); ?></option>
				<option value="inactive"><?php _e( 'Deactivate', 'buddyboss' ); ?></option>
			</select>
			<input type="submit" id="doaction" class="button action" name="bp-admin-component-submit" value="Apply">
		</div>
	</div>
	<?php
}

/**
 * Handle saving the Component settings.
 *
 * @since BuddyPress 1.6.0
 *
 * @todo Use settings API when it supports saving network settings
 */
function bp_core_admin_components_settings_handler() {

	// Bail if not saving settings.
	if ( ! isset( $_POST['bp-admin-component-submit'] ) )
		return;

	// Bail if nonce fails.
	if ( ! check_admin_referer( 'bp-admin-component-setup' ) )
		return;

	$action = ( isset( $_POST['action'] ) && '' !== $_POST['action'] ) ? $_POST['action'] : $_POST['action2'];
	if ( '' === $action )
		return;

	// Settings form submitted, now save the settings. First, set active components.
	if ( isset( $_POST['bp_components'] ) ) {

		// Load up BuddyPress.
		$bp = buddypress();

		// Save settings and upgrade schema.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		$current_components = $bp->active_components;
		$submitted          = stripslashes_deep( $_POST['bp_components'] );
		$required           = bp_core_admin_get_components( 'required' );

		if ( 'inactive' === $action ) {
			$submitted = array_diff_key( $current_components, $submitted );
			if ( empty( $submitted ) ) {
				foreach ( $required as $key => $req ) {
					$submitted[$key] = '1';
				}
			} else {
				foreach ( $required as $key => $req ) {
					$submitted[$key] = '1';
				}
			}

			$bp->active_components = $submitted;
			$uninstalled_components = array_diff_key($current_components, $bp->active_components);

			bp_core_install( $bp->active_components );
			bp_core_add_page_mappings( $bp->active_components );
			bp_update_option( 'bp-active-components', $bp->active_components );

			bp_core_uninstall( $uninstalled_components );

		} else {

			if ( empty( $submitted ) ) {
				foreach ( $required as $key => $req ) {
					$submitted[$key] = '1';
				}
			} else {
				foreach ( $required as $key => $req ) {
					$submitted[$key] = '1';
				}
			}

			$bp->active_components = $submitted;
			$uninstalled_components = array_diff_key($current_components, $bp->active_components);

			bp_core_install( $bp->active_components );
			bp_core_add_page_mappings( $bp->active_components );
			bp_update_option( 'bp-active-components', $bp->active_components );

			bp_core_uninstall( $uninstalled_components );
		}


	}

	$current_action = 'all';
	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'active', 'inactive' ) ) ) {
		$current_action = $_GET['action'];
	}

	// Where are we redirecting to?
	$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) );

	// Redirect.
	wp_redirect( $base_url );
	die();
}
add_action( 'bp_admin_init', 'bp_core_admin_components_settings_handler' );

/**
 * Handle saving the Component settings.
 *
 * @since BuddyBoss 1.0.0
 *
 * @todo Use settings API when it supports saving network settings
 */
function bp_core_admin_components_activation_handler() {

    if ( ! isset( $_GET['bp_component'] ) ) {
	    return;
    }

	// Bail if nonce fails.
	if ( ! check_admin_referer( 'bp-admin-component-activation' ) )
		return;

	// Settings form submitted, now save the settings. First, set active components.
	if ( isset( $_GET['bp_component'] ) ) {

		// Load up BuddyPress.
		$bp = buddypress();

		// Save settings and upgrade schema.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		$current_action = 'active';
		if ( isset( $_GET['do_action'] ) && in_array( $_GET['do_action'], array( 'activate', 'deactivate' ) ) ) {
			$current_action = $_GET['do_action'];
		}

		$current_components = $bp->active_components;

		$submitted = stripslashes_deep( $_GET['bp_component'] );

		switch ( $current_action ) {
			case 'deactivate' :
			    foreach( $current_components as $key => $component ) {
			        if ( $submitted == $key ) {
				        unset( $current_components[ $key ] );
			        }
                }
				$bp->active_components = $current_components;
				break;

			case 'activate' :
			default :
			    $bp->active_components = array_merge( array( $submitted => $current_action == 'activate' ? '1' : '0' ), $current_components );
				break;
		}

		$uninstalled_components = array_diff_key($current_components, $bp->active_components);

		bp_core_install( $bp->active_components );
		bp_core_add_page_mappings( $bp->active_components );
		bp_update_option( 'bp-active-components', $bp->active_components );

		bp_core_uninstall( $uninstalled_components );
	}

	$current_action = 'all';
	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'active', 'inactive' ) ) ) {
		$current_action = $_GET['action'];
	}

	// Where are we redirecting to?
	$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components', 'action' => $current_action, 'updated' => 'true' ), 'admin.php' ) );

	// Redirect.
	wp_redirect( $base_url );
	die();
}
add_action( 'bp_admin_init', 'bp_core_admin_components_activation_handler' );

/**
 * Calculates the components that should be active after save, based on submitted settings.
 *
 * The way that active components must be set after saving your settings must
 * be calculated differently depending on which of the Components subtabs you
 * are coming from:
 * - When coming from All or Active, the submitted checkboxes accurately
 *   reflect the desired active components, so we simply pass them through
 * - When coming from Inactive, components can only be activated - already
 *   active components will not be passed in the $_POST global. Thus, we must
 *   parse the newly activated components with the already active components
 *   saved in the $bp global
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $submitted This is the array of component settings coming from the POST
 *                         global. You should stripslashes_deep() before passing to this function.
 * @return array The calculated list of component settings
 */
function bp_core_admin_get_active_components_from_submitted_settings( $submitted, $action = 'all' ) {
	$current_action = $action;

	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'active', 'inactive' ) ) ) {
		$current_action = $_GET['action'];
	}

	$current_components = buddypress()->active_components;

	switch ( $current_action ) {
		case 'inactive' :
			$components = array_merge( $submitted, $current_components );
			break;

		case 'all' :
		case 'active' :
		default :
			$components = $submitted;
			break;
	}

	return $components;
}

/**
 * Return a list of component information.
 *
 * We use this information both to build the markup for the admin screens, as
 * well as to do some processing on settings data submitted from those screens.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $type Optional; component type to fetch. Default value is 'all', or 'optional', 'required'.
 * @return array Requested components' data.
 */
function bp_core_admin_get_components( $type = 'all' ) {
	$components = bp_core_get_components( $type );

	/**
	 * Filters the list of component information.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array  $components Array of component information.
	 * @param string $type       Type of component list requested.
	 *                           Possible values include 'all', 'optional',
	 *                           'required'.
	 */
	return apply_filters( 'bp_core_admin_get_components', $components, $type );
}
