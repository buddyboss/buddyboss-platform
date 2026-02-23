<?php
/**
 * BuddyBoss Admin Settings - Groups Meta Fields Registration.
 *
 * Registers core group fields for the Group Edit modal
 * in the Settings 2.0 admin interface via BB_Admin_Meta_Field_Registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core group meta fields for the edit modal.
 *
 * Hooks into `bb_register_groups_meta_fields` at priority 1 so Platform fields
 * come first. Pro extensions register at priority 10+.
 *
 * @since BuddyBoss [BBVERSION]
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
			'label'             => __( 'Name', 'buddyboss' ),
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
			'label'             => __( 'Permalink', 'buddyboss' ),
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
				$group->slug = groups_check_slug( sanitize_title( $value ) );
			},
			'sanitize_callback' => 'sanitize_title',
		)
	);

	// Description.
	$registry->register(
		$component,
		'description',
		array(
			'label'             => __( 'Description (Optional)', 'buddyboss' ),
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
			'label'             => __( 'Group Privacy', 'buddyboss' ),
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
						'label' => __( 'Public', 'buddyboss' ),
					),
					array(
						'value' => 'private',
						'label' => __( 'Private', 'buddyboss' ),
					),
					array(
						'value' => 'hidden',
						'label' => __( 'Hidden', 'buddyboss' ),
					),
				);

				/**
				 * Filters the allowed group statuses.
				 *
				 * @since BuddyBoss [BBVERSION]
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
			'label'             => __( 'Group Type (Optional)', 'buddyboss' ),
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
						'label' => __( 'Select Group Type', 'buddyboss' ),
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
			'label'     => __( 'Who can invite others to join this group?', 'buddyboss' ),
			'meta_key'  => 'invite_status',
			'type_slug' => 'invite',
			'order'     => 310,
			'get_fn'    => 'bp_group_get_invite_status',
		),
		array(
			'id'        => 'perm_activity_feed',
			'label'     => __( 'Who can post into this group?', 'buddyboss' ),
			'meta_key'  => 'activity_feed_status',
			'type_slug' => 'activity_feed',
			'order'     => 320,
			'get_fn'    => 'bp_group_get_activity_feed_status',
		),
		array(
			'id'        => 'perm_media',
			'label'     => __( 'Who can upload photos in this group?', 'buddyboss' ),
			'meta_key'  => 'media_status',
			'type_slug' => 'media',
			'order'     => 330,
			'get_fn'    => 'bp_group_get_media_status',
		),
		array(
			'id'        => 'perm_album',
			'label'     => __( 'Who can create albums in this group?', 'buddyboss' ),
			'meta_key'  => 'album_status',
			'type_slug' => 'album',
			'order'     => 340,
			'get_fn'    => 'bp_group_get_album_status',
		),
		array(
			'id'        => 'perm_document',
			'label'     => __( 'Who can upload documents in this group?', 'buddyboss' ),
			'meta_key'  => 'document_status',
			'type_slug' => 'document',
			'order'     => 350,
			'get_fn'    => 'bp_group_get_document_status',
		),
		array(
			'id'        => 'perm_message',
			'label'     => __( 'Who can manage group messages in this group?', 'buddyboss' ),
			'meta_key'  => 'message_status',
			'type_slug' => 'message',
			'order'     => 360,
			'get_fn'    => 'bp_group_get_message_status',
		),
	);

	foreach ( $permission_fields as $perm ) {
		$registry->register(
			$component,
			$perm['id'],
			array(
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
						'members' => __( 'All Members', 'buddyboss' ),
						'mods'    => __( 'Organizers and Moderators', 'buddyboss' ),
						'admins'  => __( 'Organizers', 'buddyboss' ),
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
				'save_value'        => function ( $group, $value ) use ( $perm ) {
					$allowed = bb_groups_get_settings_status( $perm['type_slug'] );
					if ( in_array( $value, $allowed, true ) ) {
						groups_update_groupmeta( $group->id, $perm['meta_key'], $value );
					}
				},
				'sanitize_callback' => 'sanitize_key',
			)
		);
	}

	// =========================================================================
	// TAB: Integrations (order 450–470).
	// =========================================================================

	// Parent Group (hierarchies).
	$registry->register(
		$component,
		'parent_id',
		array(
			'label'             => __( 'Group Parent (Optional)', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'integrations',
			'order'             => 450,
			'save_phase'        => 'before',
			'is_visible'        => function ( $group ) {
				return bp_enable_group_hierarchies();
			},
			'get_value'         => function ( $group ) {
				return (int) $group->parent_id;
			},
			'get_options'       => function ( $group ) {
				$options = array(
					array(
						'value' => '0',
						'label' => __( 'Select Parent', 'buddyboss' ),
					),
				);

				$possible_parents = bp_get_possible_parent_groups( $group->id, bp_loggedin_user_id() );
				if ( ! empty( $possible_parents ) ) {
					foreach ( $possible_parents as $possible_parent ) {
						$options[] = array(
							'value' => (string) $possible_parent->id,
							'label' => $possible_parent->name,
						);
					}
				}
				return $options;
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
			'label'             => __( 'Allow this group to have a discussion forum', 'buddyboss' ),
			'description'       => __( 'Connect a discussion forum to allow members of this group to communicate in a structured, bulletin-board style fashion. Unchecking this option will not delete existing forum content.', 'buddyboss' ),
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

	// Connected Forum (select which forum is linked).
	$registry->register(
		$component,
		'forum_id',
		array(
			'label'             => __( 'Forum', 'buddyboss' ),
			'type'              => 'select',
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
			'get_options'       => function ( $group ) {
				$options = array(
					array(
						'value' => '0',
						'label' => __( 'Select Forum', 'buddyboss' ),
					),
				);

				if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
					return $options;
				}

				$forums = get_posts(
					array(
						'post_type'      => bbp_get_forum_post_type(),
						'posts_per_page' => -1,
						'orderby'        => 'menu_order title',
						'order'          => 'ASC',
						'post_status'    => array( 'publish', 'private', 'hidden' ),
					)
				);

				if ( ! empty( $forums ) ) {
					foreach ( $forums as $forum ) {
						$options[] = array(
							'value' => (string) $forum->ID,
							'label' => $forum->post_title,
						);
					}
				}

				return $options;
			},
			'save_value'        => function ( $group, $value ) {
				if ( ! function_exists( 'bbp_update_group_forum_ids' ) || ! function_exists( 'bbp_update_forum_group_ids' ) ) {
					return;
				}

				$new_forum_id = absint( $value );
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
