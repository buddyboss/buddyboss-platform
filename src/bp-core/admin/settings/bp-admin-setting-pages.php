<?php

class BP_Admin_Setting_Pages extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Pages', 'buddyboss' );
		$this->tab_name  = 'bp-pages';
		$this->tab_order = 5;

		$this->register_page_fields();
		$this->register_registration_page_fields();
	}

	public function settings_save() {
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
	}

	public function register_page_fields() {
		$existing_pages = bp_core_get_directory_page_ids();
		$directory_pages = bp_core_admin_get_directory_pages();

		$this->add_section( 'bp_pages', __( 'Directories', 'buddyboss' ), [$this, 'pages_description'] );
		foreach ($directory_pages as $name => $label) {
			$this->add_field( $name, $label, [$this, 'bp_admin_setting_callback_page_directory_dropdown'], [], compact('existing_pages', 'name', 'label') );
		}
	}

	public function register_registration_page_fields() {
		$existing_pages = bp_core_get_directory_page_ids();
		$directory_pages = bp_core_admin_get_directory_pages();

		$this->add_section( 'bp_registration_pages', __( 'Registration', 'buddyboss' ), [$this, 'registration_pages_description'] );

		if (! bp_get_signup_allowed()) {
			return;
		}

		$existing_pages = bp_core_get_directory_page_ids();
		$static_pages = bp_core_admin_get_static_pages();

		foreach ($static_pages as $name => $label) {
			$this->add_field( $name, $label, [$this, 'bp_admin_setting_callback_page_directory_dropdown'], [], compact('existing_pages', 'name', 'label') );
		}
	}

	public function pages_description() {
		echo wpautop( __( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddyboss' ) );
	}

	public function registration_pages_description() {
		if ( bp_get_signup_allowed() ) :
			echo wpautop( __( 'Associate WordPress Pages with the following BuddyPress Registration pages.', 'buddyboss' ) );
		else :
			if ( is_multisite() ) :
				echo wpautop(
					sprintf(
						__( 'Registration is currently disabled.  Before associating a page is allowed, please enable registration by selecting either the "User accounts may be registered" or "Both sites and user accounts can be registered" option on <a href="%s">this page</a>.', 'buddyboss' ),
						network_admin_url( 'settings.php' )
					)
				);
			else :
				echo wpautop(
					sprintf(
						__( 'Registration is currently disabled.  Before associating a page is allowed, please enable registration by clicking on the "Anyone can register" checkbox on <a href="%s">this page</a>.', 'buddyboss' ),
						network_admin_url( 'options-general.php' )
					)
				);
			endif;
		endif;
	}

	public function bp_admin_setting_callback_page_directory_dropdown($args) {
		extract($args);

		if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() );

		echo wp_dropdown_pages( array(
			'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
			'echo'             => false,
			'show_option_none' => __( '- None -', 'buddyboss' ),
			'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
		) );

		if ( !empty( $existing_pages[$name] ) ) :

			printf(
				'<a href="%s" class="button-secondary" target="_bp">%s</a>',
				get_permalink( $existing_pages[$name] ),
				__( 'View', 'buddyboss' )
			);
		endif;

		if ( ! bp_is_root_blog() ) restore_current_blog();
	}
}

return new BP_Admin_Setting_Pages;
