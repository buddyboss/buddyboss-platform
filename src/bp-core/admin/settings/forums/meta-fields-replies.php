<?php
/**
 * BuddyBoss Admin Settings - Reply Meta Fields Registration.
 *
 * Registers core reply fields for the Reply Create/Edit modals
 * in the Settings 2.0 admin interface via BB_Admin_Meta_Field_Registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core reply meta fields for the create/edit modals.
 *
 * Hooks into `bb_register_replies_meta_fields` at priority 1 so Platform fields
 * come first. Pro extensions and third-party plugins register at priority 10+.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
 * @param string                       $component The component identifier.
 */
function bb_replies_register_core_meta_fields( $registry, $component ) {

	// Content (Description).
	$registry->register(
		$component,
		'content',
		array(
			'label'             => __( 'Description', 'buddyboss' ),
			'type'              => 'richtext',
			'tab'               => 'details',
			'order'             => 10,
			'save_phase'        => 'before',
			'get_value'         => function ( $reply ) {
				return $reply->post_content;
			},
			'save_value'        => function ( $reply, $value ) {
				$reply->post_content = $value;
			},
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	// Reply link (readonly, edit only — shows the reply URL).
	$registry->register(
		$component,
		'reply_link',
		array(
			'label'      => '',
			'type'       => 'readonly',
			'tab'        => 'details',
			'order'      => 15,
			'is_visible' => function ( $reply ) {
				return ! empty( $reply->ID );
			},
			'get_value'  => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return null;
				}
				return bbp_get_reply_url( $reply->ID );
			},
		)
	);

	// Forum.
	$registry->register(
		$component,
		'forum_id',
		array(
			'label'             => __( 'Forum', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_forum_autocomplete',
			'placeholder'       => __( 'Select Forum', 'buddyboss' ),
			'tab'               => 'details',
			'order'             => 20,
			'save_phase'        => 'after',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 0;
				}
				return (int) get_post_meta( $reply->ID, '_bbp_forum_id', true );
			},
			// No-op: forum_id is saved directly by create_reply()/save_reply() AJAX handlers.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'absint',
		)
	);

	// Discussion (Topic).
	$registry->register(
		$component,
		'topic_id',
		array(
			'label'             => __( 'Discussion', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_discussion_autocomplete',
			'placeholder'       => __( 'Select Discussion', 'buddyboss' ),
			'tab'               => 'details',
			'order'             => 30,
			'save_phase'        => 'after',
			'async_depends_on'  => 'forum_id',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 0;
				}
				return (int) get_post_meta( $reply->ID, '_bbp_topic_id', true );
			},
			// No-op: topic_id is saved directly by create_reply()/save_reply() AJAX handlers.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'absint',
		)
	);

	// Reply to (threading).
	$registry->register(
		$component,
		'reply_to',
		array(
			'label'             => __( 'Reply to', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_reply_autocomplete',
			'placeholder'       => __( 'Select Reply', 'buddyboss' ),
			'tab'               => 'details',
			'order'             => 40,
			'save_phase'        => 'after',
			'async_depends_on'  => 'topic_id',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 0;
				}
				return (int) get_post_meta( $reply->ID, '_bbp_reply_to', true );
			},
			// No-op: reply_to is saved directly by create_reply()/save_reply() AJAX handlers.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'absint',
		)
	);

	// Visibility — order 45 places it after Reply to, before Status.
	$registry->register(
		$component,
		'visibility',
		array(
			'label'             => __( 'Visibility', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 45,
			'save_phase'        => 'before',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 'publish';
				}
				return get_post_status( $reply->ID );
			},
			'get_options'       => function ( $reply ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'publish',
						'label' => __( 'Public', 'buddyboss' ),
					),
					array(
						'value' => 'private',
						'label' => __( 'Private', 'buddyboss' ),
					),
					array(
						'value' => 'password',
						'label' => __( 'Password Protected', 'buddyboss' ),
					),
				);
			},
			'save_value'        => function ( $reply, $value ) {
				$allowed = array( 'publish', 'private', 'password' );
				if ( in_array( $value, $allowed, true ) ) {
					if ( 'password' === $value ) {
						$reply->post_status = 'publish';
					} else {
						$reply->post_status = $value;
					}
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Status (matches legacy WP publish box: Draft, Pending Review, Published).
	$registry->register(
		$component,
		'reply_status',
		array(
			'label'             => __( 'Status', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 47,
			'save_phase'        => 'after',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 'publish';
				}
				return get_post_status( $reply->ID );
			},
			'get_options'       => function ( $reply ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'publish',
						'label' => __( 'Published', 'buddyboss' ),
					),
					array(
						'value' => 'pending',
						'label' => __( 'Pending Review', 'buddyboss' ),
					),
					array(
						'value' => 'draft',
						'label' => __( 'Draft', 'buddyboss' ),
					),
				);
			},
			// No-op: status is saved directly by the AJAX handler.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Publish (Immediately/Schedule).
	$registry->register(
		$component,
		'publish_mode',
		array(
			'label'             => __( 'Publish', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 50,
			'save_phase'        => 'after',
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return 'immediately';
				}
				$post_date = $reply->post_date;
				$now       = current_time( 'mysql' );
				return strtotime( $post_date ) > strtotime( $now ) ? 'schedule' : 'immediately';
			},
			'get_options'       => function ( $reply ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'immediately',
						'label' => __( 'Immediately', 'buddyboss' ),
					),
					array(
						'value' => 'schedule',
						'label' => __( 'Schedule', 'buddyboss' ),
					),
				);
			},
			// No-op: publish mode is handled by the AJAX handler via post_date.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Schedule Date (conditional on publish_mode=schedule).
	$registry->register(
		$component,
		'schedule_date',
		array(
			'label'             => __( 'Date', 'buddyboss' ),
			'type'              => 'date',
			'tab'               => 'details',
			'order'             => 51,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return '';
				}
				return get_the_date( 'Y-m-d', $reply->ID );
			},
			// No-op: date is handled by the AJAX handler.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Schedule Time (conditional on publish_mode=schedule).
	$registry->register(
		$component,
		'schedule_time',
		array(
			'label'             => __( 'Time', 'buddyboss' ),
			'type'              => 'time',
			'tab'               => 'details',
			'order'             => 52,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return '';
				}
				return get_the_date( 'H:i', $reply->ID );
			},
			// No-op: time is handled by the AJAX handler.
			'save_value'        => function ( $reply, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Author ID (editable — allows re-assigning reply author, edit-only).
	$registry->register(
		$component,
		'author_id',
		array(
			'label'             => __( 'Author ID', 'buddyboss' ),
			'type'              => 'number',
			'tab'               => 'details',
			'order'             => 60,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'is_visible'        => function ( $reply ) {
				return ! empty( $reply->ID );
			},
			'get_value'         => function ( $reply ) {
				return (int) $reply->post_author;
			},
			'get_extra_data'    => function ( $reply ) {
				if ( empty( $reply->ID ) || empty( $reply->post_author ) ) {
					return array();
				}

				$user = get_userdata( (int) $reply->post_author );
				if ( ! $user ) {
					return array();
				}

				return array(
					'author_name'   => $user->display_name,
					'author_avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
					'author_url'    => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user->ID ) : get_author_posts_url( $user->ID ),
				);
			},
			'save_value'        => function ( $reply, $value ) {
				$new_author = absint( $value );
				if ( empty( $new_author ) || empty( $reply->ID ) ) {
					return;
				}

				$user = get_userdata( $new_author );
				if ( ! $user ) {
					return;
				}

				wp_update_post(
					array(
						'ID'          => $reply->ID,
						'post_author' => $new_author,
					)
				);
			},
			'sanitize_callback' => 'absint',
		)
	);

	// Author IP (readonly — informational only, edit-only).
	$registry->register(
		$component,
		'author_ip',
		array(
			'label'      => __( 'Author IP', 'buddyboss' ),
			'type'       => 'text',
			'tab'        => 'details',
			'order'      => 65,
			'layout'     => 'half',
			'is_visible' => function ( $reply ) {
				return ! empty( $reply->ID );
			},
			'get_value'  => function ( $reply ) {
				if ( empty( $reply->ID ) ) {
					return '';
				}
				return get_post_meta( $reply->ID, '_bbp_author_ip', true );
			},
		)
	);

	// Author info (readonly display — avatar + name link, edit-only).
	$registry->register(
		$component,
		'author_info',
		array(
			'label'      => '',
			'type'       => 'readonly',
			'tab'        => 'details',
			'order'      => 68,
			'is_visible' => function ( $reply ) {
				return ! empty( $reply->ID ) && ! empty( $reply->post_author );
			},
			'get_value'  => function ( $reply ) {
				if ( empty( $reply->ID ) || empty( $reply->post_author ) ) {
					return null;
				}

				$user = get_userdata( (int) $reply->post_author );
				if ( ! $user ) {
					return null;
				}

				return array(
					'author_name'   => $user->display_name,
					'author_avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
					'author_url'    => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user->ID ) : get_author_posts_url( $user->ID ),
				);
			},
		)
	);
}
add_action( 'bb_register_replies_meta_fields', 'bb_replies_register_core_meta_fields', 1, 2 );
