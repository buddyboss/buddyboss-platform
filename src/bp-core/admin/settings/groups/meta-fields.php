<?php
/**
 * BuddyBoss Admin Settings - Groups Meta Fields Registration.
 *
 * Registers core group fields for the Group Edit modal
 * in the Settings 2.0 admin interface via BB_Admin_Meta_Field_Registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core group meta fields for the edit modal.
 *
 * Hooks into `bb_register_groups_meta_fields` at priority 1 so Platform fields
 * come first. Pro extensions register at priority 10+.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
 * @param string                       $component The component identifier.
 */
function bb_groups_register_core_meta_fields( $registry, $component ) {

	// =========================================================================
	// TAB: Details (order 10–50).
	// =========================================================================

	// Name.
	$registry->register(
		$component,
		'name',
		array(
			'label'             => __( 'Name', 'buddyboss-platform' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 10,
			'save_phase'        => 'before',
			'get_value'         => function ( $group ) {
				return $group->name;
			},
			'save_value'        => function ( $group, $value ) {
				$group->name = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Slug (Permalink).
	$registry->register(
		$component,
		'slug',
		array(
			'label'             => __( 'Permalink', 'buddyboss-platform' ),
			'type'              => 'permalink',
			'tab'               => 'details',
			'order'             => 20,
			'save_phase'        => 'before',
			'get_value'         => function ( $group ) {
				return $group->slug;
			},
			'get_extra_data'    => function ( $group ) {
				return array(
					'base_url' => bp_get_groups_directory_permalink(),
				);
			},
			'save_value'        => function ( $group, $value ) {
				$new_slug = sanitize_title( $value );

				// Only check for slug conflicts when it actually changed,
				// matching legacy groups_edit_base_group_details() behavior.
				if ( $new_slug && strtolower( $new_slug ) !== strtolower( $group->slug ) ) {
					$group->slug = groups_check_slug( $new_slug );
				}
			},
			'sanitize_callback' => 'sanitize_title',
		)
	);

	// Description.
	$registry->register(
		$component,
		'description',
		array(
			'label'             => __( 'Description (Optional)', 'buddyboss-platform' ),
			'type'              => 'richtext',
			'tab'               => 'details',
			'order'             => 30,
			'save_phase'        => 'before',
			'get_value'         => function ( $group ) {
				return $group->description;
			},
			'save_value'        => function ( $group, $value ) {
				$group->description = $value;
			},
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	// Status (Privacy).
	$registry->register(
		$component,
		'status',
		array(
			'label'             => __( 'Group Privacy', 'buddyboss-platform' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 40,
			'save_phase'        => 'before',
			'get_value'         => function ( $group ) {
				return $group->status;
			},
			'get_options'       => function ( $group ) {
				$options = array(
					array(
						'value' => 'public',
						'label' => __( 'Public', 'buddyboss-platform' ),
					),
					array(
						'value' => 'private',
						'label' => __( 'Private', 'buddyboss-platform' ),
					),
					array(
						'value' => 'hidden',
						'label' => __( 'Hidden', 'buddyboss-platform' ),
					),
				);

				/**
				 * Filters the allowed group statuses.
				 *
				 * @since BuddyBoss 3.0.0
				 *
				 * @param array $options Status options.
				 * @param object $group  The group object.
				 */
				return apply_filters( 'bb_admin_group_edit_status_options', $options, $group );
			},
			'save_value'        => function ( $group, $value ) {
				// Status is handled by groups_edit_group_settings(), just store on object.
				$group->status = $value;
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Group Type.
	$registry->register(
		$component,
		'group_type',
		array(
			'label'             => __( 'Group Type (Optional)', 'buddyboss-platform' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 50,
			'save_phase'        => 'after',
			'is_visible'        => function ( $group ) {
				$types = bp_groups_get_group_types( array(), 'objects' );
				return ! empty( $types );
			},
			'get_value'         => function ( $group ) {
				return bp_groups_get_group_type( $group->id, true );
			},
			'get_options'       => function ( $group ) {
				$options     = array(
					array(
						'value' => '',
						'label' => __( 'Select Group Type', 'buddyboss-platform' ),
					),
				);
				$type_objects = bp_groups_get_group_types( array(), 'objects' );
				foreach ( $type_objects as $type_obj ) {
					$options[] = array(
						'value' => $type_obj->name,
						'label' => $type_obj->labels['singular_name'],
					);
				}
				return $options;
			},
			'save_value'        => function ( $group, $value ) {
				$current_type = bp_groups_get_group_type( $group->id, true );
				if ( ! empty( $value ) ) {
					bp_groups_set_group_type( $group->id, sanitize_key( $value ) );
				} elseif ( ! empty( $current_type ) ) {
					bp_groups_remove_group_type( $group->id, $current_type );
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// =========================================================================
	// TAB: Permissions (order 310–360).
	// =========================================================================

	$permission_fields = array(
		array(
			'id'        => 'perm_invite',
			'label'     => __( 'Who can invite others to join this group?', 'buddyboss-platform' ),
			'meta_key'  => 'invite_status',
			'type_slug' => 'invite',
			'order'     => 310,
			'get_fn'    => 'bp_group_get_invite_status',
		),
		array(
			'id'         => 'perm_activity_feed',
			'label'      => __( 'Who can post into this group?', 'buddyboss-platform' ),
			'meta_key'   => 'activity_feed_status',
			'type_slug'  => 'activity_feed',
			'order'      => 320,
			'get_fn'     => 'bp_group_get_activity_feed_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'activity' );
			},
		),
		array(
			'id'         => 'perm_media',
			'label'      => __( 'Who can upload photos in this group?', 'buddyboss-platform' ),
			'meta_key'   => 'media_status',
			'type_slug'  => 'media',
			'order'      => 330,
			'get_fn'     => 'bp_group_get_media_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'media' ) && bp_is_group_media_support_enabled();
			},
		),
		array(
			'id'         => 'perm_album',
			'label'      => __( 'Who can create albums in this group?', 'buddyboss-platform' ),
			'meta_key'   => 'album_status',
			'type_slug'  => 'album',
			'order'      => 340,
			'get_fn'     => 'bp_group_get_album_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'media' ) && bp_is_group_albums_support_enabled();
			},
		),
		array(
			'id'         => 'perm_document',
			'label'      => __( 'Who can upload documents in this group?', 'buddyboss-platform' ),
			'meta_key'   => 'document_status',
			'type_slug'  => 'document',
			'order'      => 350,
			'get_fn'     => 'bp_group_get_document_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'media' ) && bp_is_group_document_support_enabled();
			},
		),
		array(
			'id'         => 'perm_video',
			'label'      => __( 'Who can upload videos in this group?', 'buddyboss-platform' ),
			'meta_key'   => 'video_status',
			'type_slug'  => 'video',
			'order'      => 355,
			'get_fn'     => 'bp_group_get_video_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'video' ) && function_exists( 'bp_is_group_video_support_enabled' ) && bp_is_group_video_support_enabled();
			},
		),
		array(
			'id'         => 'perm_message',
			'label'      => __( 'Who can manage group messages in this group?', 'buddyboss-platform' ),
			'meta_key'   => 'message_status',
			'type_slug'  => 'message',
			'order'      => 360,
			'get_fn'     => 'bp_group_get_message_status',
			'is_visible' => function ( $group ) {
				return bp_is_active( 'messages' ) && bp_disable_group_messages();
			},
		),
	);

	foreach ( $permission_fields as $perm ) {
		$field_args = array(
			'label'             => $perm['label'],
			'type'              => 'radio',
			'tab'               => 'permissions',
			'order'             => $perm['order'],
			'save_phase'        => 'after',
			'get_value'         => function ( $group ) use ( $perm ) {
				if ( function_exists( $perm['get_fn'] ) ) {
					return call_user_func( $perm['get_fn'], $group->id );
				}
				return groups_get_groupmeta( $group->id, $perm['meta_key'] );
			},
			'get_options'       => function ( $group ) use ( $perm ) {
				$statuses = bb_groups_get_settings_status( $perm['type_slug'] );
				$labels   = array(
					'members' => __( 'All Members', 'buddyboss-platform' ),
					'mods'    => __( 'Organizers and Moderators', 'buddyboss-platform' ),
					'admins'  => __( 'Organizers', 'buddyboss-platform' ),
				);

				$options = array();
				foreach ( $statuses as $status ) {
					$options[] = array(
						'value' => $status,
						'label' => isset( $labels[ $status ] ) ? $labels[ $status ] : $status,
					);
				}
				return $options;
			},
			// No-op: actual save happens via groups_edit_group_settings() in save_group().
			// Defined so the field is not marked readonly and JS includes it in POST payload.
			'save_value'        => function ( $group, $value ) {
				// No-op: actual save via groups_edit_group_settings() in save_group().
			},
			'sanitize_callback' => 'sanitize_key',
		);

		// Add is_visible callback if the permission field defines one.
		if ( isset( $perm['is_visible'] ) && is_callable( $perm['is_visible'] ) ) {
			$field_args['is_visible'] = $perm['is_visible'];
		}

		$registry->register( $component, $perm['id'], $field_args );
	}

	// =========================================================================
	// TAB: Integrations (order 450–470).
	// =========================================================================

	// Parent Group (hierarchies) — async searchable select with load-more
	// pagination. The legacy `select` type loaded every possible parent up
	// front via bp_get_possible_parent_groups(); on sites with thousands of
	// groups that's an O(n) blocking query on every modal open. Async drops
	// the cost to one search-paged query per keystroke (debounced).
	$registry->register(
		$component,
		'parent_id',
		array(
			'label'             => __( 'Group Parent (Optional)', 'buddyboss-platform' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_group_parent_autocomplete',
			'placeholder'       => __( 'Select Parent', 'buddyboss-platform' ),
			'tab'               => 'integrations',
			'order'             => 450,
			'save_phase'        => 'before',
			'is_visible'        => function ( $group ) {
				return bp_enable_group_hierarchies();
			},
			'get_value'         => function ( $group ) {
				return (int) $group->parent_id;
			},
			'save_value'        => function ( $group, $value ) {
				$group->parent_id = absint( $value );
			},
			'sanitize_callback' => 'absint',
		)
	);

	// Enable Forum.
	$registry->register(
		$component,
		'enable_forum',
		array(
			'label'             => __( 'Allow this group to have a discussion forum', 'buddyboss-platform' ),
			'description'       => __( 'Connect a discussion forum to allow members of this group to communicate in a structured, bulletin-board style fashion. Unchecking this option will not delete existing forum content.', 'buddyboss-platform' ),
			'type'              => 'checkbox',
			'tab'               => 'integrations',
			'order'             => 460,
			'save_phase'        => 'before',
			'is_visible'        => function ( $group ) {
				return bp_is_active( 'forums' ) && function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active();
			},
			'get_value'         => function ( $group ) {
				return (int) $group->enable_forum;
			},
			'save_value'        => function ( $group, $value ) {
				$group->enable_forum = absint( $value );
			},
			'sanitize_callback' => 'absint',
		)
	);

	// Connected Forum — async searchable select with load more pagination.
	// Uses bb_admin_forum_autocomplete AJAX endpoint instead of loading all forums upfront.
	$registry->register(
		$component,
		'forum_id',
		array(
			'label'             => __( 'Forum', 'buddyboss-platform' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_forum_autocomplete',
			'placeholder'       => __( 'Search forums…', 'buddyboss-platform' ),
			'tab'               => 'integrations',
			'order'             => 470,
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'enable_forum',
				'value' => '1',
			),
			'is_visible'        => function ( $group ) {
				return bp_is_active( 'forums' )
					&& function_exists( 'bbp_is_group_forums_active' )
					&& bbp_is_group_forums_active()
					&& function_exists( 'bbp_is_user_keymaster' )
					&& bbp_is_user_keymaster();
			},
			'get_value'         => function ( $group ) {
				if ( ! function_exists( 'bbp_get_group_forum_ids' ) ) {
					return 0;
				}
				$forum_ids = bbp_get_group_forum_ids( $group->id );
				return ! empty( $forum_ids ) ? (int) current( $forum_ids ) : 0;
			},
			'save_value'        => function ( $group, $value ) {
				if ( ! function_exists( 'bbp_get_group_forum_ids' ) || ! function_exists( 'bbp_update_group_forum_ids' ) || ! function_exists( 'bbp_update_forum_group_ids' ) ) {
					return;
				}

				$new_forum_id = absint( $value );

				// Verify the new forum ID is actually a forum post type (prevent arbitrary post association).
				if ( ! empty( $new_forum_id ) ) {
					$forum_post = get_post( $new_forum_id );
					if ( ! $forum_post || bbp_get_forum_post_type() !== $forum_post->post_type ) {
						return;
					}
				}

				$old_forum_ids = bbp_get_group_forum_ids( $group->id );
				$old_forum_id  = ! empty( $old_forum_ids ) ? (int) current( $old_forum_ids ) : 0;

				// No change.
				if ( $new_forum_id === $old_forum_id ) {
					return;
				}

				// Remove group from old forum's group IDs.
				if ( ! empty( $old_forum_id ) ) {
					$old_group_ids = bbp_get_forum_group_ids( $old_forum_id );
					$old_group_ids = array_diff( $old_group_ids, array( $group->id ) );
					bbp_update_forum_group_ids( $old_forum_id, array_values( $old_group_ids ) );
				}

				// Update group → forum relationship.
				if ( ! empty( $new_forum_id ) ) {
					bbp_update_group_forum_ids( $group->id, array( $new_forum_id ) );

					// Add group to new forum's group IDs.
					$new_group_ids   = bbp_get_forum_group_ids( $new_forum_id );
					$new_group_ids[] = $group->id;
					bbp_update_forum_group_ids( $new_forum_id, array_unique( $new_group_ids ) );
				} else {
					bbp_update_group_forum_ids( $group->id, array() );
				}
			},
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'bb_register_groups_meta_fields', 'bb_groups_register_core_meta_fields', 1, 2 );
