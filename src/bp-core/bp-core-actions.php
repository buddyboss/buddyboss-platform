<?php
/**
 * BuddyPress Filters & Actions.
 *
 * This file contains the actions and filters that are used through-out BuddyPress.
 * They are consolidated here to make searching for them easier, and to help
 * developers understand at a glance the order in which things occur.
 *
 * @package BuddyBoss\Hooks
 * @since BuddyPress 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Attach BuddyPress to WordPress.
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_action( 'plugins_loaded', 'bp_loaded', 10 );
add_action( 'init', 'bp_init', 10 );
add_action( 'rest_api_init', 'bp_rest_api_init', 20 ); // After WP core.
add_action( 'customize_register', 'bp_customize_register', 20 ); // After WP core.
add_action( 'parse_query', 'bp_parse_query', 2 ); // Early for overrides.
add_action( 'wp', 'bp_ready', 10 );
add_action( 'set_current_user', 'bp_setup_current_user', 10 );
add_action( 'setup_theme', 'bp_setup_theme', 10 );
add_action( 'after_setup_theme', 'bp_after_setup_theme', 100 ); // After WP themes.
add_action( 'wp_enqueue_scripts', 'bp_enqueue_scripts', 10 );
add_action( 'enqueue_embed_scripts', 'bp_enqueue_embed_scripts', 10 );
add_action( 'admin_bar_menu', 'bp_setup_admin_bar', 20 ); // After WP core.
add_action( 'template_redirect', 'bp_template_redirect', 10 );
add_action( 'widgets_init', 'bp_widgets_init', 10 );
add_action( 'generate_rewrite_rules', 'bp_generate_rewrite_rules', 10 );

/**
 * The bp_loaded hook - Attached to 'plugins_loaded' above.
 *
 * Attach various loader actions to the bp_loaded action.
 * The load order helps to execute code at the correct time.
 *                                                      v---Load order
 */
add_action( 'bp_loaded', 'bp_setup_components', 2 );
add_action( 'bp_loaded', 'bp_setup_integrations', 3 );
add_action( 'bp_loaded', 'bp_include', 4 );
add_action( 'bp_loaded', 'bp_setup_option_filters', 5 );
add_action( 'bp_loaded', 'bp_setup_cache_groups', 5 );
add_action( 'bp_loaded', 'bp_setup_widgets', 6 );
add_action( 'bp_loaded', 'bp_register_theme_packages', 12 );

/**
 * The bp_init hook - Attached to 'init' above.
 *
 * Attach various initialization actions to the bp_init action.
 * The load order helps to execute code at the correct time.
 *                                                   v---Load order
 */
add_action( 'bp_init', 'bp_register_post_types', 2 );
add_action( 'bp_init', 'bp_register_taxonomies', 2 );
add_action( 'bp_init', 'bp_core_set_uri_globals', 2 );
add_action( 'bp_init', 'bp_setup_globals', 4 );
add_action( 'bp_init', 'bp_setup_canonical_stack', 5 );
add_action( 'bp_init', 'bp_setup_nav', 6 );
add_action( 'bp_init', 'bp_setup_title', 8 );
add_action( 'bp_init', 'bp_core_load_admin_bar_css', 12 );
add_action( 'bp_init', 'bp_add_rewrite_tags', 20 );
add_action( 'bp_init', 'bp_add_rewrite_rules', 30 );
add_action( 'bp_init', 'bp_add_permastructs', 40 );
add_action( 'bp_init', 'bp_init_background_updater', 50 );
add_action( 'bp_init', 'bb_init_email_background_updater', 51 );
add_action( 'bp_init', 'bb_init_notifications_background_updater', 52 );
add_action( 'bp_init', 'bb_init_background_updater', 50 );

/**
 * The bp_register_taxonomies hook - Attached to 'bp_init' @ priority 2 above.
 */
add_action( 'bp_register_taxonomies', 'bp_register_member_types' );

/**
 * Late includes.
 *
 * Run after the canonical stack is setup to allow for conditional includes
 * on certain pages.
 */
add_action( 'bp_setup_canonical_stack', 'bp_late_include', 20 );

/**
 * The bp_template_redirect hook - Attached to 'template_redirect' above.
 *
 * Attach various template actions to the bp_template_redirect action.
 * The load order helps to execute code at the correct time.
 *
 * Note that we currently use template_redirect versus template include because
 * BuddyPress is a bully and overrides the existing themes output in many
 * places. This won't always be this way, we promise.
 *                                                           v---Load order
 */
add_action( 'bp_template_redirect', 'bp_redirect_canonical', 2 );
add_action( 'bp_template_redirect', 'bp_actions', 4 );
add_action( 'bp_template_redirect', 'bp_screens', 6 );
add_action( 'bp_template_redirect', 'bp_post_request', 10 );
add_action( 'bp_template_redirect', 'bp_get_request', 10 );
add_action( 'bp_template_redirect', 'bp_private_network_template_redirect', 10 );

/**
 * Add the BuddyPress functions file and the Theme Compat Default features.
 */
add_action( 'bp_after_setup_theme', 'bp_check_theme_template_pack_dependency', -10 );
add_action( 'bp_after_setup_theme', 'bp_load_theme_functions', 1 );
add_action( 'bp_after_setup_theme', 'bp_show_hide_toolbar', 9999999 );

// Restrict user when view media/document from url.
add_action( 'template_redirect', 'bp_restrict_single_attachment', 999 );

// Load Post Notifications.
add_action( 'bp_core_components_included', 'bb_load_post_notifications' );
add_action( 'comment_post', 'bb_post_new_comment_reply_notification', 20, 3 );
add_action( 'wp_insert_comment', 'bb_post_new_comment_reply_notification_helper', 20, 2 );
add_action( 'transition_comment_status', 'bb_post_comment_on_status_change', 20, 3 );

// Load the admin.
if ( is_admin() ) {
	add_action( 'bp_loaded', 'bp_admin' );
}

// Activation redirect.
add_action( 'bp_activation', 'bp_add_activation_redirect' );

// Add Platform plugin updater code.
if ( is_admin() ) {
	add_action( 'bp_admin_init', 'bp_platform_plugin_updater' );
}

// Email unsubscribe.
add_action( 'bp_get_request_unsubscribe', 'bp_email_unsubscribe_handler' );

add_action(
	'bp_init',
	function() {
		if ( false === bb_enabled_legacy_email_preference() ) {
			// Render notifications on frontend.
			add_action( 'bp_notification_settings', 'bb_render_notification_settings', 1 );
		}
	}
);

add_action(
	'bp_init',
	function() {
		$component = bp_get_option( 'bp-active-components' );

		// Set the "Document" component active/inactive based on the media components.
		if ( isset( $component ) && isset( $component['media'] ) && '1' === $component['media'] && empty( $component['document'] ) ) {
			$component['document'] = '1';
			bp_update_option( 'bp-active-components', $component );
		} elseif ( isset( $component ) && isset( $component['document'] ) && empty( $component['media'] ) ) {
			unset( $component['document'] );
			bp_update_option( 'bp-active-components', $component );
		}

		// Set the "Video" component active/inactive based on the media components.
		if ( isset( $component ) && isset( $component['media'] ) && '1' === $component['media'] && empty( $component['video'] ) ) {
			$component['video'] = '1';
			bp_update_option( 'bp-active-components', $component );
		} elseif ( isset( $component ) && isset( $component['video'] ) && empty( $component['media'] ) ) {
			unset( $component['video'] );
			bp_update_option( 'bp-active-components', $component );
		}
	},
	10,
	2
);

/**
 * Restrict user when visit attachment url from media/document.
 * - Privacy security.
 *
 * @since BuddyBoss 1.5.5
 */
function bp_restrict_single_attachment() {
	if ( is_attachment() ) {
		global $post;
		if ( ! empty( $post ) ) {
			$media_meta    = get_post_meta( $post->ID, 'bp_media_upload', true );
			$document_meta = get_post_meta( $post->ID, 'bp_document_upload', true );
			if (
				! empty( $media_meta ) ||
				! empty( $document_meta )
			) {
				bp_do_404();
				return;
			}
		}
	}
}

/**
 * Validate and update symlink option value.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $updated_value Current value of options.
 */
function bb_media_symlink_validate( $updated_value ) {
	$keys = array(
		'bb_media_symlink_type',
		'bb_document_symlink_type',
		'bb_document_video_symlink_type',
		'bb_video_symlink_type',
		'bb_video_thumb_symlink_type',
	);

	if ( true === bb_check_server_disabled_symlink() ) {
		bp_update_option( 'bp_media_symlink_support', 0 );
		foreach ( $keys as $k ) {
			bp_delete_option( $k );
		}
		return;
	}

	$output_file_src = '';

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$platform_previews_path = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $platform_previews_path ) ) {
		wp_mkdir_p( $platform_previews_path );
		chmod( $platform_previews_path, 0755 );
	}

	$media_symlinks_path = $platform_previews_path . '/' . md5( 'bb-media' );
	if ( ! is_dir( $media_symlinks_path ) ) {
		wp_mkdir_p( $media_symlinks_path );
		chmod( $media_symlinks_path, 0755 );
	}

	foreach ( $keys as $k ) {
		bp_delete_option( $k );
	}

	if ( empty( $updated_value ) || 0 === $updated_value ) {
		return;
	}

	$attachment_id = bb_core_upload_dummy_attachment();

	if ( ! empty( $attachment_id ) ) {

		$attachment_url  = wp_get_attachment_image_src( $attachment_id );
		$attachment_file = get_attached_file( $attachment_id );
		$symlinks_path   = $media_symlinks_path;
		$size            = 'thumbnail';
		$symlink_name    = md5( 'testsymlink' . $attachment_id . $size );
		$attachment_path = $symlinks_path . '/' . $symlink_name;
		$file            = image_get_intermediate_size( $attachment_id, $size );
		if ( $file && ! empty( $file['path'] ) ) {
			$output_file_src = $upload_dir . '/' . $file['path'];
		} elseif ( $attachment_url ) {
			$output_file_src = $attachment_file;
		}

		$upload_directory        = wp_get_upload_dir();
		$key                     = 'bb_media_symlink_type';
		$preview_attachment_path = $symlinks_path . '/' . $symlink_name;
		$symlink_url             = bb_core_symlink_absolute_path( $preview_attachment_path, $upload_directory );

		if ( file_exists( $output_file_src ) && is_file( $output_file_src ) && ! is_dir( $output_file_src ) && ! file_exists( $attachment_path ) ) {
			if ( ! is_link( $attachment_path ) ) {

				$sym_status = bp_get_option( $key, '' );
				$status     = false;

				if ( empty( $sym_status ) || 'default' === $sym_status ) {

					symlink( $output_file_src, $attachment_path );
				}

				if ( empty( $sym_status ) ) {
					if ( ! empty( $symlink_url ) ) {

						$fetch = wp_remote_get( $symlink_url );
						if ( is_wp_error( $fetch ) ) {
							$fetch = wp_remote_get( $symlink_url, array( 'sslverify' => false ) );
						}

						if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
							$status     = true;
							$sym_status = 'default';
							foreach ( $keys as $k ) {
								bp_update_option( $k, $sym_status );
							}
							bp_delete_option( 'bb_display_support_error' );
						} else {
							bp_update_option( 'bb_display_support_error', 1 );
						}
					}

					if ( false === $status && ! empty( $symlink_url ) && file_exists( $attachment_path ) ) {
						unlink( $attachment_path );
						bp_update_option( 'bp_media_symlink_support', 0 );

						foreach ( $keys as $k ) {
							bp_delete_option( $k );
						}
						bp_update_option( 'bb_display_support_error', 1 );
					} else {
						bp_delete_option( 'bb_display_support_error' );
					}
				}

				if ( false === $status && ( empty( $sym_status ) || 'relative' === $sym_status ) ) {
					$tmp = getcwd();
					chdir( wp_normalize_path( ABSPATH ) );
					$sym_path   = explode( '/', $symlinks_path );
					$search_key = array_search( 'wp-content', $sym_path, true );
					if ( is_array( $sym_path ) && ! empty( $sym_path ) && false !== $search_key ) {
						$sym_path = array_slice( array_filter( $sym_path ), $search_key );
						$sym_path = implode( '/', $sym_path );
					}
					if ( is_dir( 'wp-content/' . $sym_path ) ) {
						chdir( 'wp-content/' . $sym_path );
						if ( empty( $file['path'] ) ) {
							$file['path'] = get_post_meta( $attachment_id, '_wp_attached_file', true );
						}
						$output_file_src = '../../' . $file['path'];
						if ( file_exists( $output_file_src ) ) {
							symlink( $output_file_src, $symlink_name );
						}
					}
					chdir( $tmp );

					if ( empty( $sym_status ) ) {

						if ( ! empty( $symlink_url ) ) {
							$fetch = wp_remote_get( $symlink_url );
							if ( is_wp_error( $fetch ) ) {
								$fetch = wp_remote_get( $symlink_url, array( 'sslverify' => false ) );
							}
							if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
								$status     = true;
								$sym_status = 'relative';
								foreach ( $keys as $k ) {
									bp_update_option( $k, $sym_status );
								}
								bp_delete_option( 'bb_display_support_error' );
							} else {
								bp_update_option( 'bb_display_support_error', 1 );
							}
						}

						if ( false === $status && ! empty( $symlink_url ) && file_exists( $attachment_path ) ) {
							unlink( $attachment_path );
							bp_update_option( 'bp_media_symlink_support', 0 );
							bp_update_option( 'bb_display_support_error', 1 );
							foreach ( $keys as $k ) {
								bp_delete_option( $k );
							}
						} else {
							bp_delete_option( 'bb_display_support_error' );
						}
					}
				}
			}
		}
		wp_delete_attachment( $attachment_id, true );
	} else {

		foreach ( $keys as $k ) {
			bp_delete_option( $k );
		}

		bp_update_option( 'bp_media_symlink_support', 0 );
		bp_core_remove_temp_directory( $upload_dir . '/bb-platform-previews' );
	}
}

/**
 * Check the symlink type default/relative on symlink option update.
 *
 * @since BuddyBoss 1.8.2
 *
 * @param mixed $old_value The old option value.
 * @param mixed $value     The new option value.
 */
function bb_update_media_symlink_support( $old_value, $value ) {
	if ( $old_value !== $value ) {
		bb_media_symlink_validate( $value );
	}
}

add_action( 'update_option_bp_media_symlink_support', 'bb_update_media_symlink_support', 10, 2 );

/**
 * Check and re-start the background process if queue is not empty.
 *
 * @since BuddyBoss 1.8.1
 */
function bb_email_handle_cron_healthcheck() {
	global $bb_email_background_updater;
	if ( $bb_email_background_updater->is_updating() ) {
		$bb_email_background_updater->handle_cron_healthcheck();
	}
}

add_action( 'bb_init_email_background_updater', 'bb_email_handle_cron_healthcheck' );

/**
 * Check and reschedule the background process if queue is not empty.
 *
 * @since BuddyBoss 1.8.3
 */
function bb_handle_cron_healthcheck() {
	global $bp_background_updater;
	if ( $bp_background_updater->is_updating() ) {
		$bp_background_updater->schedule_event();
	}
}

add_action( 'bp_init_background_updater', 'bb_handle_cron_healthcheck' );

/**
 * Check and reschedule the newly added background process if queue is not empty.
 *
 * @since BuddyBoss 2.4.20
 */
function bb_updater_handle_cron_healthcheck() {
	global $bb_background_updater;
	if (
		is_object( $bb_background_updater ) &&
		$bb_background_updater->is_updating()
	) {
		$bb_background_updater->schedule_event();
	}
}

add_action( 'bb_init_background_updater', 'bb_updater_handle_cron_healthcheck' );

/**
 * Function will remove RSS Feeds.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_restricate_rss_feed_callback() {
	if ( is_user_logged_in() ) {
		return;
	}
	if ( true === bp_enable_private_rss_feeds() ) {
		bb_restricate_rss_feed();
	}
}
add_action( 'init', 'bb_restricate_rss_feed_callback', 10 );

/**
 * Function will remove REST APIs endpoint.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
 *                                                                   Usually a WP_REST_Response or WP_Error.
 * @param array                                            $handler  Route handler used for the request.
 * @param WP_REST_Request                                  $request  Request used to generate the response.
 *
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
 */
function bb_restricate_rest_api_callback( $response, $handler, $request ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if (
		! is_user_logged_in() &&
		! empty( $handler['permission_callback'] ) &&
		(
			(
				function_exists( 'bbapp_is_private_app_enabled' ) && // buddyboss-app is active.
				true === bbapp_is_private_app_enabled() && // private app is enabled.
				true === bp_enable_private_rest_apis() // BB private rest api is enabled.
			) ||
			(
				! function_exists( 'bbapp_is_private_app_enabled' ) && // buddyboss-app is not active.
				true === bp_enable_private_rest_apis() // BB private rest api is enabled.
			)
		)
	) {
		return bb_restricate_rest_api( $response, $handler, $request );
	}

	return $response;
}

add_filter( 'rest_request_before_callbacks', 'bb_restricate_rest_api_callback', 100, 3 );

/**
 * Function will run after plugin successfully update.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param object $upgrader_object WP_Upgrader instance.
 * @param array  $options         Array of bulk item update data.
 */
function bb_plugin_upgrade_function_callback( $upgrader_object, $options ) {
	$show_display_popup = false;
	// The path to our plugin's main file.
	$our_plugin = 'buddyboss-platform/bp-loader.php';
	if ( ! empty( $options ) && 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
		foreach ( $options['plugins'] as $plugin ) {
			if ( ! empty( $plugin ) && $plugin === $our_plugin ) {
				update_option( '_bb_is_update', $show_display_popup );
				flush_rewrite_rules(); // Flush rewrite rules when update the Buddyboss platform plugin.
			}
		}
	}
}
add_action( 'upgrader_process_complete', 'bb_plugin_upgrade_function_callback', 10, 2 );

/**
 * Render registered notifications into frontend.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_render_notification_settings() {
	$registered_notification = bb_register_notification_preferences();

	bb_render_enable_notification_options();

	bb_render_manual_notification();

	if ( ! empty( $registered_notification ) ) {
		foreach ( $registered_notification as $group => $data ) {
			bb_render_notification( $group );
		}
	}
}

/**
 * Clear interval time when heartbeat enable/disabled.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $old_value Previous saved value.
 * @param int $value     Newly updated value.
 *
 * @return void
 */
function bb_clear_interval_on_enable_disabled_heartbeat( $old_value, $value ) {
	if ( $old_value !== $value ) {
		bp_delete_option( 'bb_presence_interval' );
	}
}

add_action( 'update_option_bp_wp_heartbeat_disabled', 'bb_clear_interval_on_enable_disabled_heartbeat', 10, 2 );

/**
 * Redirect old forums subscriptions links to new one.
 *
 * @since BuddyBoss 2.2.6
 *
 * @return void
 */
function bb_forums_subscriptions_redirect() {

	if ( bp_is_my_profile() && function_exists( 'bbp_get_user_subscriptions_slug' ) ) {
		global $wp;
		$url = explode( '/', untrailingslashit( $wp->request ) );
		if (
			! empty( $url ) &&
			end( $url ) === bbp_get_user_subscriptions_slug() &&
			isset( buddypress()->forums ) &&
			! empty( buddypress()->forums->id ) &&
			buddypress()->forums->id === $url[ count( $url ) - 2 ] &&
			bp_is_active( 'settings' ) &&
			! empty( bb_get_subscriptions_types() )
		) {
			$user_domain   = bp_loggedin_user_domain();
			$slug          = bp_get_settings_slug();
			$settings_link = trailingslashit( $user_domain . $slug ) . 'notifications/subscriptions';
			if ( wp_safe_redirect( $settings_link ) ) {
				exit;
			}
		}
	}
}

add_action( 'bp_ready', 'bb_forums_subscriptions_redirect' );

/**
 * Load Presence API mu plugin.
 *
 * @since BuddyBoss 2.3.1
 */
function bb_load_presence_api_mu() {
	if ( class_exists( 'BB_Presence' ) ) {
		BB_Presence::bb_load_presence_api_mu_plugin();
	}
}

add_action( 'bp_admin_init', 'bb_load_presence_api_mu' );

/**
 * Function to check server allow to load php file directly or not.
 *
 * @since BuddyBoss 2.3.1
 */
function bb_check_presence_load_directly() {
	if ( class_exists( 'BB_Presence' ) ) {
		BB_Presence::bb_check_native_presence_load_directly();
	}
}

add_action( 'bp_init', 'bb_check_presence_load_directly' );

/**
 * Register the post comment reply notifications.
 *
 * @since BuddyBoss 2.3.50
 */
function bb_load_post_notifications() {
	if ( class_exists( 'BB_Post_Notification' ) ) {
		BB_Post_Notification::instance();
	}
}

/**
 * Send new post comment reply notification.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param int        $comment_id       The comment ID.
 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
 * @param array      $commentdata      Comment data.
 */
function bb_post_new_comment_reply_notification( $comment_id, $comment_approved, $commentdata ) {

	// Don't send notification if the comment hasn't been approved.
	if ( empty( $comment_approved ) ) {
		return false;
	}

	// Don't record activity if the comment has already been marked as spam.
	if ( 'spam' === $comment_approved ) {
		return false;
	}

	if ( empty( $commentdata['comment_parent'] ) ) {
		return false;
	}

	// Get the post.
	$post = get_post( $commentdata['comment_post_ID'] );
	if (
		! is_a( $post, 'WP_Post' ) ||
		( isset( $post->post_type ) && 'post' !== $post->post_type ) // Allow only for the WP default post only.
	) {
		return false;
	}

	// Get the user by the comment author email.
	$comment_author = get_user_by( 'email', $commentdata['comment_author_email'] );
	$parent_comment = get_comment( $commentdata['comment_parent'] );

	if ( empty( $parent_comment->user_id ) ) {
		return false;
	}

	if ( ! empty( $comment_author ) && $comment_author->ID === (int) $parent_comment->user_id ) {
		return false;
	}

	// Check for moderation.
	if (
		! empty( $parent_comment ) &&
		! empty( $comment_author ) &&
		true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $comment_author->ID, $parent_comment->user_id )
	) {
		return false;
	}

	$comment_author_id        = ! empty( $comment_author ) ? $comment_author->ID : $commentdata['user_id'];
	$comment_content          = $commentdata['comment_content'];
	$comment_author_name      = ! empty( $comment_author ) ? bp_core_get_user_displayname( $comment_author->ID ) : $commentdata['comment_author'];
	$comment_link             = get_comment_link( $comment_id );
	$parent_comment_author_id = (int) $parent_comment->user_id;

	// Send an email if the user hasn't opted-out.
	if ( ! empty( $parent_comment_author_id ) ) {

		$usernames = bp_find_mentions_by_at_sign( array(), $comment_content );
		$send_mail = true;

		if ( ! empty( $usernames ) && array_key_exists( $parent_comment_author_id, $usernames ) ) {
			if ( true === bb_is_notification_enabled( $parent_comment_author_id, 'bb_new_mention' ) ) {
				$send_mail = false;
			}
		}

		if ( true === $send_mail && true === bb_is_notification_enabled( $parent_comment_author_id, 'bb_posts_new_comment_reply' ) ) {

			$unsubscribe_args = array(
				'user_id'           => $parent_comment_author_id,
				'notification_type' => 'new-comment-reply',
			);

			$args = array(
				'tokens' => array(
					'comment.id'     => $comment_id,
					'commenter.id'   => $comment_author_id,
					'commenter.name' => $comment_author_name,
					'comment_reply'  => wp_strip_all_tags( $comment_content ),
					'comment.url'    => esc_url( $comment_link ),
					'unsubscribe'    => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'new-comment-reply', $parent_comment_author_id, $args );

		}

		update_comment_meta( $comment_id, 'bb_comment_notified_after_approved', 1 );

		/**
		 * Fires at the point that notifications should be sent for new comment reply.
		 *
		 * @since BuddyPress 2.3.50
		 *
		 * @param int   $comment_id   ID for the newly received comment reply.
		 * @param int   $commenter_id ID of the user who made the comment reply.
		 * @param array $commentdata  Comment reply related data.
		 */
		do_action( 'bb_post_new_comment_reply_notification', $comment_id, $comment_author_id, $commentdata );
	}
}

/**
 * Check post comment status on transition_comment_status hook and send the new comment reply notification if not sent already.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param string     $new_status New comment status.
 * @param string     $old_status Previous comment status.
 * @param WP_Comment $comment Comment data.
 */
function bb_post_comment_on_status_change( $new_status, $old_status, $comment ) {

	$notification_already_sent = get_comment_meta( $comment->comment_ID, 'bb_comment_notified_after_approved', true );
	if (
		empty( $notification_already_sent ) &&
		'approved' === $new_status &&
		in_array( $old_status, array( 'unapproved', 'spam' ), true )
	) {
		$commentdata = get_object_vars( $comment );
		bb_post_new_comment_reply_notification( $commentdata['comment_ID'], $commentdata['comment_approved'], $commentdata );
	}
}

/**
 * Call new blog post comment reply notification in case of REST API.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param string     $comment_ID Comment id.
 * @param WP_Comment $comment    Comment data.
 */
function bb_post_new_comment_reply_notification_helper( $comment_ID, $comment ) {
	$commentdata               = get_object_vars( $comment );
	$notification_already_sent = get_comment_meta( $comment_ID, 'bb_comment_notified_after_approved', true );
	if ( empty( $notification_already_sent ) && 1 === (int) $commentdata['comment_approved'] ) {
		bb_post_new_comment_reply_notification( $commentdata['comment_ID'], $commentdata['comment_approved'], $commentdata );
	}
}

/**
 * Mark blog comment notifications as read.
 *
 * @since BuddyBoss 2.3.50
 */
function bb_core_read_blog_comment_notification() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$comment_id = 0;
	// For replies to a parent update.
	if ( ! empty( $_GET['cid'] ) ) {
		$comment_id = (int) $_GET['cid'];
	}

	// Mark individual notification as read.
	if ( ! empty( $comment_id ) ) {
		$updated = BP_Notifications_Notification::update(
			array(
				'is_new' => false,
			),
			array(
				'user_id' => bp_loggedin_user_id(),
				'id'      => $comment_id,
			)
		);

		if ( 1 === $updated ) {
			$notifications_data = bp_notifications_get_notification( $comment_id );
			if ( isset( $notifications_data->item_id ) ) {
				BP_Notifications_Notification::update(
					array(
						'is_new' => false,
					),
					array(
						'user_id'        => bp_loggedin_user_id(),
						'item_id'        => $notifications_data->item_id,
						'component_name' => $notifications_data->component_name,
					)
				);

				if ( bp_is_active( 'activity' ) ) {
					$activity_id = get_comment_meta( $notifications_data->item_id, 'bp_activity_comment_id', true );
					if ( ! empty( $activity_id ) ) {
						BP_Notifications_Notification::update(
							array(
								'is_new' => false,
							),
							array(
								'user_id'        => bp_loggedin_user_id(),
								'item_id'        => $activity_id,
								'component_name' => 'activity',
							)
						);
					}
				}
			}
		}
	}
}

add_action( 'template_redirect', 'bb_core_read_blog_comment_notification', 99 );

/**
 * Fire an email when someone mentioned users into the blog post comment and post published.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int  $comment_id  ID of the comment.
 * @param bool $is_approved Whether the comment is approved or not.
 */
function bb_mention_post_type_comment( $comment_id = 0, $is_approved = true ) {

	if ( ! function_exists( 'bp_find_mentions_by_at_sign' ) ) {
		return;
	}

	// Get the users comment.
	$post_type_comment = get_comment( $comment_id );

	// Don't record activity if the comment hasn't been approved.
	if ( empty( $is_approved ) ) {
		return false;
	}

	// Don't record activity if no email address has been included.
	if ( empty( $post_type_comment->comment_author_email ) ) {
		return false;
	}

	// Don't record activity if the comment has already been marked as spam.
	if ( 'spam' === $is_approved ) {
		return false;
	}

	// Get the user by the comment author email.
	$user = get_user_by( 'email', $post_type_comment->comment_author_email );

	// If user isn't registered, don't record activity.
	if ( empty( $user ) ) {
		return false;
	}

	// Get the user_id.
	$comment_user_id = (int) $user->ID;

	// Get the post.
	$post = get_post( $post_type_comment->comment_post_ID );

	if ( ! is_a( $post, 'WP_Post' ) ) {
		return false;
	}

	if (
		! empty( $post->post_type ) &&
		bp_is_active( 'activity' ) &&
		(
			bp_is_post_type_feed_enable( $post->post_type ) ||
			(
				bp_is_post_type_feed_enable( $post->post_type ) &&
				! bb_is_post_type_feed_comment_enable( $post->post_type )
			)
		)
	) {
		return;
	}

	// Try to find mentions.
	$usernames = bp_find_mentions_by_at_sign( array(), $post_type_comment->comment_content );

	if ( empty( $usernames ) ) {
		return;
	}

	// Replace @mention text with userlinks.
	foreach ( (array) $usernames as $user_id => $username ) {
		$pattern = '/(?<=[^A-Za-z0-9\_\/\.\-\*\+\=\%\$\#\?]|^)@' . preg_quote( $username, '/' ) . '\b(?!\/)/';
		$post_type_comment->comment_content = preg_replace( $pattern, "<a class='bp-suggestions-mention' href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $post_type_comment->comment_content );
	}

	// Send @mentions and setup BP notifications.
	foreach ( (array) $usernames as $user_id => $username ) {

		// Check the sender is blocked by recipient or not.
		if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, $comment_user_id ) ) {
			continue;
		}

		// User Mentions email.
		if (
			(
				! bb_enabled_legacy_email_preference() &&
				true === bb_is_notification_enabled( $user_id, 'bb_new_mention' )
			) ||
			(
				bb_enabled_legacy_email_preference() &&
				true === bb_is_notification_enabled( $user_id, 'notification_activity_new_mention' )
			)
		) {

			// Poster name.
			$reply_author_name = bp_core_get_user_displayname( $comment_user_id );
			$author_id         = $comment_user_id;

			/** Mail */
			// Strip tags from text and setup mail data.
			$reply_content = apply_filters( 'comment_text', $post_type_comment->comment_content, $post_type_comment, array() );
			$reply_url     = get_comment_link( $post_type_comment );
			$title_text    = get_the_title( $post );

			$email_type = 'new-mention';

			$unsubscribe_args = array(
				'user_id'           => $user_id,
				'notification_type' => $email_type,
			);

			$notification_type_html = esc_html__( 'comment', 'buddyboss' );

			$args = array(
				'tokens' => array(
					'usermessage'       => wp_strip_all_tags( $reply_content ),
					'mentioned.url'     => $reply_url,
					'poster.name'       => $reply_author_name,
					'receiver-user.id'  => $user_id,
					'unsubscribe'       => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					'mentioned.type'    => $notification_type_html,
					'mentioned.content' => $reply_content,
					'author_id'         => $author_id,
					'reply_text'        => esc_html__( 'View Comment', 'buddyboss' ),
					'title_text'        => $title_text,
				),
			);

			bp_send_email( $email_type, $user_id, $args );
		}

		if ( bp_is_active( 'notifications' ) ) {
			// Specify the Notification type.
			$component_action = 'bb_new_mention';
			$component_name   = 'core';

			add_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $comment_id,
					'secondary_item_id' => $comment_user_id,
					'component_name'    => $component_name,
					'component_action'  => $component_action,
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
			remove_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
		}
	}

}

add_action( 'comment_post', 'bb_mention_post_type_comment', 10, 2 );

/**
 * Fired and mention email when comment has been approved.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param string     $new_status New comment status.
 * @param string     $old_status Previous comment status.
 * @param WP_Comment $comment Comment data.
 *
 * @return void
 */
function bb_mention_post_type_comment_status_change( $new_status, $old_status, $comment ) {

	if (
		'approved' === $new_status &&
		in_array( $old_status, array( 'unapproved', 'spam' ), true )
	) {
		$commentdata = get_object_vars( $comment );
		bb_mention_post_type_comment( $commentdata['comment_ID'], $commentdata['comment_approved'] );
	}
}

add_action( 'transition_comment_status', 'bb_mention_post_type_comment_status_change', 10, 3 );

/**
 * Registered component name for the core.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param array $component_names Array of registered component names.
 *
 * @return mixed
 */
function bb_core_registered_notification_components( $component_names ) {
	if ( ! in_array( 'core', $component_names, true ) ) {
		$component_names[] = 'core';
	}

	return $component_names;
}

add_action( 'bp_notifications_get_registered_components', 'bb_core_registered_notification_components', 20, 1 );
