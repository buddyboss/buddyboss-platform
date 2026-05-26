<?php
/**
 * Register core Email Template fields in the Admin Meta Field Registry.
 *
 * These fields power the Email Template Add/Edit modal in Settings 2.0.
 * Third-party plugins can add fields via the `bb_register_emails_meta_fields` hook.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core email template edit fields via the global field registry.
 *
 * Hooked to `bb_register_emails_meta_fields` at priority 1 so core
 * fields are registered before any third-party additions.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
 * @param string                       $component The component identifier.
 *
 * @return void
 */
function bb_emails_register_core_meta_fields( $registry, $component = 'emails' ) {

	// =========================================================================
	// TAB: Details (order 10–50)
	// =========================================================================

	// 1. Title.
	$registry->register(
		$component,
		'title',
		array(
			'label'             => __( 'Title', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 10,
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				return $post->post_title;
			},
			'save_value'        => function ( $post, $value ) {
				$post->post_title = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// 2. Slug (optional — auto-generated from title if left empty).
	$registry->register(
		$component,
		'slug',
		array(
			'label'             => __( 'Slug (Optional)', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 15,
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				return $post->post_name;
			},
			'save_value'        => function ( $post, $value ) {
				$post->post_name = sanitize_title( $value );
			},
			'sanitize_callback' => 'sanitize_title',
		)
	);

	// 3. Description (HTML body — rich text with {{token}} support).
	$registry->register(
		$component,
		'content',
		array(
			'label'             => __( 'Description', 'buddyboss' ),
			'description'       => __( 'Phrases wrapped in braces {{ }} are email tokens.', 'buddyboss' ),
			'type'              => 'richtext',
			'tab'               => 'details',
			'order'             => 20,
			'save_phase'        => 'before',
			'get_extra_data'    => function ( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					'description_link' => array(
						'text' => __( 'Learn about email tokens.', 'buddyboss' ),
						'url'  => admin_url( 'admin.php?page=bp-help&article=62844' ),
					),
				);
			},
			'get_value'         => function ( $post ) {
				return $post->post_content;
			},
			'save_value'        => function ( $post, $value ) {
				$post->post_content = $value;
			},
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	// 3. Plain Text Email Content (optional).
	$registry->register(
		$component,
		'excerpt',
		array(
			'label'             => __( 'Plain Text Email Content (Optional)', 'buddyboss' ),
			'description'       => __( 'Most email clients support HTML email. However, some people prefer to receive plain text email. Enter a plain text alternative version of your email here.', 'buddyboss' ),
			'type'              => 'textarea',
			'tab'               => 'details',
			'order'             => 30,
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				return $post->post_excerpt;
			},
			'save_value'        => function ( $post, $value ) {
				$post->post_excerpt = $value;
			},
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);

	// 4. Situation (email type taxonomy term).
	// Rendered as custom tabbed radio component in the modal (not via RegisteredMetaField).
	// Marked visible=false so auto-render skips it; value still flows through registry get/save.
	$registry->register(
		$component,
		'email_type',
		array(
			'label'             => __( 'Situation', 'buddyboss' ),
			'description'       => __( 'Choose when this email will be sent.', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 50,
			'save_phase'        => 'after',
			'get_value'         => function ( $post ) {
				if ( empty( $post->ID ) ) {
					return '';
				}
				$terms = get_the_terms( $post->ID, bp_get_email_tax_type() );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					return $terms[0]->slug;
				}
				return '';
			},
			'save_value'        => function ( $post, $value ) {
				if ( ! empty( $post->ID ) ) {
					if ( ! empty( $value ) && term_exists( $value, bp_get_email_tax_type() ) ) {
						wp_set_object_terms( $post->ID, $value, bp_get_email_tax_type() );
					} else {
						wp_set_object_terms( $post->ID, array(), bp_get_email_tax_type() );
					}
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// =========================================================================
	// Context: after — Publish fields rendered after Custom Fields + Situation
	// (order 60–100)
	// =========================================================================

	// 6. Status.
	$registry->register(
		$component,
		'status',
		array(
			'label'             => __( 'Status', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 60,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				$status = $post->post_status;
				// Map private/future back to their UI equivalents.
				if ( 'private' === $status || 'future' === $status ) {
					return 'publish';
				}
				// Default to publish for new templates.
				if ( empty( $post->ID ) || 'auto-draft' === $status ) {
					return 'publish';
				}
				return $status;
			},
			'get_options'       => function ( $post ) {
				return array(
					array(
						'label' => __( 'Draft', 'buddyboss' ),
						'value' => 'draft',
					),
					array(
						'label' => __( 'Pending Review', 'buddyboss' ),
						'value' => 'pending',
					),
					array(
						'label' => __( 'Published', 'buddyboss' ),
						'value' => 'publish',
					),
				);
			},
			'save_value'        => function ( $post, $value ) {
				// Final post_status is resolved by the AJAX handler after
				// combining status + visibility + publish_mode.
				$post->post_status = $value;
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// 7. Visibility.
	$registry->register(
		$component,
		'visibility',
		array(
			'label'             => __( 'Visibility', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 70,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				if ( 'private' === $post->post_status ) {
					return 'private';
				} elseif ( ! empty( $post->post_password ) ) {
					return 'password';
				}
				return 'public';
			},
			'get_options'       => function ( $post ) {
				return array(
					array(
						'label' => __( 'Public', 'buddyboss' ),
						'value' => 'public',
					),
					array(
						'label' => __( 'Private', 'buddyboss' ),
						'value' => 'private',
					),
					array(
						'label' => __( 'Password Protected', 'buddyboss' ),
						'value' => 'password',
					),
				);
			},
			'save_value'        => function ( $post, $value ) {
				if ( 'private' === $value ) {
					$post->post_status = 'private';
				} elseif ( 'password' !== $value ) {
					$post->post_password = '';
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// 8. Password (conditional on visibility = password).
	$registry->register(
		$component,
		'password',
		array(
			'label'             => __( 'Password', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 75,
			'save_phase'        => 'before',
			'conditional'       => array(
				'field' => 'visibility',
				'value' => 'password',
			),
			'get_value'         => function ( $post ) {
				return $post->post_password;
			},
			'save_value'        => function ( $post, $value ) {
				$post->post_password = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// 9. Publish mode.
	$registry->register(
		$component,
		'publish_mode',
		array(
			'label'             => __( 'Publish', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 80,
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				return 'future' === $post->post_status ? 'schedule' : 'immediately';
			},
			'get_options'       => function ( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
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
			'save_value'        => function ( $post, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// 10. Schedule Date (conditional on publish_mode=schedule).
	$registry->register(
		$component,
		'schedule_date',
		array(
			'label'             => __( 'Date', 'buddyboss' ),
			'type'              => 'date',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 85,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $post ) {
				if ( empty( $post->ID ) ) {
					return '';
				}
				return get_the_date( 'Y-m-d', $post->ID );
			},
			// No-op: date is handled by the AJAX handler.
			'save_value'        => function ( $post, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// 11. Schedule Time (conditional on publish_mode=schedule).
	$registry->register(
		$component,
		'schedule_time',
		array(
			'label'             => __( 'Time', 'buddyboss' ),
			'type'              => 'time',
			'tab'               => 'details',
			'context'           => 'after',
			'order'             => 90,
			'layout'            => 'half',
			'save_phase'        => 'after',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $post ) {
				if ( empty( $post->ID ) ) {
					return '';
				}
				return get_the_date( 'H:i', $post->ID );
			},
			// No-op: time is handled by the AJAX handler.
			'save_value'        => function ( $post, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}

add_action( 'bb_register_emails_meta_fields', 'bb_emails_register_core_meta_fields', 1, 2 );
