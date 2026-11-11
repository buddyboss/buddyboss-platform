<?php
/**
 * BuddyBoss Admin Settings - Discussion (Topic) Meta Fields Registration.
 *
 * Registers core discussion fields for the Discussion Create/Edit modals
 * in the Settings 2.0 admin interface via BB_Admin_Meta_Field_Registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core discussion meta fields for the create/edit modals.
 *
 * Hooks into `bb_register_discussions_meta_fields` at priority 1 so Platform fields
 * come first. Pro extensions and third-party plugins register at priority 10+.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
 * @param string                       $component The component identifier.
 */
function bb_discussions_register_core_meta_fields( $registry, $component ) {

	// Title.
	$registry->register(
		$component,
		'title',
		array(
			'label'             => __( 'Title', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 10,
			'save_phase'        => 'before',
			'get_value'         => function ( $topic ) {
				return $topic->post_title;
			},
			'save_value'        => function ( $topic, $value ) {
				$topic->post_title = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Permalink (edit only — topics auto-generate slug from title on create).
	$registry->register(
		$component,
		'slug',
		array(
			'label'             => __( 'Permalink', 'buddyboss' ),
			'type'              => 'permalink',
			'tab'               => 'details',
			'order'             => 15,
			'save_phase'        => 'before',
			'is_visible'        => function ( $topic ) {
				return ! empty( $topic->ID );
			},
			'get_value'         => function ( $topic ) {
				return $topic->post_name;
			},
			'get_extra_data'    => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return array( 'base_url' => '' );
				}

				$permalink = bbp_get_topic_permalink( $topic->ID );

				// Remove the slug from the end to get the base URL.
				$slug     = $topic->post_name;
				$base_url = $slug ? str_replace( $slug . '/', '', $permalink ) : $permalink;

				return array(
					'base_url' => $base_url,
				);
			},
			'save_value'        => function ( $topic, $value ) {
				$topic->post_name = sanitize_title( $value );
			},
			'sanitize_callback' => 'sanitize_title',
		)
	);

	// Description.
	$registry->register(
		$component,
		'description',
		array(
			'label'             => __( 'Description', 'buddyboss' ),
			'type'              => 'richtext',
			'tab'               => 'details',
			'order'             => 20,
			'save_phase'        => 'before',
			'get_value'         => function ( $topic ) {
				return $topic->post_content;
			},
			'save_value'        => function ( $topic, $value ) {
				$topic->post_content = $value;
			},
			'sanitize_callback' => 'wp_kses_post',
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
			'order'             => 30,
			'save_phase'        => 'after',
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return 0;
				}
				return (int) get_post_meta( $topic->ID, '_bbp_forum_id', true );
			},
			// No-op: forum_id is saved directly by create_discussion()/save_discussion() AJAX handlers.
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'absint',
		)
	);

	// Type (Normal/Sticky/Super Sticky).
	$registry->register(
		$component,
		'type',
		array(
			'label'             => __( 'Type', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 40,
			'save_phase'        => 'after',
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return 'normal';
				}
				if ( bbp_is_topic_super_sticky( $topic->ID ) ) {
					return 'super_sticky';
				} elseif ( bbp_is_topic_sticky( $topic->ID ) ) {
					return 'sticky';
				}
				return 'normal';
			},
			'get_options'       => function ( $topic ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'normal',
						'label' => __( 'Normal', 'buddyboss' ),
					),
					array(
						'value' => 'sticky',
						'label' => __( 'Sticky', 'buddyboss' ),
					),
					array(
						'value' => 'super_sticky',
						'label' => __( 'Super Sticky (To front)', 'buddyboss' ),
					),
				);
			},
			// No-op: type is saved directly by the AJAX handlers (sticky/unstick logic).
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Status (Open/Closed/Spam/Trash/Pending) — matches legacy bbp_get_topic_statuses().
	$registry->register(
		$component,
		'topic_status',
		array(
			'label'             => __( 'Status', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 50,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return bbp_get_public_status_id();
				}
				return get_post_status( $topic->ID );
			},
			'get_options'       => function ( $topic ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				$statuses = function_exists( 'bbp_get_topic_statuses' ) ? bbp_get_topic_statuses() : array();
				$options  = array();
				foreach ( $statuses as $status_id => $label ) {
					$options[] = array(
						'value' => $status_id,
						'label' => $label,
					);
				}
				return $options;
			},
			// No-op: status is saved directly by the AJAX handlers (bbp_close/open_topic).
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Visibility (Public/Private/Password Protected).
	$registry->register(
		$component,
		'visibility',
		array(
			'label'             => __( 'Visibility', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 55,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return 'publish';
				}

				// Password-protected posts have post_status='publish' with a non-empty post_password.
				if ( ! empty( $topic->post_password ) ) {
					return 'password';
				}

				return get_post_status( $topic->ID );
			},
			'get_options'       => function ( $topic ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
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
			'save_value'        => function ( $topic, $value ) {
				$allowed = array( 'publish', 'private', 'password' );
				if ( in_array( $value, $allowed, true ) ) {
					// Password Protected uses 'publish' post_status with a post_password.
					if ( 'password' === $value ) {
						$topic->post_status = 'publish';
					} else {
						$topic->post_status = $value;
					}
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Password (conditional: only when visibility='password').
	$registry->register(
		$component,
		'post_password',
		array(
			'label'             => __( 'Password', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 56,
			'save_phase'        => 'before',
			'conditional'       => array(
				'field' => 'visibility',
				'value' => 'password',
			),
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return '';
				}
				return $topic->post_password;
			},
			'save_value'        => function ( $topic, $value ) {
				$topic->post_password = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// =========================================================================
	// Publish fields (order 57–59).
	// =========================================================================

	// Publish (Immediately/Schedule).
	$registry->register(
		$component,
		'publish_mode',
		array(
			'label'             => __( 'Publish', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 57,
			'save_phase'        => 'after',
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return 'immediately';
				}
				$post_date = $topic->post_date;
				$now       = current_time( 'mysql' );
				return strtotime( $post_date ) > strtotime( $now ) ? 'schedule' : 'immediately';
			},
			'get_options'       => function ( $topic ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
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
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
			'order'             => 58,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return '';
				}
				return get_the_date( 'Y-m-d', $topic->ID );
			},
			// No-op: date is handled by the AJAX handler.
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
			'order'             => 59,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return '';
				}
				return get_the_date( 'H:i', $topic->ID );
			},
			// No-op: time is handled by the AJAX handler.
			'save_value'        => function ( $topic, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// =========================================================================
	// Author fields (edit only — order 60–70).
	// =========================================================================

	// Author ID.
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
			'is_visible'        => function ( $topic ) {
				return ! empty( $topic->ID );
			},
			'get_value'         => function ( $topic ) {
				return (int) $topic->post_author;
			},
			'get_extra_data'    => function ( $topic ) {
				if ( empty( $topic->ID ) || empty( $topic->post_author ) ) {
					return array();
				}

				$user = get_userdata( (int) $topic->post_author );
				if ( ! $user ) {
					return array();
				}

				return array(
					'author_name'   => $user->display_name,
					'author_avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
					'author_url'    => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user->ID ) : get_author_posts_url( $user->ID ),
				);
			},
			'save_value'        => function ( $topic, $value ) {
				$new_author = absint( $value );
				if ( empty( $new_author ) || empty( $topic->ID ) ) {
					return;
				}

				// Verify user exists before changing author.
				$user = get_userdata( $new_author );
				if ( ! $user ) {
					return;
				}

				wp_update_post(
					array(
						'ID'          => $topic->ID,
						'post_author' => $new_author,
					)
				);
			},
			'sanitize_callback' => 'absint',
		)
	);

	// Author IP (readonly — informational only, matches legacy bbPress admin).
	$registry->register(
		$component,
		'author_ip',
		array(
			'label'      => __( 'Author IP', 'buddyboss' ),
			'type'       => 'text',
			'tab'        => 'details',
			'order'      => 65,
			'layout'     => 'half',
			'is_visible' => function ( $topic ) {
				return ! empty( $topic->ID );
			},
			'get_value'  => function ( $topic ) {
				if ( empty( $topic->ID ) ) {
					return '';
				}
				return get_post_meta( $topic->ID, '_bbp_author_ip', true );
			},
		)
	);
	// Author info (readonly display — avatar + name link).
	$registry->register(
		$component,
		'author_info',
		array(
			'label'      => '',
			'type'       => 'readonly',
			'tab'        => 'details',
			'order'      => 68,
			'is_visible' => function ( $topic ) {
				return ! empty( $topic->ID ) && ! empty( $topic->post_author );
			},
			'get_value'  => function ( $topic ) {
				if ( empty( $topic->ID ) || empty( $topic->post_author ) ) {
					return null;
				}

				$user = get_userdata( (int) $topic->post_author );
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
add_action( 'bb_register_discussions_meta_fields', 'bb_discussions_register_core_meta_fields', 1, 2 );
