<?php
/**
 * BuddyPress XProfile Admin.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool
 */
function xprofile_add_admin_menu() {

	// Bail if current user cannot moderate community.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	add_submenu_page(
		'buddyboss-platform',
		__( 'Profiles', 'buddyboss' ),
		__( 'Profiles', 'buddyboss' ),
		'bp_moderate',
		'bp-profile-setup',
		'xprofile_admin'
	);

}
add_action( bp_core_admin_hook(), 'xprofile_add_admin_menu' );

/**
 * Handles all actions for the admin area for creating, editing and deleting
 * profile groups and fields.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $message Message to display.
 * @param string $type    Type of action to be displayed.
 */
function xprofile_admin( $message = '', $type = 'error' ) {

	// What mode?
	$mode = ! empty( $_GET['mode'] )
		? sanitize_key( $_GET['mode'] )
		: false;

	// Group ID
	$group_id = ! empty( $_GET['group_id'] )
		? intval( $_GET['group_id'] )
		: false;

	// Field ID
	$field_id = ! empty( $_GET['field_id'] )
		? intval( $_GET['field_id'] )
		: false;

	// Option ID
	$option_id = ! empty( $_GET['option_id'] )
		? intval( $_GET['option_id'] )
		: false;

	// Allowed modes
	$allowed_modes = array(
		'add_group',
		'edit_group',
		'delete_group',
		'add_field',
		'edit_field',
		'delete_field',
		'delete_option',
	);

	// Is an allowed mode
	if ( in_array( $mode, $allowed_modes, true ) ) {

		// All group actions
		if ( false !== $group_id ) {

			// Add field to group
			if ( 'add_field' == $mode ) {
				xprofile_admin_manage_field( $group_id );

				// Edit field of group
			} elseif ( ! empty( $field_id ) && 'edit_field' === $mode ) {
				xprofile_admin_manage_field( $group_id, $field_id );

				// Delete group
			} elseif ( 'delete_group' === $mode ) {
				xprofile_admin_delete_group( $group_id );

				// Edit group
			} elseif ( 'edit_group' === $mode ) {
				xprofile_admin_manage_group( $group_id );
			}

			// Delete field
		} elseif ( ( false !== $field_id ) && ( 'delete_field' === $mode ) ) {
			xprofile_admin_delete_field( $field_id, 'field' );

			// Delete option
		} elseif ( ! empty( $option_id ) && 'delete_option' === $mode ) {
			xprofile_admin_delete_field( $option_id, 'option' );

			// Add group
		} elseif ( 'add_group' == $mode ) {
			xprofile_admin_manage_group();
		}
	} else {
		xprofile_admin_screen( $message, $type );
	}
}

/**
 * Output the main XProfile management screen.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $message Feedback message.
 * @param string $type    Feedback type.
 *
 * @todo Improve error message output
 */
function xprofile_admin_screen( $message = '', $type = 'error' ) {

	// Users admin URL
	$url = bp_get_admin_url( 'admin.php' );

	// Add Group
	$add_group_url = add_query_arg(
		array(
			'page' => 'bp-profile-setup',
			'mode' => 'add_group',
		),
		$url
	);

	// Validate type.
	$type = preg_replace( '|[^a-z]|i', '', $type );

	// Get all of the profile groups & fields.
	$groups = bp_xprofile_get_groups(
		array(
			'fetch_fields' => true,
		)
	); ?>

	<div class="wrap">
		<?php
			$users_tab = count( bp_core_get_users_admin_tabs() );
		if ( $users_tab > 1 ) {
			?>
				<h2 class="nav-tab-wrapper"><?php bp_core_admin_users_tabs( __( 'Profile Fields', 'buddyboss' ) ); ?></h2>
																			<?php
		}
		?>
	</div>
	<div class="wrap">
		<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

			<h1 class="wp-heading-inline"><?php _e( 'Profile Fields', 'buddyboss' ); ?></h1>

				<a id="add_group" class="page-title-action" href="<?php echo esc_url( $add_group_url ); ?>"><?php _e( 'New Field Set', 'buddyboss' ); ?></a>

			<hr class="wp-header-end">

		<?php else : ?>

			<h1>
				<?php _e( 'Profile Fields', 'buddyboss' ); ?>
				<a id="add_group" class="add-new-h2" href="<?php echo esc_url( $add_group_url ); ?>"><?php _e( 'New Field Set', 'buddyboss' ); ?></a>
			</h1>

		<?php endif; ?>

		<form action="" id="profile-field-form" method="post">

			<?php

			wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields' );
			wp_nonce_field( 'bp_reorder_groups', '_wpnonce_reorder_groups', false );

			if ( ! empty( $message ) ) :
				$type = ( $type == 'error' ) ? 'error' : 'updated';
				?>

				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<div id="tabs" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<ul id="field-group-tabs">

					<?php
					if ( ! empty( $groups ) ) :
						foreach ( $groups as $group ) :
							?>

						<li id="group_<?php echo esc_attr( $group->id ); ?>">
							<a href="#tabs-<?php echo esc_attr( $group->id ); ?>" class="ui-tab">
								<?php
								/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
								echo esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
								?>

								<?php if ( ! $group->can_delete ) : ?>
									<span><?php _e( '(Signup)', 'buddyboss' ); ?></span>
								<?php endif; ?>

							</a>
						</li>

											<?php
					endforeach;
endif;
					?>

				</ul>

				<?php
				if ( ! empty( $groups ) ) :
					foreach ( $groups as $group ) :

						// Add Field to Group URL
						$add_field_url = add_query_arg(
							array(
								'page'     => 'bp-profile-setup',
								'mode'     => 'add_field',
								'group_id' => (int) $group->id,
							),
							$url
						);

											// Edit Group URL
											$edit_group_url = add_query_arg(
												array(
													'page' => 'bp-profile-setup',
													'mode' => 'edit_group',
													'group_id' => (int) $group->id,
												),
												$url
											);

											// Delete Group URL
											$delete_group_url = wp_nonce_url(
												add_query_arg(
													array(
														'page' => 'bp-profile-setup',
														'mode' => 'delete_group',
														'group_id' => (int) $group->id,
													),
													$url
												),
												'bp_xprofile_delete_group'
											);
						?>

					<noscript>
						<h3>
						<?php
						/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
						echo esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
						?>
						</h3>
					</noscript>

					<div id="tabs-<?php echo esc_attr( $group->id ); ?>" class="tab-wrapper">
						<div class="tab-toolbar">
							<div class="tab-toolbar-left">
								<a class="button-primary" href="<?php echo esc_url( $add_field_url ); ?>"><?php _e( 'Add New Field', 'buddyboss' ); ?></a>
								<a class="button edit" href="<?php echo esc_url( $edit_group_url ); ?>"><?php _e( 'Edit Field Set', 'buddyboss' ); ?></a>

								<?php if ( $group->can_delete ) : ?>

									<div class="delete-button">
										<a class="confirm submitdelete deletion ajax-option-delete delete-profile-field-group" href="<?php echo esc_url( $delete_group_url ); ?>"><?php _e( 'Delete Field Set', 'buddyboss' ); ?></a>
									</div>

								<?php endif; ?>

								<?php

								/**
								 * Fires at end of action buttons in xprofile management admin.
								 *
								 * @since BuddyPress 2.2.0
								 *
								 * @param BP_XProfile_Group $group BP_XProfile_Group object
								 *                                 for the current group.
								 */
								do_action( 'xprofile_admin_group_action', $group );
								?>

							</div>
						</div>

						<fieldset id="<?php echo esc_attr( $group->id ); ?>" class="connectedSortable field-group" aria-live="polite" aria-atomic="true" aria-relevant="all">
												<?php if ( ! empty( $group->description ) ) : ?>
								<p class="bp-profile-group-description">
													<?php
													/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
													echo esc_html( apply_filters( 'bp_get_the_profile_group_description', $group->description ) );
													?>
								</p>
							<?php endif; ?>

							<legend class="screen-reader-text">
												<?php
												/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
												/* translators: accessibility text */
												printf( esc_html__( 'Fields for "%s" Field Set', 'buddyboss' ), apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
												?>
							</legend>

												<?php

												if ( ! empty( $group->fields ) ) :
													foreach ( $group->fields as $field ) {

														if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
															if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && $field->id === bp_get_xprofile_member_type_field_id() ) {
																continue;
															}
														}

														// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
														if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $field->id ) ) {
															continue;
														}

														// Load the field.
														$field = xprofile_get_field( $field->id );

														$class = '';
														if ( empty( $field->can_delete ) ) {
															$class = ' core';
														}

														/**
														 * This function handles the WYSIWYG profile field
														 * display for the xprofile admin setup screen.
														 */
														xprofile_admin_field( $field, $group, $class );

													} // end for

												else : // !$group->fields
													?>

								<p class="nodrag nofields"><?php _e( 'There are no fields in this field set.', 'buddyboss' ); ?></p>

							<?php endif; // End $group->fields. ?>

						</fieldset>

											<?php if ( empty( $group->can_delete ) ) : ?>

							<p><?php esc_html_e( '* These fields appear on the signup page. The (Signup) fields cannot be deleted or moved, as they are needed for the signup process.', 'buddyboss' ); ?></p>

						<?php endif; ?>

					</div>

									<?php endforeach; else : ?>

					<div id="message" class="error"><p><?php _e( 'You have no field sets.', 'buddyboss' ); ?></p></div>
					<p><a href="<?php echo esc_url( $add_group_url ); ?>"><?php _e( 'New Field Set', 'buddyboss' ); ?></a></p>

				<?php endif; ?>

			</div>
		</form>
	</div>

	<?php
}

/**
 * Handles the adding or editing of groups.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int|null $group_id Group ID to manage.
 */
function xprofile_admin_manage_group( $group_id = null ) {
	global $message, $type;

	// Get the field group.
	$group = new BP_XProfile_Group( $group_id );

	// Updating.
	if ( isset( $_POST['save_group'] ) ) {

		// Check nonce
		check_admin_referer( 'bp_xprofile_admin_group', 'bp_xprofile_admin_group' );

		// Validate $_POSTed data.
		if ( BP_XProfile_Group::admin_validate() ) {

			// Set the group name.
			$group->name = $_POST['group_name'];

			// Set the group description.
			if ( ! empty( $_POST['group_description'] ) ) {
				$group->description = $_POST['group_description'];
			} else {
				$group->description = '';
			}

			// Attempt to save the field group.
			if ( false === $group->save() ) {
				$message = __( 'There was an error saving the field set. Please try again.', 'buddyboss' );
				$type    = 'error';

				// Save successful.
			} else {
				$message = __( 'The field set was saved successfully.', 'buddyboss' );
				$type    = 'success';

				// @todo remove these old options
				if ( 1 == $group_id ) {
					bp_update_option( 'bp-xprofile-base-group-name', $group->name );
				}

				/**
				 * Fires at the end of the group adding/saving process, if successful.
				 *
				 * @since BuddyPress 1.0.0
				 *
				 * @param BP_XProfile_Group $group Current BP_XProfile_Group object.
				 */
				do_action( 'xprofile_groups_saved_group', $group );
			}

			xprofile_admin_screen( $message, $type );

		} else {
			$group->render_admin_form( $message );
		}
	} else {
		$group->render_admin_form();
	}
}

/**
 * Handles the deletion of profile data groups.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $group_id ID of the group to delete.
 */
function xprofile_admin_delete_group( $group_id ) {
	global $message, $type;

	check_admin_referer( 'bp_xprofile_delete_group' );

	$group = new BP_XProfile_Group( $group_id );

	if ( ! $group->delete() ) {
		$message = __( 'There was an error deleting the field set. Please try again.', 'buddyboss' );
		$type    = 'error';
	} else {
		$message = __( 'The field set was deleted successfully.', 'buddyboss' );
		$type    = 'success';

		/**
		 * Fires at the end of group deletion process, if successful.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_XProfile_Group $group Current BP_XProfile_Group object.
		 */
		do_action( 'xprofile_groups_deleted_group', $group );
	}

	xprofile_admin_screen( $message, $type );
}

/**
 * Handles the adding or editing of profile field data for a user.
 *
 * @since BuddyPress 1.0.0
 * @since BuddyBoss 1.0.0
 * Updated to continue showing the field-edit form, after field is saved/updated.
 * Updated to exclude repeater field IDs while determining field_order for new field.
 *
 * @param int      $group_id ID of the group.
 * @param int|null $field_id ID of the field being managed.
 */
function xprofile_admin_manage_field( $group_id, $field_id = null ) {
	global $wpdb, $message, $groups;

	$bp = buddypress();

	if ( is_null( $field_id ) ) {
		$field = new BP_XProfile_Field();
		$new   = true;
	} else {
		$field = xprofile_get_field( $field_id );
		$new   = false;
	}

	$field->group_id = $group_id;

	if ( isset( $_POST['saveField'] ) ) {

		// Check nonce
		wp_verify_nonce( $_POST['bp_xprofile_admin_field'], 'bp_xprofile_admin_field' );

		if ( BP_XProfile_Field::admin_validate() ) {
			$field->is_required = $_POST['required'];
			$field->type        = $_POST['fieldtype'];
			$field->name        = $_POST['title'];

			if ( 'socialnetworks' === $field->type ) {

				if ( true === $new ) {
					$disabled_social_networks = false;
					$exists_social_networks   = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
					if ( $exists_social_networks > 0 ) {
						$disabled_social_networks = true;
					}

					if ( true === $disabled_social_networks ) {
						$message = __( 'You can only have one instance of the "Social Network" profile field.', 'buddyboss' );
						$type    = 'error';
						$field->render_admin_form( $message, $type );

						return false;
					}
				}
			}

			if ( ! empty( $_POST['description'] ) ) {
				$field->description = $_POST['description'];
			} else {
				$field->description = '';
			}

			if ( ! empty( $_POST[ "sort_order_{$field->type}" ] ) ) {
				$field->order_by = $_POST[ "sort_order_{$field->type}" ];
			}

			$field->field_order = $wpdb->get_var( $wpdb->prepare( "SELECT field_order FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ) );
			if ( ! is_numeric( $field->field_order ) || is_wp_error( $field->field_order ) ) {
				// cloned fields should not be considered when determining the max order of fields in given group
				$cloned_field_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT f.id FROM {$bp->profile->table_name_fields} AS f JOIN {$bp->profile->table_name_meta} AS fm ON f.id = fm.object_id "
						. " WHERE f.group_id = %d AND fm.meta_key = '_is_repeater_clone' AND fm.meta_value = 1 ",
						$group_id
					)
				);

				if ( ! empty( $cloned_field_ids ) ) {
					$field->field_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT max(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d AND id NOT IN ( " . implode( ',', $cloned_field_ids ) . ' )', $group_id ) );
				} else {
					$field->field_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT max(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id ) );
				}
				$field->field_order++;
			}

			// For new profile fields, set the $field_id. For existing profile
			// fields, this will overwrite $field_id with the same value.
			$field_id = $field->save();

			if ( empty( $field_id ) ) {
				if ( 'membertypes' === $field->type ) {
					$message = __( 'You can only have one instance of the "Profile Type" profile field.', 'buddyboss' );
					$type    = 'error';
					$field->render_admin_form( $message, $type );
					return false;
				} elseif ( 'gender' === $field->type ) {
					$message = __( 'You can only have one instance of the "Gender" profile field.', 'buddyboss' );
					$type    = 'error';
					$field->render_admin_form( $message, $type );
					return false;
				} elseif ( 'socialnetworks' === $field->type ) {
					$message = __( 'You can only have one instance of the "Social Network" profile field.', 'buddyboss' );
					$type    = 'error';
					$field->render_admin_form( $message, $type );
					return false;
				} else {
					$message = __( 'There was an error saving the field. Please try again.', 'buddyboss' );
				}
				$type = 'error';
			} else {
				$message = __( 'The field was saved successfully.', 'buddyboss' );
				$type    = 'updated';

				// Set profile types.
				if ( isset( $_POST['has-member-types'] ) ) {
					$member_types = array();
					if ( isset( $_POST['member-types'] ) ) {
						$member_types = stripslashes_deep( $_POST['member-types'] );
					}

					$field->set_member_types( $member_types );
				}

				// Set position of Gender fields option.
				if ( isset( $_POST['gender-option-order'] ) && ! empty( $_POST['gender-option-order'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'gender-option-order', $_POST['gender-option-order'] );
				}

				// Validate default visibility.
				if ( ! empty( $_POST['default-visibility'] ) && in_array( $_POST['default-visibility'], wp_list_pluck( bp_xprofile_get_visibility_levels(), 'id' ) ) ) {
					bp_xprofile_update_field_meta( $field_id, 'default_visibility', $_POST['default-visibility'] );
				}

				// Validate custom visibility.
				if ( ! empty( $_POST['allow-custom-visibility'] ) && in_array( $_POST['allow-custom-visibility'], array( 'allowed', 'disabled' ) ) ) {
					bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $_POST['allow-custom-visibility'] );
				}

				// Update alternate name.
				$alternate_name = isset( $_POST['title_secondary'] ) ? $_POST['title_secondary'] : '';
				bp_xprofile_update_field_meta( $field_id, 'alternate_name', $alternate_name );

				// Validate signup.
				if ( ! empty( $_POST['signup-position'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'signup_position', (int) $_POST['signup-position'] );
				} else {
					bp_xprofile_delete_meta( $field_id, 'field', 'signup_position' );
				}

				if ( $field->type_obj->do_settings_section() ) {
					$settings = isset( $_POST['field-settings'] ) ? wp_unslash( $_POST['field-settings'] ) : array();
					$field->admin_save_settings( $settings );
				}

				/**
				 * Fires at the end of the process to save a field for a user, if successful.
				 *
				 * @since BuddyPress 1.0.0
				 *
				 * @param BP_XProfile_Field $field Current BP_XProfile_Field object.
				 */
				do_action( 'xprofile_fields_saved_field', $field );

				$groups = bp_xprofile_get_groups();
			}

			$field->render_admin_form( $message, $type );

			// Users Admin URL
			$users_url = bp_get_admin_url( 'admin.php' );
			$redirect  = add_query_arg(
				array(
					'page'     => 'bp-profile-setup',
					'mode'     => 'edit_field',
					'group_id' => (int) $group_id,
					'type'     => $type,
					'field_id' => (int) $field_id,
				),
				$users_url
			);

			// wp_safe_redirect( $redirect );
			// exit();

		} else {
			$field->render_admin_form( $message );
		}
	} else {
		$field->render_admin_form();
	}
}

/**
 * Handles the deletion of a profile field (or field option).
 *
 * @since BuddyPress 1.0.0
 *
 * @global string $message The feedback message to show.
 * @global string $type The type of feedback message to show.
 *
 * @param int    $field_id    The field to delete.
 * @param string $field_type  The type of field being deleted.
 * @param bool   $delete_data Should the field data be deleted too.
 */
function xprofile_admin_delete_field( $field_id, $field_type = 'field', $delete_data = false ) {
	global $message, $type;

	// Switch type to 'option' if type is not 'field'.
	// @todo trust this param.
	$field_type = ( 'field' == $field_type ) ? __( 'field', 'buddyboss' ) : __( 'option', 'buddyboss' );
	$field      = xprofile_get_field( $field_id );

	if ( ! $field->delete( (bool) $delete_data ) ) {
		$message = sprintf( __( 'There was an error deleting the %s. Please try again.', 'buddyboss' ), $field_type );
		$type    = 'error';
	} else {
		$message = sprintf( __( 'The %s was deleted successfully!', 'buddyboss' ), $field_type );
		$type    = 'success';

		/**
		 * Fires at the end of the field deletion process, if successful.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_XProfile_Field $field Current BP_XProfile_Field object.
		 */
		do_action( 'xprofile_fields_deleted_field', $field );
	}

	xprofile_admin_screen( $message, $type );
}

/**
 * Handles the ajax reordering of fields within a group.
 *
 * @since BuddyPress 1.0.0
 */
function xprofile_ajax_reorder_fields() {

	// Check the nonce.
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );

	if ( empty( $_POST['field_order'] ) ) {
		return false;
	}

	parse_str( $_POST['field_order'], $order );

	$field_group_id = $_POST['field_group_id'];

	foreach ( (array) $order['draggable_field'] as $position => $field_id ) {
		xprofile_update_field_position( (int) $field_id, (int) $position, (int) $field_group_id );
	}
}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );

/**
 * Handles the reordering of field groups.
 *
 * @since BuddyPress 1.5.0
 */
function xprofile_ajax_reorder_field_groups() {

	// Check the nonce.
	check_admin_referer( 'bp_reorder_groups', '_wpnonce_reorder_groups' );

	if ( empty( $_POST['group_order'] ) ) {
		return false;
	}

	parse_str( $_POST['group_order'], $order );

	foreach ( (array) $order['group'] as $position => $field_group_id ) {
		xprofile_update_field_group_position( (int) $field_group_id, (int) $position );
	}
}
add_action( 'wp_ajax_xprofile_reorder_groups', 'xprofile_ajax_reorder_field_groups' );

/**
 * Check if the gender field has been added.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_check_gender_added_previously() {

	global $wpdb, $bp;

	$response            = array();
	$response['message'] = __( 'You can only have one instance of the "Gender" profile field.', 'buddyboss' );

	$referer = bb_filter_input_string( INPUT_POST, 'referer' );

	parse_str( $referer, $parsed_array );

	if ( 'edit_field' === $parsed_array['mode'] ) {

		$current_edit_id = intval( $parsed_array['field_id'] );

		$exists_gender = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$bp->profile->table_name_fields} a WHERE parent_id = 0 AND type = 'gender' " );
		if ( isset( $exists_gender[0] ) && intval( $exists_gender[0]->count ) > 0 ) {
			if ( $current_edit_id === intval( $exists_gender[0]->id ) ) {
				$response['status'] = 'not_added';
			} else {
				$response['status'] = 'added';
			}
		} else {
			$response['status'] = 'not_added';
		}
	} else {
		$exists_gender = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$bp->profile->table_name_fields} a WHERE parent_id = 0 AND type = 'gender' " );
		if ( isset( $exists_gender[0] ) && intval( $exists_gender[0]->count ) > 0 ) {
			$response['status'] = 'added';
		} else {
			$response['status'] = 'not_added';
		}
	}
	echo wp_json_encode( $response );
	wp_die();
}
add_action( 'wp_ajax_xprofile_check_gender_added_previously', 'xprofile_check_gender_added_previously' );

/**
 * Handles the WYSIWYG display of each profile field on the edit screen.
 *
 * @since BuddyPress 1.5.0
 *
 * @param BP_XProfile_Field $admin_field Admin field.
 * @param object            $admin_group Admin group object.
 * @param string            $class       Classes to append to output.
 */
function xprofile_admin_field( $admin_field, $admin_group, $class = '' ) {
	global $field;

	$field = $admin_field;

	// Users admin URL
	$url = bp_get_admin_url( 'admin.php' );

	// Edit
	$field_edit_url = add_query_arg(
		array(
			'page'     => 'bp-profile-setup',
			'mode'     => 'edit_field',
			'group_id' => (int) $field->group_id,
			'field_id' => (int) $field->id,
		),
		$url
	);

	// Delete
	if ( $field->can_delete ) {
		$field_delete_url = add_query_arg(
			array(
				'page'     => 'bp-profile-setup',
				'mode'     => 'delete_field',
				'field_id' => (int) $field->id,
			),
			$url . '#tabs-' . (int) $field->group_id
		);
	}

	$fieldset_class = array( $field->type );

	// sortable class
	$fieldset_class[] = in_array(
		$field->id,
		array_filter(
			array(
				bp_xprofile_firstname_field_id(),
				bp_xprofile_lastname_field_id(),
				bp_xprofile_nickname_field_id(),
			)
		)
	) ? 'primary_field sortable' : 'sortable';

	$fieldset_class[] = ! empty( $class ) ? $class : '';
	$fieldset_class   = array_filter( $fieldset_class );
	?>

	<fieldset id="draggable_field_<?php echo esc_attr( $field->id ); ?>" class="<?php echo implode( ' ', $fieldset_class ); ?>">
		<legend>
			<span>
				<span class="field-name"><?php bp_the_profile_field_name(); ?></span>

				<?php if ( empty( $field->can_delete ) ) : ?>
					<span class="bp-signup-field-label">
					<?php
					esc_html_e( '(Signup)', 'buddyboss' );
endif;
				?>
				</span>
				<?php bp_the_profile_field_required_label(); ?>
				<?php if ( bp_xprofile_get_meta( $field->id, 'field', 'signup_position' ) ) : ?>
					<span class="bp-signup-field-label">
					<?php
					esc_html_e( '(Signup)', 'buddyboss' );
endif;
				?>
				</span>
				<?php
				if ( bp_get_member_types() ) :
					echo $field->get_member_type_label();
endif;
				?>

				<?php

				/**
				 * Fires at end of legend above the name field in base xprofile group.
				 *
				 * @since BuddyPress 2.2.0
				 *
				 * @param BP_XProfile_Field $field Current BP_XProfile_Field
				 *                                 object being rendered.
				 */
				do_action( 'xprofile_admin_field_name_legend', $field );
				?>
			</span>
		</legend>
		<div class="field-wrapper">

			<?php if ( $field->description ) : ?>

				<p class="description"><?php echo esc_attr( $field->description ); ?></p>

			<?php endif; ?>

			<div class="actions">
				<a class="button edit" href="<?php echo esc_url( $field_edit_url ); ?>"><?php _e( 'Edit', 'buddyboss' ); ?></a>

				<?php if ( $field->can_delete ) : ?>

					<div class="delete-button">
						<a class="confirm submit-delete deletion bb-delete-profile-field" href="<?php echo esc_url( $field_delete_url ); ?>"><?php _e( 'Delete', 'buddyboss' ); ?></a>
					</div>

				<?php endif; ?>

				<?php

				/**
				 * Fires at end of field management links in xprofile management admin.
				 *
				 * @since BuddyPress 2.2.0
				 *
				 * @param BP_XProfile_Group $group BP_XProfile_Group object
				 *                                 for the current group.
				 */
				do_action( 'xprofile_admin_field_action', $field );
				?>

			</div>
		</div>
	</fieldset>

	<?php
}

/**
 * Print <option> elements containing the xprofile field types.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $select_field_type The name of the field type that should be selected.
 *                                  Will defaults to "textbox" if NULL is passed.
 */
function bp_xprofile_admin_form_field_types( $select_field_type ) {
	$categories = array();

	if ( is_null( $select_field_type ) ) {
		$select_field_type = 'textbox';
	}

	// Sort each field type into its category.
	foreach ( bp_xprofile_get_field_types() as $field_name => $field_class ) {
		$field_type_obj = new $field_class();
		$the_category   = $field_type_obj->category;

		// Fallback to a catch-all if category not set.
		if ( ! $the_category ) {
			$the_category = __( 'Other', 'buddyboss' );
		}

		if ( isset( $categories[ $the_category ] ) ) {
			$categories[ $the_category ][] = array( $field_name, $field_type_obj );
		} else {
			$categories[ $the_category ] = array( array( $field_name, $field_type_obj ) );
		}
	}

	// Sort the categories alphabetically. ksort()'s SORT_NATURAL is only in PHP >= 5.4 :((.
	uksort( $categories, 'strnatcmp' );

	// Disable Gender field if that is already added previously.
	global $wpdb;
	global $bp;
	$disabled_gender = false;
	$exists_gender   = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'gender' " );
	if ( $exists_gender > 0 ) {
		$disabled_gender = true;
	}

	$disabled_social_networks = false;
	$exists_social_networks   = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
	if ( $exists_social_networks > 0 ) {
		$disabled_social_networks = true;
	}

	// Loop through each category and output form <options>.
	foreach ( $categories as $category => $fields ) {
		printf( '<optgroup label="%1$s">', esc_attr( $category ) );  // Already i18n'd in each profile type class.

		// Sort these fields types alphabetically.
		uasort(
			$fields,
			function( $a, $b ) {
				return strnatcmp( $a[1]->name, $b[1]->name );
			}
		);

		foreach ( $fields as $field_type_obj ) {
			$field_name     = $field_type_obj[0];
			$field_type_obj = $field_type_obj[1];
			if ( 'gender' === $field_name && true === $disabled_gender ) {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $field_name ), selected( $select_field_type, $field_name, false ), esc_html( $field_type_obj->name ) );
			} elseif ( 'socialnetworks' === $field_name && true === $disabled_social_networks ) {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $field_name ), selected( $select_field_type, $field_name, false ), esc_html( $field_type_obj->name ) );
			} else {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $field_name ), selected( $select_field_type, $field_name, false ), esc_html( $field_type_obj->name ) );
			}
		}

		printf( '</optgroup>' );
	}
}

// Load the xprofile user admin.
add_action( 'bp_init', array( 'BP_XProfile_User_Admin', 'register_xprofile_user_admin' ), 11 );

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_users_tabs( $active_tab = '' ) {

	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_users_tabs', bp_core_get_users_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_admin_groups_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Groups screens.
 *
 * @param string $active_tab
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_core_get_users_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	// Check profile type enabled.
	$is_member_type_enabled = bp_member_type_enable_disable();

	// Check profile search enabled.
	$is_profile_search_enabled = bp_disable_advanced_profile_search();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-profile-setup' ), 'admin.php' ) ),
		'name'  => __( 'Profile Fields', 'buddyboss' ),
		'class' => 'bp-profile-fields',
	);

	if ( true === $is_member_type_enabled ) {

		if ( is_network_admin() && bp_is_network_activated() ) {
			$profile_url = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=bp-member-type' );
		} else {
			$profile_url = bp_get_admin_url( add_query_arg( array( 'post_type' => 'bp-member-type' ), 'edit.php' ) );
		}

		$tabs[] = array(
			'href'  => $profile_url,
			'name'  => __( 'Profile Types', 'buddyboss' ),
			'class' => 'bp-profile-types',
		);
	}

	if ( false === $is_profile_search_enabled ) {
		$tabs[] = array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'post_type' => 'bp_ps_form' ), 'edit.php' ) ),
			'name'  => __( 'Profile Search', 'buddyboss' ),
			'class' => 'bp-profile-search',
		);
	}

	$query['autofocus[section]'] = 'bp_nouveau_user_primary_nav';
	$section_link                = add_query_arg( $query, admin_url( 'customize.php' ) );
	$tabs[]                      = array(
		'href'  => esc_url( $section_link ),
		'name'  => __( 'Profile Navigation', 'buddyboss' ),
		'class' => 'bp-user-customizer',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_users_admin_tabs', $tabs );
}

/**
 * Added Navigation tab on top of the page BuddyBoss > Group Types
 *
 * @since BuddyBoss 1.0.0
 */
function bp_users_admin_profile_types_listing_add_users_tab() {
	global $pagenow ,$post;

	// Check profile type enabled.
	$is_member_type_enabled = bp_member_type_enable_disable();

	if ( true === $is_member_type_enabled ) {

		if ( ( isset( $GLOBALS['wp_list_table']->screen->post_type ) && $GLOBALS['wp_list_table']->screen->post_type == 'bp-member-type' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'post.php' ) ) {
			?>
			<div class="wrap">
				<?php
				$users_tab = count( bp_core_get_users_admin_tabs() );
				if ( $users_tab > 1 ) {
					?>
					<h2 class="nav-tab-wrapper"><?php bp_core_admin_users_tabs( __( 'Profile Types', 'buddyboss' ) ); ?></h2>
																				<?php
				}
				?>
			</div>
			<?php
		}
	}
}
add_action( 'admin_notices', 'bp_users_admin_profile_types_listing_add_users_tab' );

add_filter( 'parent_file', 'bp_profile_type_set_platform_tab_submenu_active' );
/**
 * Highlights the submenu item using WordPress native styles.
 *
 * @param string $parent_file The filename of the parent menu.
 *
 * @return string $parent_file The filename of the parent menu.
 */
function bp_profile_type_set_platform_tab_submenu_active( $parent_file ) {
	global $pagenow, $current_screen, $post;

	if ( true === bp_member_type_enable_disable() ) {
		if ( ( isset( $GLOBALS['wp_list_table']->screen->post_type ) && $GLOBALS['wp_list_table']->screen->post_type == 'bp-member-type' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp-member-type' && $pagenow == 'post.php' ) ) {
			$parent_file = 'buddyboss-platform';
		}
	}
	return $parent_file;
}

/**
 * Check if the social networks field has been added.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_check_social_networks_added_previously() {

	global $wpdb, $bp;

	$response            = array();
	$response['message'] = __( 'You can only have one instance of the "Social Networks" profile field on the website.', 'buddyboss' );
	$referer             = bb_filter_input_string( INPUT_POST, 'referer' );

	parse_str( $referer, $parsed_array );

	if ( 'edit_field' === $parsed_array['mode'] ) {

		$current_edit_id = intval( $parsed_array['field_id'] );

		$exists_social_networks = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
		if ( isset( $exists_social_networks[0] ) && intval( $exists_social_networks[0]->count ) > 0 ) {
			if ( $current_edit_id === intval( $exists_social_networks[0]->id ) ) {
				$response['status'] = 'not_added';
			} else {
				$response['status'] = 'added';
			}
		} else {
			$response['status'] = 'not_added';
		}
	} else {
		$exists_social_networks = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );
		if ( isset( $exists_social_networks[0] ) && intval( $exists_social_networks[0]->count ) > 0 ) {
			$response['status'] = 'added';
		} else {
			$response['status'] = 'not_added';
		}
	}
	echo wp_json_encode( $response );
	wp_die();
}
add_action( 'wp_ajax_xprofile_check_social_networks_added_previously', 'xprofile_check_social_networks_added_previously' );

/**
 * Check if the member type field has been added.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_check_member_type_added_previously() {

	global $wpdb;

	$response            = array();
	$response['message'] = __( 'You can only have one instance of the "Profile Type" profile field.', 'buddyboss' );

	$referer = bb_filter_input_string( INPUT_POST, 'referer' );

	parse_str( $referer, $parsed_array );

	if ( 'edit_field' === $parsed_array['mode'] ) {

		$current_edit_id = intval( $parsed_array['field_id'] );

		$exists_member_type = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$wpdb->base_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'membertypes' " );
		if ( intval( $exists_member_type[0]->count ) > 0 ) {
			if ( $current_edit_id === intval( $exists_member_type[0]->id ) ) {
				$response['status'] = 'not_added';
			} else {
				$response['status'] = 'added';
			}
		} else {
			$response['status'] = 'not_added';
		}
	} else {
		$exists_member_type = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$wpdb->base_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'membertypes' " );
		if ( intval( $exists_member_type[0]->count ) > 0 ) {
			$response['status'] = 'added';
		} else {
			$response['status'] = 'not_added';
		}
	}
	echo wp_json_encode( $response );
	wp_die();
}
add_action( 'wp_ajax_xprofile_check_member_type_added_previously', 'xprofile_check_member_type_added_previously' );

/**
 * Save repeater option temporary before save group details.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param object|BP_XProfile_Group $xprofile Current instance of the group being saved.
 */
function bb_xprofile_before_save_xprofile_group_details( $xprofile ) {
	// Save the previous data to use later.
	$is_repeater_enabled = BP_XProfile_Group::get_group_meta( $xprofile->id, 'is_repeater_enabled' );
	bp_update_option( 'xprofile_group_' . $xprofile->id, $is_repeater_enabled );
}
add_action( 'xprofile_group_before_save', 'bb_xprofile_before_save_xprofile_group_details', 11, 1 );

/**
 * Migrating the user simple data's to repeater fields data's.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param object|BP_XProfile_Group $xprofile Current instance of the group being saved.
 */
function bb_xprofile_migrate_simple_to_repeater_fields_data( $xprofile ) {
	global $wpdb, $bp, $bp_background_updater;

	$repeater_enabled = isset( $_POST['group_is_repeater'] ) && 'on' == $_POST['group_is_repeater'] ? 'on' : 'off';
	$previous_value   = bp_get_option( 'xprofile_group_' . $xprofile->id );

	if (
		'on' === $repeater_enabled &&
		'on' !== $previous_value &&
		! empty( $xprofile->id )
	) {
		$repeater_template_fields = bp_get_repeater_template_field_ids( $xprofile->id );

		// Check if clone fields not created then create it.
		$repeater_fields = bp_get_repeater_clone_field_ids_all( $xprofile->id );
		if ( empty( $repeater_fields ) ) {
			$user_field_set_count = bp_get_profile_field_set_count( $xprofile->id, get_current_user_id() );
			bp_get_repeater_clone_field_ids_subset( $xprofile->id, $user_field_set_count );
		}

		if ( ! empty( $repeater_template_fields ) ) {
			$repeater_template_fields_in = "'" . implode( "','", $repeater_template_fields ) . "'";

			$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data} WHERE field_id IN ({$repeater_template_fields_in})" );

			if ( ! empty( $user_ids ) ) {

				$min_count = (int) apply_filters( 'bb_xprofile_migrate_repeater_queue_min_count', 10 );
				if ( $min_count && count( $user_ids ) > $min_count ) {

					$chunk_user_ids = array_chunk( $user_ids, $min_count );
					if ( ! empty( $chunk_user_ids ) ) {
						foreach ( $chunk_user_ids as $chunked_user_ids ) {
							$bp_background_updater->data(
								array(
									array(
										'callback' => 'bb_xprofile_mapping_simple_to_repeater_fields_data',
										'args'     => array( $chunked_user_ids, $xprofile->id ),
									),
								)
							);
							$bp_background_updater->save();
						}
					}

					$bp_background_updater->dispatch();
				} else {
					bb_xprofile_mapping_simple_to_repeater_fields_data( $user_ids, $xprofile->id );
				}
			}
		}
	}

	// Delete the option.
	bp_delete_option( 'xprofile_group_' . $xprofile->id );
}
add_action( 'xprofile_group_after_save', 'bb_xprofile_migrate_simple_to_repeater_fields_data', 11, 1 );

/**
 * Prepare data to insert into repeater fields.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param array $user_ids Array of user ID's.
 * @param int   $group_id Xprofile group ID.
 */
function bb_xprofile_mapping_simple_to_repeater_fields_data( $user_ids, $group_id ) {
	if ( ! empty( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {
			$clone_fields = bp_get_repeater_clone_field_ids_subset( $group_id, 1 );
			if ( ! empty( $clone_fields ) ) {
				foreach ( $clone_fields as $clone_field_id ) {
					$data              = xprofile_get_field_data( $clone_field_id, $user_id );
					$template_field_id = bp_xprofile_get_meta( $clone_field_id, 'field', '_cloned_from', true );
					if ( empty( $data ) && ! empty( $template_field_id ) && null !== bb_xprofile_get_field_type( $template_field_id ) ) {
						xprofile_set_field_data( $clone_field_id, $user_id, xprofile_get_field_data( $template_field_id, $user_id ) );
					}
				}
			}
		}
	}
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @since BuddyBoss 2.3.80
 *
 * @param array $args List of removable query arguments.
 *
 * @return array Updated list of removable query arguments.
 */
function bb_xprofile_admin_removable_query_vars( $args ) {

	// What mode?
	$mode = ! empty( $_GET['mode'] ) ? sanitize_key( $_GET['mode'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( empty( $mode ) ) {
		return $args;
	}

	// Allowed modes.
	$allowed_modes = array(
		'delete_group',
		'delete_field',
		'delete_option',
	);

	// Is an allowed mode.
	if ( in_array( $mode, $allowed_modes, true ) ) {
		$args = array_merge( $args, array( 'mode', 'field_id', 'group_id', '_wpnonce' ) );
	}

	if ( isset( $_POST['save_group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args = array_merge( $args, array( 'mode' ) );
	}

	if ( isset( $_POST['saveField'] ) && ! isset( $_GET['field_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args = array_merge( $args, array( 'mode', 'group_id' ) );
	}

	return $args;
}
add_filter( 'removable_query_args', 'bb_xprofile_admin_removable_query_vars' );
