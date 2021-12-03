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

// Load the admin.
if ( is_admin() ) {
	add_action( 'bp_loaded', 'bp_admin' );
}

// Activation redirect.
add_action( 'bp_activation', 'bp_add_activation_redirect' );

// Add Platform plugin updater code.
if ( is_admin() ) {
	add_action( 'bp_init', 'bp_platform_plugin_updater' );
}

// Email unsubscribe.
add_action( 'bp_get_request_unsubscribe', 'bp_email_unsubscribe_handler' );

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
