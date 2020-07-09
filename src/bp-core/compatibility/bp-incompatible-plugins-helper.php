<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fire to add support for third party plugin
 *
 * @since BuddyBoss 1.1.9
 */
function bp_helper_plugins_loaded_callback() {

	global $bp_plugins;

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support for LearnDash & bbPress Integration
	 */
	if ( in_array( 'learndash-bbpress/learndash-bbpress.php', $bp_plugins ) ) {

			/**
			 * Remove bbPress Integration admin init hook action
			 *
			 * Support bbPress Integration
			 */
			remove_action( 'admin_init', 'wdm_activation_dependency_check' );

		if ( empty( bp_is_active( 'forums' ) ) || empty( in_array( 'sfwd-lms/sfwd_lms.php', $bp_plugins ) ) ) {
			deactivate_plugins( 'learndash-bbpress/learndash-bbpress.php' );

			add_action( 'admin_notices', 'bp_core_learndash_bbpress_notices' );
			add_action( 'network_admin_notices', 'bp_core_learndash_bbpress_notices' );
		}
	}

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support Rank Math SEO
	 */
	if ( in_array( 'seo-by-rank-math/rank-math.php', $bp_plugins ) && ! is_admin() ) {
		require buddypress()->compatibility_dir . '/bp-rankmath-plugin-helpers.php';
	}

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support Co-Authors Plus
	 */
	if ( in_array( 'co-authors-plus/co-authors-plus.php', $bp_plugins ) ) {
		add_filter( 'bp_search_settings_post_type_taxonomies', 'bp_core_remove_authors_taxonomy_for_co_authors_plus', 100, 2 );
	}

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support MemberPress + BuddyPress Integration
	 */
	if ( in_array( 'memberpress-buddypress/main.php', $bp_plugins ) ) {
		/**
		 * This action is use when admin bar is Enable
		 */
		add_action( 'bp_setup_admin_bar', 'bp_core_add_admin_menu_for_memberpress_buddypress', 100 );

		/**
		 * This action to update the first and last name usermeta
		 */
		add_action( 'user_register', 'bp_core_updated_flname_memberpress_buddypress', 0 );
	}

	/**
	 * Include fix when WPML plugin is activated
	 *
	 * Support WPML Multilingual CMS
	 */
	if ( in_array( 'sitepress-multilingual-cms/sitepress.php', $bp_plugins ) ) {

		/**
		 * Add fix for WPML redirect issue
		 * @since BuddyBoss 1.4.0
		 *
		 * @param array $q
		 *
		 * @return array
		 */
		function bp_core_fix_wpml_redirection( $q ) {
			if (
				! defined( 'DOING_AJAX' )
				&& ! bp_is_blog_page()
				&& (bool) $q->get( 'page_id' ) === false
				&& (bool) $q->get( 'pagename' ) === true
			) {
				$bp_current_component = bp_current_component();
				$bp_pages             = bp_core_get_directory_pages();

				if ( 'photos' === $bp_current_component && isset( $bp_pages->media->id ) ) {
					$q->set( 'page_id', $bp_pages->media->id );
				} elseif ( 'forums' === $bp_current_component && isset( $bp_pages->members->id ) ) {
					$q->set( 'page_id', $bp_pages->members->id );
				} elseif ( 'groups' === $bp_current_component && isset( $bp_pages->groups->id ) ) {
					$q->set( 'page_id', $bp_pages->groups->id );
				} elseif ( 'documents' === $bp_current_component && isset( $bp_pages->document->id ) ) {
					$q->set( 'page_id', $bp_pages->document->id );
				} else {
					$page_id = apply_filters( 'bpml_redirection_page_id', null, $bp_current_component, $bp_pages );
					if ( $page_id ) {
						$q->set( 'page_id', $page_id );
					}
				}
			}

			return $q;
		}

		add_action( 'parse_query', 'bp_core_fix_wpml_redirection', 5 );

		/**
		 * Fix for url with wpml
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param $url
		 * @return string
		 */
		function bp_core_wpml_fix_get_root_domain( $url ) {
			return untrailingslashit( $url );
		}

		add_filter( 'bp_core_get_root_domain', 'bp_core_wpml_fix_get_root_domain' );

	}
}

add_action( 'init', 'bp_helper_plugins_loaded_callback', 0 );

/**
 * Add User meta as first and last name is update by BuddyBoss Platform itself
 *
 * @since BuddyBoss 1.1.9
 *
 * @param int $user_id Register member user id
 */
function bp_core_updated_flname_memberpress_buddypress( $user_id ) {
	$user_id = empty( $user_id ) ? bp_loggedin_user_id() : $user_id;
	update_user_meta( $user_id, 'bp_flname_sync', 1 );
}

/**
 * Add Menu in Admin section for MemberPress + BuddyPress Integration plugin
 *
 * @since BuddyBoss 1.1.9
 *
 * @param $menus
 */
function bp_core_add_admin_menu_for_memberpress_buddypress( $menus ) {
	// Define the WordPress global.
	global $wp_admin_bar, $bp;

	if ( ! bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) ) {
		return;
	}

	$main_slug = apply_filters( 'mepr-bp-info-main-nav-slug', 'mp-membership' );
	$name      = apply_filters( 'mepr-bp-info-main-nav-name', _x( 'Membership', 'ui', 'buddyboss' ) );
	$position  = apply_filters( 'mepr-bp-info-main-nav-position', 25 );

	$wp_admin_bar->add_menu(
		array(
			'parent'   => $bp->my_account_menu_id,
			'id'       => $main_slug,
			'title'    => $name,
			'href'     => $bp->loggedin_user->domain . $main_slug . '/',
			'position' => $position,
		)
	);

	// add submenu item
	$wp_admin_bar->add_menu(
		array(
			'parent' => $main_slug,
			'id'     => 'mp-info',
			'title'  => _x( 'Info', 'ui', 'buddyboss' ),
			'href'   => $bp->loggedin_user->domain . $main_slug . '/',
		)
	);

	// add submenu item
	$wp_admin_bar->add_menu(
		array(
			'parent' => $main_slug,
			'id'     => 'mp-subscriptions',
			'title'  => _x( 'Subscriptions', 'ui', 'buddyboss' ),
			'href'   => $bp->loggedin_user->domain . $main_slug . '/mp-subscriptions/',
		)
	);

	// add submenu item
	$wp_admin_bar->add_menu(
		array(
			'parent' => $main_slug,
			'id'     => 'mp-payments',
			'title'  => _x( 'Payments', 'ui', 'buddyboss' ),
			'href'   => $bp->loggedin_user->domain . $main_slug . '/mp-payments/',
		)
	);

}

/**
 * On BuddyPress update
 *
 * @since BuddyBoss 1.0.9
 */
function bp_core_update_group_fields_id_in_db() {

	if ( is_multisite() ) {
		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		$table_name = $bp_prefix . 'bp_xprofile_fields';

		if ( empty( bp_xprofile_firstname_field_id( 0, false ) ) ) {
			// first name fields update
			$firstname = bp_get_option( 'bp-xprofile-firstname-field-name' );
			$results   = $wpdb->get_results( "SELECT id FROM {$table_name} WHERE name = '{$firstname}' AND can_delete = 0" );
			$count     = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-firstname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		if ( empty( bp_xprofile_lastname_field_id( 0, false ) ) ) {
			// last name fields update
			$lastname = bp_get_option( 'bp-xprofile-lastname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$lastname}' AND can_delete = 0" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-lastname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		if ( empty( bp_xprofile_nickname_field_id( true, false ) ) ) {
			// nick name fields update
			$nickname = bp_get_option( 'bp-xprofile-nickname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$nickname}' AND can_delete = 0" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-nickname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		add_site_option( 'bp-xprofile-field-ids-updated', 1 );
	}
}

add_action( 'xprofile_admin_group_action', 'bp_core_update_group_fields_id_in_db', 100 );

/**
 * Remove the Author Taxonomies as that is added by Co-Authors Plus which is not used full.
 *
 * Support Co-Authors Plus
 *
 * @since 1.1.7
 *
 * @param array $taxonomies Taxonomies which are registered for the requested object or object type
 * @param array $post_type Post type
 *
 * @return array Return the names or objects of the taxonomies which are registered for the requested object or object type
 */
function bp_core_remove_authors_taxonomy_for_co_authors_plus( $taxonomies = array(), $post_type ) {

	delete_blog_option( bp_get_root_blog_id(), "bp_search_{$post_type}_tax_author" );
	return array_diff( $taxonomies, array( 'author' ) );
}

/**
 * Include plugin when plugin is activated
 *
 * Support Google Captcha Pro
 *
 * @since BuddyBoss 1.1.9
 */
function bp_core_add_support_for_google_captcha_pro( $section_notice, $section_slug ) {

	// check for BuddyPress plugin
	if ( 'buddypress' === $section_slug ) {
		$section_notice = '';
	}

	// check for bbPress plugin
	if ( 'bbpress' === $section_slug ) {
		$section_notice = '';
		if ( empty( bp_is_active( 'forums' ) ) ) {
			$section_notice = sprintf(
				'<a href="%s">%s</a>',
				bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) ),
				__( 'Activate Forum Discussions Component', 'buddyboss' )
			);
		}
	}

	return $section_notice;

}

add_filter( 'gglcptch_section_notice', 'bp_core_add_support_for_google_captcha_pro', 100, 2 );


/**
 * Update the BuddyBoss Platform Fields when user register from MemberPress Registration form
 *
 * Support MemberPress and MemberPress Pro
 *
 * @since BuddyBoss 1.1.9
 */
function bp_core_add_support_mepr_signup_map_user_fields( $txn ) {
	if ( ! empty( $txn->user_id ) ) {
		bp_core_map_user_registration( $txn->user_id, true );
	}
}

add_action( 'mepr-signup', 'bp_core_add_support_mepr_signup_map_user_fields', 100 );

/**
 * Include plugin when plugin is activated
 *
 * Support LearnDash & bbPress Integration
 *
 * @since BuddyBoss 1.1.9
 */
function bp_core_learndash_bbpress_notices() {
	global $bp_plugins;

	if ( empty( bp_is_active( 'forums' ) ) || empty( in_array( 'sfwd-lms/sfwd_lms.php', $bp_plugins ) ) ) {
		$links = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) );

		$text     = sprintf( '<a href="%s">%s</a>', $links, __( 'Forum Discussions', 'buddyboss' ) );
		$activate = sprintf( '<a href="%s">%s</a>', $links, __( 'activate', 'buddyboss' ) );
		?>
		<div id="message" class="error notice">
			<p><strong><?php esc_html_e( 'LearnDash & bbPress Integration is deactivated.', 'buddyboss' ); ?></strong></p>
			<p><?php printf( esc_html__( 'The LearnDash & bbPress Integration plugin can\'t work if LearnDash LMS plugin & %1$s component is deactivated. Please activate LearnDash LMS plugin & %2$s component.', 'buddyboss' ), $text, $text, $activate ); ?></p>
		</div>
		<?php
	}
}

/**
 * Fix PHP notices in WooCommerce status Menu
 *
 * @since BuddyBoss 1.2.0
 *
 * @param $tabs
 *
 * @return mixed
 */
function bp_core_fix_notices_woocommerce_admin_status( $tabs ) {
	if ( isset( $_GET['page'] ) && 'wc-status' == $_GET['page'] ) {
		bp_core_unset_bbpress_buddypress_active();
	}
	return $tabs;
}
add_filter( 'woocommerce_admin_status_tabs', 'bp_core_fix_notices_woocommerce_admin_status' );

/**
 * Fix forums subscription tab in user's profile.
 *
 * @param $passed
 *
 * @return bool
 * @since BuddyBoss 1.3.3
 */
function bp_core_fix_forums_subscriptions_tab( $passed ) {
	$bp_current_component = bp_current_component();
	$bp_current_action    = bp_current_action();

	if ( 'forums' === $bp_current_component && $bp_current_action === bbp_get_user_subscriptions_slug() ) {
		$passed = false;
	}

	return $passed;
}

add_filter( 'woocommerce_account_endpoint_page_not_found', 'bp_core_fix_forums_subscriptions_tab' );

/**
 * Fix Memberpress Privacy for BuddyPress pages.
 *
 * @since BuddyBoss 1.2.4
 *
 * @param mixed $content
 *
 * @return mixed
 */
function bp_core_memberpress_the_content( $content ) {
	if ( class_exists( 'MeprBaseModel' ) ) {
		global $post;
		$page_ids = bp_core_get_directory_page_ids();

		if (
			bp_is_groups_component()
			&& !empty( $page_ids['groups'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['groups'] );
		} else if (
			bp_is_media_component()
			&& !empty( $page_ids['media'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['media'] );
		} else if (
			bp_is_members_component()
			&& !empty( $page_ids['members'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['members'] );
		} else if (
			bp_is_activity_component()
			&& !empty( $page_ids['activity'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['activity'] );
		}
	}

	return $content;
}
add_filter( 'the_content', 'bp_core_memberpress_the_content', 999 );

/**
 * Fix Medium Editor version conflict with user blog plugin
 *
 * @since BuddyBoss 1.3.4
 *
 */
function bp_remove_user_blog_disable_medium_editor_js() {
	if ( bp_is_activity_directory() || bp_is_user_activity() || bp_is_group_activity() ) {
		wp_dequeue_script('buddyboss-bower-medium-editor');
	}
}
add_action('wp_enqueue_scripts', 'bp_remove_user_blog_disable_medium_editor_js', 100);

/**
 * Removed WC filter to the settings page when its active.
 *
 * @since BuddyBoss 1.3.3
 */
function bp_settings_remove_wc_lostpassword_url() {
	if ( class_exists( 'woocommerce' ) ) {
		remove_filter( 'lostpassword_url', 'wc_lostpassword_url', 10, 1 );
	}
}
add_action( 'bp_before_member_settings_template', 'bp_settings_remove_wc_lostpassword_url' );
add_action( 'login_form_login', 'bp_settings_remove_wc_lostpassword_url' );
