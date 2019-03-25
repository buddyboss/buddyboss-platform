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
	?>
    <div class="wrap">
	    <h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Pages', 'buddypress' ) ); ?></h2>
        <form action="" method="post">
			<?php
			settings_fields( 'bp-pages' );
			bp_custom_pages_do_settings_sections( 'bp-pages' );

			printf(
				'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
				esc_attr__( 'Save Settings', 'buddyboss' )
			);
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

	if ( ! isset( $wp_settings_sections[$page] ) )
		return;

	foreach ( (array) $wp_settings_sections[$page] as $section ) {
		echo "<div class='bp-admin-card section-{$section['id']}'>";
		if ( $section['title'] )
			echo "<h2>{$section['title']}</h2>\n";

		if ( $section['callback'] )
			call_user_func( $section['callback'], $section );

		if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) )
			continue;
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
 * @since BuddyPress 2.7.0
 *
 * @param string $page Slug title of the admin page who's settings fields you want to show.
 * @param string $section Slug title of the settings section who's fields you want to show.
 */
function bp_custom_pages_do_settings_fields($page, $section) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[$page][$section] ) )
		return;

	foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
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
		call_user_func($field['callback'], $field['args']);
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
	$existing_pages = bp_core_get_directory_page_ids();
	$directory_pages = bp_core_admin_get_directory_pages();
	$description = '';
	add_settings_section( 'bp_pages', __( 'Components', 'buddyboss' ), 'bp_core_admin_directory_pages_description', 'bp-pages' );
	foreach ($directory_pages as $name => $label) {

		if ( 'members' === $name ) {
			$description = 'Directory showing all members';
		} elseif ( 'activity' === $name ) {
			$description = 'All sitewide activity';
		} elseif ( 'groups' === $name ) {
			$description = 'Directory showing all groups';
		} elseif ( 'media' === $name ) {
			$description = 'All media uploaded by members';
		}
		add_settings_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_pages', compact('existing_pages', 'name', 'label', 'description' ) );
		register_setting( 'bp-pages', $name, [] );
	}
}
add_action( 'admin_init', 'bp_core_admin_register_page_fields' );

/**
 * Register registration page fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_register_registration_page_fields() {

	add_settings_section( 'bp_registration_pages', __( 'Registration', 'buddyboss' ), 'bp_core_admin_registration_pages_description', 'bp-pages' );

	$existing_pages = bp_core_get_directory_page_ids();
	$static_pages = bp_core_admin_get_static_pages();
	$description = '';

	foreach ($static_pages as $name => $label) {
		if ( 'register' === $name ) {
			$description = 'This is a register descriptions.';
		} elseif ( 'terms' === $name ) {
			$description = 'This is a terms descriptions.';
		} elseif ( 'privacy' === $name ) {
			$description = 'This is a privacy descriptions.';
		} elseif ( 'activate' === $name ) {
			$description = 'This is a activate descriptions.';
		}
		add_settings_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_registration_pages', compact('existing_pages', 'name', 'label', 'description' ) );
		register_setting( 'bp-pages', $name, [] );
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
		if ( is_multisite() ) :
			echo wpautop(
				sprintf(
					__( 'Registration is currently disabled. If "Email Invites" is enabled, invited users will still be allowed to register new accounts. To enable registration, please select either the "User accounts may be registered" or "Both sites and user accounts can be registered" option on <a href="%s">this page</a>.', 'buddyboss' ),
					network_admin_url( 'settings.php' )
				)
			);
		else :
			echo wpautop(
				sprintf(
					__( 'Registration is currently disabled. If "Email Invites" is enabled, invited users will still be allowed to register new accounts. To enable registration, please click on the "Anyone can register" checkbox on <a href="%s">this page</a>.', 'buddyboss' ),
					network_admin_url( 'options-general.php' )
				)
			);
		endif;
	endif;
}

/**
 * Pages dropdowns callback
 *
 * @since BuddyBoss 1.0.0
 * @param $args
 */
function bp_admin_setting_callback_page_directory_dropdown($args) {
	extract($args);

	if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() );

	echo wp_dropdown_pages( array(
		'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
		'echo'             => false,
		'show_option_none' => __( '- Select a page -', 'buddyboss' ),
		'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
	) );

	if ( !empty( $existing_pages[$name] ) ) {
		printf( '<a href="%s" class="button-secondary" target="_bp">%s</a>',
			get_permalink( $existing_pages[ $name ] ),
			__( 'View', 'buddyboss' ) );
	} else {
		printf( '<a href="%s" class="button-secondary create-background-page" data-name="%s" target="_bp">%s</a>',
			'javascript:void(0);',esc_attr( $name ),
			__( 'Create Page', 'buddyboss' ) );
	}

	if ( '' !== $description )
	printf(
		'<p class="description">%s</p>',
		sprintf(
			__( $description, 'buddyboss' )
		)
	);

	if ( ! bp_is_root_blog() ) restore_current_blog();
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
			if ( isset( $valid_pages[ $key ] ) ) {
				$new_directory_pages[ $key ] = (int) $value;
			}
		}
		bp_core_update_directory_page_ids( $new_directory_pages );
	}

	bp_core_redirect( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages', 'updated' => 'true' ) , 'admin.php' ) ) );
}

add_action( 'bp_admin_init', 'bp_core_admin_maybe_save_pages_settings', 100 );
