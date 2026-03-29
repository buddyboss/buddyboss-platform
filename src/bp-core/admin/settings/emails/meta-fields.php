<?php
/**
 * Register core Email Template fields in the Admin Meta Field Registry.
 *
 * These fields power the Email Template Add/Edit modal in Settings 2.0.
 * Third-party plugins can add fields via the `bb_register_emails_meta_fields` hook.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core email template edit fields via the global field registry.
 *
 * Hooked to `bb_register_emails_meta_fields` at priority 1 so core
 * fields are registered before any third-party additions.
 *
 * @since BuddyBoss [BBVERSION]
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

	// 2. Description (HTML body — rich text with {{token}} support).
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
			'description'       => __( 'Most email clients support HTML. For text-only clients, enter a plain text version.', 'buddyboss' ),
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
				if ( ! empty( $post->ID ) && ! empty( $value ) ) {
					wp_set_object_terms( $post->ID, $value, bp_get_email_tax_type() );
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// =========================================================================
	// TAB: Publish (order 60–100)
	// =========================================================================

	// 6. Status.
	$registry->register(
		$component,
		'status',
		array(
			'label'             => __( 'Status', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'publish',
			'order'             => 60,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				$status = $post->post_status;
				// Map private/future back to their UI equivalents.
				if ( 'private' === $status || 'future' === $status ) {
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
			'tab'               => 'publish',
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
			'tab'               => 'publish',
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
			'tab'               => 'publish',
			'order'             => 80,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $post ) {
				return 'future' === $post->post_status ? 'schedule' : 'immediately';
			},
			'get_options'       => function ( $post ) {
				return array(
					array(
						'label' => __( 'Immediately', 'buddyboss' ),
						'value' => 'immediately',
					),
					array(
						'label' => __( 'Schedule', 'buddyboss' ),
						'value' => 'schedule',
					),
				);
			},
			'save_value'        => function ( $post, $value ) {
				if ( 'schedule' === $value ) {
					$post->post_status = 'future';
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// 10. Schedule date (conditional on publish_mode = schedule).
	$registry->register(
		$component,
		'publish_date',
		array(
			'label'             => __( 'Schedule Date', 'buddyboss' ),
			'type'              => 'date',
			'tab'               => 'publish',
			'order'             => 90,
			'save_phase'        => 'before',
			'conditional'       => array(
				'field' => 'publish_mode',
				'value' => 'schedule',
			),
			'get_value'         => function ( $post ) {
				return $post->post_date;
			},
			'save_value'        => function ( $post, $value ) {
				if ( ! empty( $value ) ) {
					$post->post_date     = $value;
					$post->post_date_gmt = get_gmt_from_date( $value );
				}
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}

add_action( 'bb_register_emails_meta_fields', 'bb_emails_register_core_meta_fields', 1, 2 );
