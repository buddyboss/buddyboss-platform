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
 * Renders the Pages Setup admin panel.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_pages_settings() {

	// Flush the rewrite rule to work forum on newly assigned the page.
	if ( isset( $_GET['added'] ) && 'true' === $_GET['added'] ) {
		flush_rewrite_rules( true );
	}
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Pages', 'buddyboss' ) ); ?></h2>
		<form action="" method="post">
			<?php
			settings_fields( 'bp-pages' );
			bp_custom_pages_do_settings_sections( 'bp-pages' );

			// Check WPML Active
			if ( class_exists( 'SitePress' ) ) {
				$wpml_options = get_option( 'icl_sitepress_settings' );
				$default_lang = $wpml_options['default_language'];
				$current_lang = ICL_LANGUAGE_CODE;

				if  ( $current_lang === $default_lang ) {
					// Show the "Save Settings" button only if the current language is the default language.
					printf( '<p class="submit"><input type="submit" name="submit" class="button-primary" value="%s" /></p>', esc_attr__( 'Save Settings', 'buddyboss' ) );
				} else {
					// Show a disabled "Save Settings" button if the current language is not the default language.
					printf( '<div class="submit"><p class="button-primary disabled">%s</p></div>', esc_attr__( 'Save Settings', 'buddyboss' ) );
					printf( '<p class="description">%s</p>', esc_attr__( 'You need to switch to your Default language in WPML to save these settings.', 'buddyboss' ) );
				}
			} else {
				printf( '<p class="submit"><input type="submit" name="submit" class="button-primary" value="%s" /></p>', esc_attr__( 'Save Settings', 'buddyboss' ) );
			}
			?>
		</form>
	</div>

	<?php
}

/**
 * Output custom pages admin settings.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_custom_pages_do_settings_sections( $page ) {
	global $wp_settings_sections, $wp_settings_fields;

	if ( ! isset( $wp_settings_sections[ $page ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
		echo "<div class='bp-admin-card section-{$section['id']}'>";
		if ( $section['title'] ) {
			echo "<h2>{$section['title']}</h2>\n";
		}

		if ( $section['callback'] ) {
			call_user_func( $section['callback'], $section );
		}

		if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
			continue;
		}
		echo '<table class="form-table">';
		bp_custom_pages_do_settings_fields( $page, $section['id'] );
		echo '</table></div>';
	}
}

/**
 * Print out the settings fields for a particular settings section
 *
 * Part of the Settings API. Use this in a settings page to output
 * a specific section. Should normally be called by do_settings_sections()
 * rather than directly.
 *
 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $page Slug title of the admin page who's settings fields you want to show.
 * @param string $section Slug title of the settings section who's fields you want to show.
 */
function bp_custom_pages_do_settings_fields( $page, $section ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
		$class = '';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}

		echo "<tr{$class}>";

		if ( ! empty( $field['args']['label_for'] ) ) {
			echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
		} else {
			echo '<th scope="row">' . $field['title'] . '</th>';
		}

		echo '<td>';
		call_user_func( $field['callback'], $field['args'] );
		echo '</td>';
		echo '</tr>';
	}
}

/**
 * Register page fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_register_page_fields() {
	$existing_pages  = bp_core_get_directory_page_ids();
	$directory_pages = bp_core_admin_get_directory_pages();
	$description     = '';
	add_settings_section( 'bp_pages', __( 'Component Pages', 'buddyboss' ), 'bp_core_admin_directory_pages_description', 'bp-pages' );
	foreach ( $directory_pages as $name => $label ) {

		if ( 'members' === $name ) {
			$description = 'This directory shows a listing of all members.';
		} elseif ( 'groups' === $name ) {
			$description = 'This directory shows a listing of all groups.';
		} elseif ( 'new_forums_page' === $name ) {
			$description = 'This directory shows a listing of all forums.';
		} elseif ( 'activity' === $name ) {
			$description = 'This directory shows all sitewide activity.';
		} elseif ( 'media' === $name ) {
			$description = 'This directory shows all photos uploaded by members.';
		}
		add_settings_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_pages', compact( 'existing_pages', 'name', 'label', 'description' ) );
		register_setting( 'bp-pages', $name, array() );
	}
}
add_action( 'admin_init', 'bp_core_admin_register_page_fields' );

/**
 * Register registration page fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_register_registration_page_fields() {

	$allow_custom_registration = bp_allow_custom_registration();

	if ( $allow_custom_registration ) {
		return;
	}

	if ( ! bp_enable_site_registration() && ! bp_is_active( 'invites' ) ) {
		return;
	}



	add_settings_section( 'bp_registration_pages', __( 'Registration Pages', 'buddyboss' ), 'bp_core_admin_registration_pages_description', 'bp-pages' );

	$existing_pages = bp_core_get_directory_page_ids();
	$static_pages   = bp_core_admin_get_static_pages();

	// add view tutorial button
	$static_pages['button'] = array(
		'link'  => bp_get_admin_url(
				add_query_arg(
					array(
						'page'      => 'bp-help',
						'article'   => 62795,
					),
					'admin.php'
				)
			),
		'label' => __( 'View Tutorial', 'buddyboss' ),
	);
	$description            = '';

	foreach ( $static_pages as $name => $label ) {
		$title = $label;
		if ( 'register' === $name ) {
			$description = 'New users fill out this form to register their accounts.';
		} elseif ( 'terms' === $name ) {
			$description = 'If a page is added, its contents will display in a popup on the register form.';
		} elseif ( 'privacy' === $name ) {
			$description = 'If a page is added, its contents will display in a popup on the register form.';
		} elseif ( 'activate' === $name ) {
			$description = 'After registering, users are sent to this page to activate their accounts.';
		}

		if ( 'button' === $name ) {
			$title = '';
		}

		add_settings_field( $name, $title, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_registration_pages', compact( 'existing_pages', 'name', 'label', 'description' ) );
		register_setting( 'bp-pages', $name, array() );
	}
}

add_action( 'admin_init', 'bp_core_admin_register_registration_page_fields' );

/**
 * Directory page settings section description
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_directory_pages_description() {
	echo wpautop( __( 'Associate a WordPress page with each of the following components.', 'buddyboss' ) );
}

/**
 * Registration page settings section description
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_registration_pages_description() {
	if ( bp_get_signup_allowed() ) :
		echo wpautop( __( 'Associate a WordPress page with the following Registration sections.', 'buddyboss' ) );
	else :

		$invite_text = '';
		if ( bp_is_active( 'invites' ) ) {
			$invite_text = sprintf(
				'Because <a href="%s">Email Invites</a> is enabled, invited users will still be allowed to register new accounts.',
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-invites',
					),
					admin_url( 'admin.php' )
				)
			);
		}

		echo wpautop(
			sprintf(
				__( 'Registration is currently disabled. %1$s To enable open registration, please click on the "Registration" checkbox in <a href="%2$s">General Settings</a>.', 'buddyboss' ),
				$invite_text,
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-general',
					),
					admin_url( 'admin.php' )
				)
			)
		);
	endif;
}

/**
 * Pages drop downs callback
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args
 */
function bp_admin_setting_callback_page_directory_dropdown( $args ) {
	extract( $args );

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	// For the button
	if ( 'button' === $name ) {

		printf( '<p><a href="%s" class="button">%s</a> </p>', $args['label']['link'], $args['label']['label'] );
		// For the forums will set the page selected from the custom option `_bbp_root_slug_custom_slug`
	} elseif ( 'new_forums_page' === $name ) {

		// Get the page id from the options.
		$id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );

		// Check the status of current set value.
		$status = get_post_status( $id );

		// Set the page id if page exists and in publish otherwise set blank.
		$id = ( '' !== $status && 'publish' === $status ) ? $id : '';

		echo wp_dropdown_pages(
			array(
				'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
				'echo'             => false,
				'show_option_none' => __( '- Select a page -', 'buddyboss' ),
				'selected'         => ! empty( $id ) ? $id : false,
			)
		);

		if ( ! empty( $id ) ) {
			printf(
				'<a href="%s" class="button-secondary" target="_bp">%s</a>',
				get_permalink( $id ),
				__( 'View', 'buddyboss' )
			);
		} else {
			printf(
				'<a href="%s" class="button-secondary create-background-page" data-name="%s">%s</a>',
				'javascript:void(0);',
				esc_attr( $name ),
				__( 'Create Page', 'buddyboss' )
			);
		}

		if ( '' !== $description ) {
			printf(
				'<p class="description">%s</p>',
				$description
			);
		}

		// For the normal directory pages.
	} else {

		echo wp_dropdown_pages(
			array(
				'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
				'echo'             => false,
				'show_option_none' => __( '- Select a page -', 'buddyboss' ),
				'selected'         => ! empty( $existing_pages[ $name ] ) ? $existing_pages[ $name ] : false,
			)
		);

		if ( ! empty( $existing_pages[ $name ] ) ) {
			printf(
				'<a href="%s" class="button-secondary" target="_bp">%s</a>',
				get_permalink( $existing_pages[ $name ] ),
				__( 'View', 'buddyboss' )
			);
		} else {
			printf(
				'<a href="%s" class="button-secondary create-background-page" data-name="%s">%s</a>',
				'javascript:void(0);',
				esc_attr( $name ),
				__( 'Create Page', 'buddyboss' )
			);
		}

		if ( '' !== $description ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	if ( ! bp_is_root_blog() ) {
		restore_current_blog();
	}
}

/**
 * Save BuddyBoss pages settings
 *
 * @since BuddyBoss 1.0.0
 * @return bool
 */
function bp_core_admin_maybe_save_pages_settings() {

	if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
		return false;
	}

	if ( 'bp-pages' != $_GET['page'] ) {
		return false;
	}

	if ( ! check_admin_referer( 'bp-pages-options' ) ) {
		return false;
	};

	if ( isset( $_POST['bp_pages'] ) ) {
		$valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

		$new_directory_pages = array();
		foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
			// Exclude the new_forums_page to set in $new_directory_pages array.
			if ( isset( $valid_pages[ $key ] ) && 'new_forums_page' !== $key ) {
				$new_directory_pages[ $key ] = (int) $value;
			}
		}
		bp_core_update_directory_page_ids( $new_directory_pages );

		// Save the forums page id into the _bbp_root_slug_custom_slug option and set the forum root slug to selected page slug.
		if ( bp_is_active( 'forums' ) ) {
			if ( isset( $_POST['bp_pages'] ) && '' === $_POST['bp_pages']['new_forums_page'] ) {
				bp_update_option( '_bbp_root_slug_custom_slug', '' );
			} else {
				$slug = get_post_field( 'post_name', (int) $_POST['bp_pages']['new_forums_page'] );
				bp_update_option( '_bbp_root_slug', $slug );
				bp_update_option( '_bbp_root_slug_custom_slug', (int) $_POST['bp_pages']['new_forums_page'] );
			}
		}
	}

	bp_core_redirect(
		bp_get_admin_url(
			add_query_arg(
				array(
					'page'  => 'bp-pages',
					'added' => 'true',
				),
				'admin.php'
			)
		)
	);
}

add_action( 'bp_admin_init', 'bp_core_admin_maybe_save_pages_settings', 100 );
