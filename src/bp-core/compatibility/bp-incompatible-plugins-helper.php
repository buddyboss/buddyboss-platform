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
		 *
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
				} elseif ( 'videos' === $bp_current_component && isset( $bp_pages->video->id ) ) {
					$q->set( 'page_id', $bp_pages->video->id );
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

	/**
	 * Fix email subject, content and link
     *
	 * @since BuddyBoss 1.5.4
	 */
	if ( in_array( 'wishlist-member/wpm.php', $bp_plugins ) ) {
		global $WishListMemberInstance;
		remove_filter( 'user_request_action_email_content', array( &$WishListMemberInstance, 'privacy_user_request_email' ), 10 );
		remove_filter( 'user_request_action_email_subject', array( &$WishListMemberInstance, 'privacy_user_request_email_subject' ), 10 );
		remove_filter( 'wp_privacy_personal_data_email_content', array( &$WishListMemberInstance, 'privacy_personal_data_email' ), 10 );
	}

	if ( in_array( 'instructor-role/instructor.php', $bp_plugins, true ) ) {

		/**
		 * Function to exclude group type post to prevent group role overriding.
		 *
		 * @param array $exclude_posts excluded post types.
		 *
		 * @return array|mixed
		 */
		function bp_core_instructor_role_post_exclude( $exclude_posts ) {

			if ( is_array( $exclude_posts ) ) {
				$exclude_posts[] = 'bp-group-type';
			}

			return $exclude_posts;
		}

		add_filter( 'wdmir_exclude_post_types', 'bp_core_instructor_role_post_exclude', 10, 1 );
	}

	/**
	 * Include filters when Woocommerce plugin is activated
	 *
	 * Support Woocommerce
	 */
	if ( class_exists( 'WooCommerce' ) ) {
		require buddypress()->compatibility_dir . '/class-bb-woocommerce-helpers.php';
	}
}
add_action( 'init', 'bp_helper_plugins_loaded_callback', 0 );

/**
 * Helper functions for the offload media compatibility.
 */
function bb_wp_offload_media_compatibility_helper() {

	if ( class_exists( 'WP_Offload_Media_Autoloader' ) && class_exists( 'Amazon_S3_And_CloudFront' ) ) {
		require buddypress()->compatibility_dir . '/bp-wp-offload-media-helpers.php';
	}

}
add_action( 'init', 'bb_wp_offload_media_compatibility_helper', 999 );

/**
 * Fix the media, video & document display compatibility issue.
 *
 * @since BuddyBoss 1.7.9
 */
function bb_seo_press_compatibility_helper() {

	if ( (bool) bp_get_option( 'seopress_activated' ) ) {
		if (
			(
				! empty( get_query_var( 'bb-media-preview' ) ) ||
				! empty( get_query_var( 'bb-document-preview' ) ) ||
				! empty( get_query_var( 'bb-document-player' ) ) ||
				! empty( get_query_var( 'bb-video-thumb-preview' ) ) ||
				! empty( get_query_var( 'bb-video-preview' ) )
			)
			&&
			(
				function_exists( 'seopress_redirections_enabled' ) &&
				'yes' === seopress_redirections_enabled()
			)
		) {
			remove_action( 'template_redirect', 'seopress_redirections_hook', 1 );
		}
	}

}

add_action( 'wp', 'bb_seo_press_compatibility_helper', 9999 );

/**
 * Add User meta as first and last name is update by BuddyBoss Platform itself
 *
 * @since BuddyBoss 1.1.9
 *
 * @param int $user_id Register member user id.
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
 * @param array $taxonomies Taxonomies which are registered for the requested object or object type.
 * @param array $post_type  Post type.
 *
 * @return array Return the names or objects of the taxonomies which are registered for the requested object or object type
 */
function bp_core_remove_authors_taxonomy_for_co_authors_plus( $taxonomies, $post_type ) {

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

	// check for BuddyPress plugin.
	if ( 'buddypress' === $section_slug ) {
		$section_notice = '';
	}

	// check for bbPress plugin.
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
			&& ! empty( $page_ids['groups'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['groups'] );
		} elseif (
			bp_is_media_component()
			&& ! empty( $page_ids['media'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['media'] );
		} elseif (
			bp_is_members_component()
			&& ! empty( $page_ids['members'] )
			&& empty( $post->ID )
		) {
			$post = get_post( $page_ids['members'] );
		} elseif (
			bp_is_activity_component()
			&& ! empty( $page_ids['activity'] )
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
 */
function bp_remove_user_blog_disable_medium_editor_js() {
	if ( bp_is_activity_directory() || bp_is_user_activity() || bp_is_group_activity() ) {
		wp_dequeue_script( 'buddyboss-bower-medium-editor' );
	}
}
add_action( 'wp_enqueue_scripts', 'bp_remove_user_blog_disable_medium_editor_js', 100 );

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

/**
 * Fix elementor editor issue while bp page set as front.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param boolean $bool Boolean to return.
 *
 * @return boolean
 */
function bp_core_set_uri_elementor_show_on_front( $bool ) {
	if (
		isset( $_REQUEST['elementor-preview'] )
		|| (
			is_admin() &&
			isset( $_REQUEST['action'] )
			&& (
				'elementor' === $_REQUEST['action']
				|| 'elementor_ajax' === $_REQUEST['action']
			)
		)
	) {
		return false;
	}

	return $bool;
}
add_filter( 'bp_core_set_uri_show_on_front', 'bp_core_set_uri_elementor_show_on_front', 10, 3 );

/**
 * Get the forum page id from settings.
 *
 * @since BuddyBoss 1.5.8
 *
 * @return int|bool.
 */
function bp_get_forum_page_id() {
	// Get the page id from the options.
	$id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );

	// Check the status of current set value.
	$status = get_post_status( $id );

	// Set the page id if page exists and in publish otherwise set false.
	return ( '' !== $status && 'publish' === $status ) ? $id : false;
}

/**
 * Generate Yoast SEO title.
 *
 * @since BuddyBoss 1.5.8
 *
 * @param string $title        Default SEO title
 * @param object $presentation Collection data for presentation
 *
 * @return string.
 */
function bp_wpseo_title( $title, $presentation ) {
	// Get forum page id
	$page_id = bp_get_forum_page_id();

	if ( empty( $page_id ) || ! class_exists( 'WPSEO_Replace_Vars' ) ) {
		return $title;
	}

	// Yoast SEO title regarding page id.
	$title = get_post_meta( $page_id, '_yoast_wpseo_title', true );

	// Replace title tag with actual value.
	$replace_vars = new WPSEO_Replace_Vars();
	$page         = get_post( $page_id );
	$title        = $replace_vars->replace( $title, $page );

	return $title;
}

/**
 * Generate Yoast SEO description.
 *
 * @since BuddyBoss 1.5.8
 *
 * @param string $meta_description Default SEO description
 * @param object $presentation     Collection data for presentation
 *
 * @return string.
 */
function bp_wpseo_meta_description( $meta_description, $presentation ) {
	// Get forum page id
	$page_id = bp_get_forum_page_id();

	if ( empty( $page_id ) || ! class_exists( 'WPSEO_Replace_Vars' ) ) {
		return $meta_description;
	}

	// Yoast SEO description regarding page id.
	$meta_description = get_post_meta( $page_id, '_yoast_wpseo_metadesc', true );

	// Replace title tag with actual value.
	$replace_vars     = new WPSEO_Replace_Vars();
	$page             = get_post( $page_id );
	$meta_description = $replace_vars->replace( $meta_description, $page );

	return $meta_description;
}

/**
 * Generate Yoast SEO meta.
 *
 * @since BuddyBoss 1.5.8
 *
 * @return void.
 */
function set_yoast_meta_tags() {
	// Prevent execution when forum is disabled.
	if ( ! bp_is_active( 'forums' ) ) {
		return;
	}

	// Is current post type is forum.
	if ( bbp_get_forum_post_type() == get_post_type() ) {
		// Filter default SEO title for yoast.
		add_filter( 'wpseo_title', 'bp_wpseo_title', 10, 2 );

		// Filter default SEO description for yoast.
		add_filter( 'wpseo_metadesc', 'bp_wpseo_meta_description', 10, 2 );
	}
}

// Trigger before template load.
add_action( 'bp_template_redirect', 'set_yoast_meta_tags' );

/**
 * Prevent BB template redering and Redirect to the Elementor "Maintenance Mode" template.
 *
 * @since BuddyBoss 1.5.8
 *
 * @return void
 */
function bb_get_elementor_maintenance_mode_template() {
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		return;
	}

	if ( isset( $_GET['elementor-preview'] ) && get_the_ID() === (int) $_GET['elementor-preview'] ) {
		return;
	}

	$is_login_page = apply_filters( 'elementor/maintenance_mode/is_login_page', false );

	if ( $is_login_page ) {
		return;
	}

	$user         = wp_get_current_user();
	$exclude_mode = get_option( 'elementor_maintenance_mode_exclude_mode' );

	if ( 'logged_in' === $exclude_mode && is_user_logged_in() ) {
		return;
	}

	if ( 'custom' === $exclude_mode ) {
		$exclude_roles = get_option( 'elementor_maintenance_mode_exclude_roles' );
		$user_roles    = $user->roles;

		if ( is_multisite() && is_super_admin() ) {
			$user_roles[] = 'super_admin';
		}

		$compare_roles = array_intersect( $user_roles, $exclude_roles );

		if ( ! empty( $compare_roles ) ) {
			return;
		}
	}

	$mode = get_option( 'elementor_maintenance_mode_mode' );

	if ( 'maintenance' === $mode || 'coming_soon' === $mode ) {
		remove_action( 'template_redirect', 'bp_template_redirect', 10 );
	}
}
add_action( 'bp_loaded', 'bb_get_elementor_maintenance_mode_template' );

/**
 * Load rest compatibility.
 *
 * @since BuddyBoss 1.6.0
 */
function bb_rest_compatibility_loader() {
	// BuddyPress Groups Tabs creator pro plugin support.
	if ( class_exists( 'BPGTC_Group_Tabs_Pro' ) ) {
		require_once dirname( __FILE__ ) . '/bp-rest-groups-tabs-creator-pro.php';
	}
}
add_action( 'bp_rest_api_init', 'bb_rest_compatibility_loader', 5 );

/**
 * Remove the 'group_leader' role for Learndash group author.
 * If the author is not the leader of any gorup.
 *
 * @since BuddyBoss 1.6.3
 *
 * @param int $post_id WP Post ID.
 *
 * @uses learndash_is_admin_user()                Is the author has administrator role.
 * @uses learndash_is_group_leader_user()         Is the author has group_leader role.
 * @uses learndash_get_administrators_group_ids() Gets the list of group IDs administered by the user.
 *
 * @return void
 */
function bb_learndash_delete_group( $post_id = 0 ) {
	// Is Learndash active or not.
	if ( ! defined( 'LEARNDASH_VERSION' ) ) {
		return;
	}

	$post = get_post( $post_id );

	// Is it trash or not.
	if ( 'revision' !== $post->post_type ) {
		return;
	}

	$post_parent = get_post( $post->post_parent );

	if ( 'groups' !== $post_parent->post_type ) {
		return;
	}

	$group_id = get_post_meta( $post_parent->ID, '_sync_group_id', true );

	if ( empty( $group_id ) ) {
		return;
	}

	$author = $post_parent->post_author;

	// When the group author has already administrator role.
	if ( learndash_is_admin_user( $author ) ) {
		return;
	}

	// When the group author has no group_leader role.
	if ( ! learndash_is_group_leader_user( $author ) ) {
		return;
	}

	// Gets the list of group IDs administered by the user.
	$group_ids = learndash_get_administrators_group_ids( $author );

	if ( ! empty( $group_ids ) ) {
		return;
	}

	$user = new \WP_User( $author );
	// Add role.
	$user->remove_role( 'group_leader' );
}
add_action( 'delete_post', 'bb_learndash_delete_group' );

/**
 * Add the 'group_leader' role for Learndash group author.
 * When learndash group status change form trash to draft.
 *
 * @since BuddyBoss 1.6.3
 *
 * @param int $post_id LearnDash group id.
 *
 * @uses learndash_is_admin_user() Is the author has administrator role.
 * @uses bb_learndash_role_add()   Add group author role as 'group_leade'.
 *
 * @return void
 */
function bb_learndash_untrash_group( $post_id ) {
	// Is Learndash active or not.
	if ( ! defined( 'LEARNDASH_VERSION' ) ) {
		return;
	}

	$ldgroup = get_post( $post_id );

	if ( 'groups' !== $ldgroup->post_type || 'trash' !== $ldgroup->post_status ) {
		return;
	}

	$group_id = get_post_meta( $ldgroup->ID, '_sync_group_id', true );

	if ( empty( $group_id ) ) {
		return;
	}

	if ( ! function_exists( 'learndash_is_admin_user' ) ) {
		return;
	}

	$author = $ldgroup->post_author;

	// When the group author has administrator role.
	if ( learndash_is_admin_user( $author ) ) {
		return;
	}

	$user = new Buddyboss\LearndashIntegration\Library\SyncGenerator();
	$user->promoteAsGroupLeader( $author, 'admin' );
}
add_action( 'untrash_post', 'bb_learndash_untrash_group' );

/**
 * Add user role as 'group_leader'
 *
 * @since BuddyBoss 1.6.3
 *
 * @param int $user_id Update user id.
 *
 * @return void
 */
function bb_learndash_role_add( $user_id, $before ) {
	// Is Learndash active or not.
	if ( ! defined( 'LEARNDASH_VERSION' ) ) {
		return;
	}

	if ( ! in_array( 'group_leader', $before->roles, true ) ) {
		return;
	}

	$user = new Buddyboss\LearndashIntegration\Library\SyncGenerator();
	$user->promoteAsGroupLeader( $user_id, 'admin' );
}
add_action( 'profile_update', 'bb_learndash_role_add', 10, 2 );





/**
 * Function to set the false to use the default media symlink instead use the WP Stateless media URL of media.
 *
 * @param bool   $can           default true.
 * @param int    $id            media/document/video id.
 * @param int    $attachment_id attachment id.
 * @param string $size          preview size.
 *
 * @return bool true if the offload media used.
 *
 * @since BuddyBoss 1.7.2
 */
function bb_wp_stateless_do_symlink( $can, $id, $attachment_id, $size ) {
	if ( function_exists( 'ud_get_stateless_media' ) && in_array( ud_get_stateless_media()->get( 'sm.mode' ), array( 'cdn', 'ephemeral', 'stateless' ), true ) ) {
		$can = false;
	}

	return $can;
}

add_filter( 'bb_media_do_symlink', 'bb_wp_stateless_do_symlink', PHP_INT_MAX, 4 );
add_filter( 'bb_document_do_symlink', 'bb_wp_stateless_do_symlink', PHP_INT_MAX, 4 );
add_filter( 'bb_video_do_symlink', 'bb_wp_stateless_do_symlink', PHP_INT_MAX, 4 );
add_filter( 'bb_video_create_thumb_symlinks', 'bb_wp_stateless_do_symlink', PHP_INT_MAX, 4 );

/**
 * Return the WP Stateless media plugin attachment url.
 *
 * @param string $attachment_url Attachment url.
 * @param int    $video_id       Video id.
 * @param int    $attachment_id  Attachment id.
 *
 * @return string $attachment_url Attachment URL.
 *
 * @since BuddyBoss 1.7.2
 */
function bp_video_wp_stateless_get_video_url( $attachment_url, $video_id, $attachment_id ) {

	if ( function_exists( 'ud_get_stateless_media' ) && in_array( ud_get_stateless_media()->get( 'sm.mode' ), array( 'cdn', 'ephemeral', 'stateless' ), true ) ) {
		$attachment_url = wp_get_attachment_url( $attachment_id );
	}

	return $attachment_url;
}

add_filter( 'bb_video_get_symlink', 'bp_video_wp_stateless_get_video_url', PHP_INT_MAX, 3 );

/**
 * Return the WP Stateless media plugin attachment url.
 *
 * @param string $attachment_url Attachment url.
 * @param int    $video_id       Video id.
 * @param string $size           size of the media.
 * @param int    $attachment_id  Attachment id.
 *
 * @return false|mixed|string return the original document URL.
 *
 * @since BuddyBoss 1.7.2
 */
function bp_video_wp_stateless_get_thumb_preview_url( $attachment_url, $video_id, $size, $attachment_id ) {
	if ( function_exists( 'ud_get_stateless_media' ) && in_array( ud_get_stateless_media()->get( 'sm.mode' ), array( 'cdn', 'ephemeral', 'stateless' ), true ) ) {
		$get_metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
			$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
		} else {
			$attachment_url = wp_get_attachment_url( $attachment_id );
		}
	}

	return $attachment_url;
}

add_filter( 'bb_video_get_thumb_url', 'bp_video_wp_stateless_get_thumb_preview_url', PHP_INT_MAX, 4 );

/**
 * Return the WP Stateless media plugin attachment url.
 *
 * @param string $attachment_url attachment url.
 * @param int    $media_id       media id.
 * @param int    $attachment_id  attachment id.
 * @param string $size           size of the media.
 *
 * @return false|mixed|string return the original media URL.
 *
 * @since BuddyBoss 1.7.2
 */
function bp_media_wp_stateless_get_preview_url( $attachment_url, $media_id, $attachment_id, $size ) {
	if ( function_exists( 'ud_get_stateless_media' ) && in_array( ud_get_stateless_media()->get( 'sm.mode' ), array( 'cdn', 'ephemeral', 'stateless' ), true ) ) {
		$media          = new BP_Media( $media_id );
		$attachment_url = wp_get_attachment_url( $media->attachment_id );
	}

	return $attachment_url;
}

add_filter( 'bp_media_get_preview_image_url', 'bp_media_wp_stateless_get_preview_url', PHP_INT_MAX, 4 );

/**
 * Return the WP Stateless media plugin attachment url.
 *
 * @param string $attachment_url attachment url.
 * @param int    $document_id    media id.
 * @param string $extension      extension.
 * @param string $size           size of the media.
 * @param int    $attachment_id  attachment id.
 *
 * @return false|mixed|string return the original document URL.
 *
 * @since BuddyBoss 1.7.2
 */
function bp_document_wp_stateless_get_preview_url( $attachment_url, $document_id, $extension, $size, $attachment_id ) {

	if ( function_exists( 'ud_get_stateless_media' ) && in_array( ud_get_stateless_media()->get( 'sm.mode' ), array( 'cdn', 'ephemeral', 'stateless' ), true ) ) {

		if ( in_array( $extension, bp_get_document_preview_doc_extensions(), true ) ) {
			$get_metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
				$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
			} else {
				$attachment_url = wp_get_attachment_image_url( $attachment_id, 'full' );
			}

			if ( ! $attachment_url ) {
				bp_document_generate_document_previews( $attachment_id );
				if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
					$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
				} else {
					$attachment_url = wp_get_attachment_image_url( $attachment_id, 'full' );
				}
			}
		}

		if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
			if ( ! empty( $attachment_id ) && ! empty( $document_id ) ) {
				$attachment_url = wp_get_attachment_url( $attachment_id );
			}
		}
	}

	return $attachment_url;
}

add_filter( 'bp_document_get_preview_url', 'bp_document_wp_stateless_get_preview_url', PHP_INT_MAX, 5 );

/**
 * Fix Elementor conflict for forum parent field.
 * Remove the Page Attributes meta box from forum edit page
 * since Element's page attributes parent field is conflicting with forum attributes patent field
 *
 * @return void
 *
 * @since 1.7.6
 */
function bbp_remove_page_attributes_metabox_for_forum() {

	// Check if elementor is exists.
	if ( class_exists( '\Elementor\Plugin' ) ) {
		// Remove the page attribute meta box for forum screen.
		remove_meta_box( 'pageparentdiv' , 'forum' , 'side' );
	}

}

add_action( 'admin_menu' , 'bbp_remove_page_attributes_metabox_for_forum' );
