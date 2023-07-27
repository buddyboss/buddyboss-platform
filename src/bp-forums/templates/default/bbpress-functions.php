<?php

/**
 * Functions of Forums' Default theme
 *
 * @package BuddyBoss\BBP_Theme_Compat
 * @since   bbPress (r3732)
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Theme Setup */

if ( ! class_exists( 'BBP_Default' ) ) :

	/**
	 * Loads Forums Default Theme functionality
	 *
	 * This is not a real theme by WordPress standards, and is instead used as the
	 * fallback for any WordPress theme that does not have Forums templates in it.
	 *
	 * To make your custom theme Forums compatible and customize the templates, you
	 * can copy these files into your theme without needing to merge anything
	 * together; Forums should safely handle the rest.
	 *
	 * @see   BBP_Theme_Compat() for more.
	 *
	 * @since bbPress (r3732)
	 */
	class BBP_Default extends BBP_Theme_Compat {

		/** Functions *************************************************************/

		/**
		 * The main Forums (Default) Loader
		 *
		 * @since bbPress (r3732)
		 *
		 * @uses  BBP_Default::setup_globals()
		 * @uses  BBP_Default::setup_actions()
		 */
		public function __construct( $properties = array() ) {

			parent::__construct(
				bbp_parse_args(
					$properties,
					array(
						'id'      => 'default',
						'name'    => __( 'Forums Default', 'buddyboss' ),
						'version' => bbp_get_version(),
						'dir'     => trailingslashit( bbpress()->themes_dir . 'default' ),
						'url'     => trailingslashit( bbpress()->themes_url . 'default' ),
					),
					'default_theme'
				)
			);

			$this->setup_actions();
		}

		/**
		 * Setup the theme hooks
		 *
		 * @since  bbPress (r3732)
		 * @access private
		 *
		 * @uses   add_filter() To add various filters
		 * @uses   add_action() To add various actions
		 */
		private function setup_actions() {

			/** Scripts */

			add_action( 'bbp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Enqueue theme JS.
			add_action( 'bbp_enqueue_scripts', array( $this, 'localize_topic_script' ) ); // Enqueue theme script localization.
			add_action( 'bbp_enqueue_scripts', array( $this, 'media_localize_script' ) ); // Enqueue media script localization.
			add_action( 'wp_footer', array( $this, 'enqueue_scripts' ) ); // Enqueue theme JS.
			add_action( 'wp_footer', array( $this, 'localize_topic_script' ) ); // Enqueue theme script localization.
			add_action( 'wp_footer', array( $this, 'media_localize_script' ) ); // Enqueue media script localization.
			add_action( 'bbp_ajax_favorite', array( $this, 'ajax_favorite' ) ); // Handles the topic ajax favorite/unfavorite.
			add_action( 'bbp_ajax_subscription', array( $this, 'ajax_subscription' ) ); // Handles the topic ajax subscribe/unsubscribe.
			add_action( 'bbp_ajax_forum_subscription', array( $this, 'ajax_forum_subscription' ) ); // Handles the forum ajax subscribe/unsubscribe.
			add_action( 'bbp_enqueue_scripts', array( $this, 'mentions_script' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mentions_script' ) );

			/** Template Wrappers */

			add_action( 'bbp_before_main_content', array( $this, 'before_main_content' ) ); // Top wrapper HTML.
			add_action( 'bbp_after_main_content', array( $this, 'after_main_content' ) ); // Bottom wrapper HTML.

			/** Override */

			do_action_ref_array( 'bbp_theme_compat_actions', array( &$this ) );
		}

		/**
		 * Inserts HTML at the top of the main content area to be compatible with
		 * the Twenty Twelve theme.
		 *
		 * @since bbPress (r3732)
		 */
		public function before_main_content() {
			?>

			<div id="bbp-container">
				<div id="bbp-content" role="main">

			<?php
		}

		/**
		 * Inserts HTML at the bottom of the main content area to be compatible with
		 * the Twenty Twelve theme.
		 *
		 * @since bbPress (r3732)
		 */
		public function after_main_content() {
			?>

				</div><!-- #bbp-content -->
			</div><!-- #bbp-container -->

			<?php
		}

		/**
		 * Enqueue the required Javascript files
		 *
		 * @since bbPress (r3732)
		 *
		 * @uses  bbp_is_single_forum() To check if it's the forum page
		 * @uses  bbp_is_single_topic() To check if it's the topic page
		 * @uses  bbp_thread_replies() To check if threaded replies are enabled
		 * @uses  bbp_is_single_user_edit() To check if it's the profile edit page
		 * @uses  wp_enqueue_script() To enqueue the scripts
		 */
		public function enqueue_scripts() {

			if ( ! is_bbpress() ) {

				if ( ! bp_is_active( 'groups' ) ) {
					return false;
				}

				// Only filter if group forums are active.
			   if ( ! bbp_is_group_forums_active() ) {
					return false;
			   }

			   // Only filter for single group forum topics.
			   if ( ! bp_is_group_single() && ! bp_is_group_forum_topic() && ! bp_is_group_forum_topic_edit() && ! bbp_is_reply_edit() ) {
					return false;
			   }

			}

			// Setup scripts array.
			$scripts = array();

			// Always pull in jQuery for TinyMCE shortcode usage.
			if ( bbp_use_wp_editor() ) {
				$scripts['bbpress-editor'] = array(
					'file'         => 'js/editor.js',
					'dependencies' => array( 'jquery' ),
				);
				wp_enqueue_script( 'bp-medium-editor' );
				wp_enqueue_style( 'bp-medium-editor' );
				wp_enqueue_style( 'bp-medium-editor-beagle' );
			}

			wp_enqueue_script( 'bp-select2' );
			wp_enqueue_style( 'bp-select2' );

			// Forum-specific scripts.
			if ( bbp_is_single_forum() ) {
				$scripts['bbpress-forum'] = array(
					'file'         => 'js/forum.js',
					'dependencies' => array( 'jquery' ),
				);
			}

			// Topic-specific scripts.
			if ( bbp_is_single_topic() ) {

				// Topic favorite/unsubscribe.
				$scripts['bbpress-topic'] = array(
					'file'         => 'js/topic.js',
					'dependencies' => array( 'jquery' ),
				);

				// Hierarchical replies.
				if ( bbp_thread_replies() ) {
					$scripts['bbpress-reply'] = array(
						'file'         => 'js/reply.js',
						'dependencies' => array( 'jquery' ),
					);
				}
			}

			if ( bbp_is_single_forum() || bbp_is_single_topic() ) {
				$dependencies = array( 'jquery', 'bp-nouveau' );
				if ( bp_is_active( 'media' ) ) {
					$dependencies[] = 'bp-nouveau-media';
				}

				$scripts['bb-topic-reply-draft'] = array(
					'file'         => 'js/topic-reply-draft.js',
					'dependencies' => $dependencies,
				);
			}

			// User Profile edit.
			if ( bbp_is_single_user_edit() ) {
				$scripts['bbpress-user'] = array(
					'file'         => 'js/user.js',
					'dependencies' => array( 'user-query' ),
				);
			}

			$scripts['bbpress-common'] = array(
				'file'         => 'js/common.js',
				'dependencies' => array( 'jquery' ),
			);

			// Filter the scripts.
			$scripts = apply_filters( 'bbp_default_scripts', $scripts );

			// Enqueue the scripts.
			foreach ( $scripts as $handle => $attributes ) {
				bbp_enqueue_script( $handle, $attributes['file'], $attributes['dependencies'], bp_get_version(), 'screen' );
			}

			$no_load_topic = true;
			if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) ) {
				$no_load_topic = false;
			}

			$common_array = array(
				'loading_text' => __( 'Loading', 'buddyboss' ),
				'ajax_url'     => bp_core_ajax_url(),
				'nonce'        => wp_create_nonce( 'search_tag' ),
				'load'         => $no_load_topic,
				'tag_text'     => __( 'Add Tags:', 'buddyboss' ),
			);

			wp_localize_script( 'bbpress-common', 'bbpCommonJsData', $common_array );

			if ( bp_is_active( 'media' ) ) {

				$gif = false;
				if ( bp_is_forums_gif_support_enabled() || bp_is_groups_gif_support_enabled() ) {
					wp_enqueue_script( 'giphy' );
					$gif = true;
				}

				$emoji = false;
				if ( bp_is_forums_emoji_support_enabled() || bp_is_groups_emoji_support_enabled() ) {
					wp_enqueue_script( 'emojionearea' );
					wp_enqueue_style( 'emojionearea' );
					$emoji = true;
				}

				if ( bp_is_forums_media_support_enabled() || $gif || $emoji ) {
					wp_enqueue_script( 'bp-media-dropzone' );
					wp_enqueue_script( 'bp-nouveau-media' );
					wp_enqueue_script( 'isInViewport' );
					wp_enqueue_script( 'bp-exif' );
				}
			}

			if ( bbp_use_wp_editor() ) {
				wp_localize_script(
					'bbpress-editor',
					'bbpEditorJsStrs',
					array(
						'description' => __( 'Explain what the forum is about', 'buddyboss' ),
						'type_reply'  => __( 'Type your reply here', 'buddyboss' ),
						'type_topic'  => __( 'Type your discussion content here', 'buddyboss' ),
					)
				);
			}
		}

		/**
		 * Enqueue @mentions JS.
		 *
		 * @since BuddyBoss 1.2.8
		 */
		public function mentions_script() {

			// Special handling for New/Edit screens in wp-admin.
			if ( is_admin() ) {
				if (
					! get_current_screen() ||
					! in_array( get_current_screen()->base, array( 'page', 'post' ), true ) ||
					! post_type_supports( get_current_screen()->post_type, 'editor' ) ) {
					return;
				}
			}

			$min = bp_core_get_minified_asset_suffix();

			if ( ! wp_script_is( 'bp-mentions' ) ) {
				wp_enqueue_script(
					'bp-mentions',
					buddypress()->plugin_url . "bp-core/js/mentions{$min}.js",
					array(
						'jquery',
						'jquery-atwho',
					),
					bp_get_version(),
					true
				);
				wp_enqueue_style( 'bp-mentions-css', buddypress()->plugin_url . "bp-core/css/mentions{$min}.css", array(), bp_get_version() );

				wp_style_add_data( 'bp-mentions-css', 'rtl', true );
				if ( $min ) {
					wp_style_add_data( 'bp-mentions-css', 'suffix', $min );
				}

				wp_localize_script( 'bp-mentions', 'BP_Mentions_Options', bp_at_mention_default_options() );

				/**
				 * Fires at the end of the Mentions script.
				 *
				 * This is the hook where BP components can add their own prefetched results
				 * friends to the page for quicker @mentions lookups.
				 *
				 * @since BuddyBoss 1.2.8
				 */
				do_action( 'bbp_forums_mentions_prime_results' );
			}
		}

		/**
		 * Localize scripts for Media component for forums
		 *
		 * @since BuddyBoss 1.1.5
		 */
		public function media_localize_script() {

			if ( ! is_bbpress() ) {
				return false;
			}

			$params = array();

			if ( bp_is_active( 'media' ) ) {

				// check if topic edit.
				if ( bbp_is_topic_edit() ) {
					$params['bbp_is_topic_edit'] = true;

					$document_ids = get_post_meta( bbp_get_topic_id(), 'bp_document_ids', true );

					if ( ! empty( $document_ids ) && bp_has_document(
						array(
							'include'  => $document_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['topic_edit_document'] = array();
						$index                         = 0;
						while ( bp_document() ) {
							bp_the_document();

							$size                            = filesize( get_attached_file( bp_get_document_attachment_id() ) );
							$params['topic_edit_document'][] = array(
								'id'            => bp_get_document_id(),
								'attachment_id' => bp_get_document_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_document_attachment_id() ) ),
								'type'          => 'document',
								'thumb'         => '',
								'url'           => wp_get_attachment_url( bp_get_document_attachment_id() ),
								'size'          => $size,
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$video_ids = get_post_meta( bbp_get_topic_id(), 'bp_video_ids', true );

					if ( ! empty( $video_ids ) && bp_has_video(
						array(
							'include'  => $video_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['topic_edit_video'] = array();
						$index                      = 0;
						while ( bp_video() ) {
							bp_the_video();

							$get_existing = get_post_meta( bp_get_video_attachment_id(), 'bp_video_preview_thumbnail_id', true );
							$thumb        = '';
							if ( $get_existing ) {
								$file  = get_attached_file( $get_existing );
								$type  = pathinfo( $file, PATHINFO_EXTENSION );
								$data  = file_get_contents( $file ); // phpcs:ignore
								$thumb = 'data:image/' . $type . ';base64,' . base64_encode( $data ); // phpcs:ignore
							}

							$size                         = filesize( get_attached_file( bp_get_video_attachment_id() ) );
							$params['topic_edit_video'][] = array(
								'id'            => bp_get_video_id(),
								'attachment_id' => bp_get_video_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_video_attachment_id() ) ),
								'type'          => 'video',
								'thumb'         => $thumb,
								'url'           => wp_get_attachment_url( bp_get_video_attachment_id() ),
								'size'          => $size,
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$media_ids = get_post_meta( bbp_get_topic_id(), 'bp_media_ids', true );

					if ( ! empty( $media_ids ) && bp_has_media(
						array(
							'include'  => $media_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['topic_edit_media'] = array();
						$index                      = 0;
						while ( bp_media() ) {
							bp_the_media();

							$params['topic_edit_media'][] = array(
								'id'            => bp_get_media_id(),
								'attachment_id' => bp_get_media_attachment_id(),
								'name'          => bp_get_media_title(),
								'thumb'         => bp_get_media_attachment_image_thumbnail(),
								'url'           => bp_get_media_attachment_image(),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$gif_data = get_post_meta( bbp_get_topic_id(), '_gif_data', true );

					if ( ! empty( $gif_data ) ) {
						$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
						$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

						$params['topic_edit_gif_data'] = array(
							'preview_url'  => $preview_url,
							'video_url'    => $video_url,
							'gif_raw_data' => get_post_meta( bbp_get_topic_id(), '_gif_raw_data', true ),
						);
					}
				}

				// check if reply edit.
				if ( bbp_is_reply_edit() ) {
					$params['bbp_is_reply_edit'] = true;

					$document_ids = get_post_meta( bbp_get_reply_id(), 'bp_document_ids', true );

					if ( ! empty( $document_ids ) && bp_has_document(
						array(
							'include'  => $document_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['reply_edit_document'] = array();
						$index                         = 0;
						while ( bp_document() ) {
							bp_the_document();

							$size                            = filesize( get_attached_file( bp_get_document_attachment_id() ) );
							$params['reply_edit_document'][] = array(
								'id'            => bp_get_document_id(),
								'attachment_id' => bp_get_document_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_document_attachment_id() ) ),
								'type'          => 'document',
								'thumb'         => '',
								'size'          => $size,
								'url'           => wp_get_attachment_url( bp_get_document_attachment_id() ),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$video_ids = get_post_meta( bbp_get_reply_id(), 'bp_video_ids', true );

					if ( ! empty( $video_ids ) && bp_has_video(
						array(
							'include'  => $video_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['reply_edit_video'] = array();
						$index                      = 0;
						while ( bp_video() ) {
							bp_the_video();

							$get_existing = get_post_meta( bp_get_video_attachment_id(), 'bp_video_preview_thumbnail_id', true );
							$thumb        = '';
							if ( $get_existing ) {
								$file  = get_attached_file( $get_existing );
								$type  = pathinfo( $file, PATHINFO_EXTENSION );
								$data  = file_get_contents( $file ); // phpcs:ignore
								$thumb = 'data:image/' . $type . ';base64,' . base64_encode( $data ); // phpcs:ignore
							}

							$size                         = filesize( get_attached_file( bp_get_video_attachment_id() ) );
							$params['reply_edit_video'][] = array(
								'id'            => bp_get_video_id(),
								'attachment_id' => bp_get_video_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_video_attachment_id() ) ),
								'type'          => 'video',
								'thumb'         => $thumb,
								'size'          => $size,
								'url'           => wp_get_attachment_url( bp_get_video_attachment_id() ),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$media_ids = get_post_meta( bbp_get_reply_id(), 'bp_media_ids', true );

					if ( ! empty( $media_ids ) && bp_has_media(
						array(
							'include'  => $media_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['reply_edit_media'] = array();
						$index                      = 0;
						while ( bp_media() ) {
							bp_the_media();

							$params['reply_edit_media'][] = array(
								'id'            => bp_get_media_id(),
								'attachment_id' => bp_get_media_attachment_id(),
								'name'          => bp_get_media_title(),
								'thumb'         => bp_get_media_attachment_image_thumbnail(),
								'url'           => bp_get_media_attachment_image(),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$gif_data = get_post_meta( bbp_get_reply_id(), '_gif_data', true );

					if ( ! empty( $gif_data ) ) {
						$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
						$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

						$params['reply_edit_gif_data'] = array(
							'preview_url'  => $preview_url,
							'video_url'    => $video_url,
							'gif_raw_data' => get_post_meta( bbp_get_reply_id(), '_gif_raw_data', true ),
						);
					}
				}

				// check if forum edit.
				if ( bbp_is_forum_edit() ) {
					$params['bbp_is_forum_edit'] = true;

					$document_ids = get_post_meta( bbp_get_forum_id(), 'bp_document_ids', true );

					if ( ! empty( $document_ids ) && bp_has_document(
						array(
							'include'  => $document_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['forum_edit_document'] = array();
						$index                         = 0;
						while ( bp_document() ) {
							bp_the_document();

							$size                            = filesize( get_attached_file( bp_get_document_attachment_id() ) );
							$params['forum_edit_document'][] = array(
								'id'            => bp_get_document_id(),
								'attachment_id' => bp_get_document_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_document_attachment_id() ) ),
								'type'          => 'document',
								'thumb'         => '',
								'size'          => $size,
								'url'           => wp_get_attachment_url( bp_get_document_attachment_id() ),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$video_ids = get_post_meta( bbp_get_forum_id(), 'bp_video_ids', true );

					if ( ! empty( $video_ids ) && bp_has_video(
						array(
							'include'  => $video_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['forum_edit_video'] = array();
						$index                      = 0;
						while ( bp_video() ) {
							bp_the_video();

							$get_existing = get_post_meta( bp_get_video_attachment_id(), 'bp_video_preview_thumbnail_id', true );
							$thumb        = '';
							if ( $get_existing ) {
								$file  = get_attached_file( $get_existing );
								$type  = pathinfo( $file, PATHINFO_EXTENSION );
								$data  = file_get_contents( $file ); // phpcs:ignore
								$thumb = 'data:image/' . $type . ';base64,' . base64_encode( $data ); // phpcs:ignore
							}

							$size                         = filesize( get_attached_file( bp_get_video_attachment_id() ) );
							$params['forum_edit_video'][] = array(
								'id'            => bp_get_video_id(),
								'attachment_id' => bp_get_video_attachment_id(),
								'name'          => basename( get_attached_file( bp_get_video_attachment_id() ) ),
								'type'          => 'video',
								'thumb'         => $thumb,
								'size'          => $size,
								'url'           => wp_get_attachment_url( bp_get_video_attachment_id() ),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$media_ids = get_post_meta( bbp_get_forum_id(), 'bp_media_ids', true );

					if ( ! empty( $media_ids ) && bp_has_media(
						array(
							'include'  => $media_ids,
							'order_by' => 'menu_order',
							'sort'     => 'ASC',
						)
					) ) {
						$params['forum_edit_media'] = array();
						$index                      = 0;
						while ( bp_media() ) {
							bp_the_media();

							$params['forum_edit_media'][] = array(
								'id'            => bp_get_media_id(),
								'attachment_id' => bp_get_media_attachment_id(),
								'name'          => bp_get_media_title(),
								'thumb'         => bp_get_media_attachment_image_thumbnail(),
								'url'           => bp_get_media_attachment_image(),
								'menu_order'    => $index,
							);
							$index ++;
						}
					}

					$gif_data = get_post_meta( bbp_get_forum_id(), '_gif_data', true );

					if ( ! empty( $gif_data ) ) {
						$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
						$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

						$params['forum_edit_gif_data'] = array(
							'preview_url'  => $preview_url,
							'video_url'    => $video_url,
							'gif_raw_data' => get_post_meta( bbp_get_forum_id(), '_gif_raw_data', true ),
						);
					}
				}
			}

			$result['media'] = $params;

			/**
			 * Filters core JavaScript strings for internationalization before AJAX usage.
			 *
			 * @param array $params Array of key/value pairs for AJAX usage.
			 *
			 * @since BuddyBoss 1.1.6
			 */
			wp_localize_script( 'bp-nouveau', 'BP_Forums_Nouveau', $result );
		}

		/**
		 * Load localizations for topic script
		 *
		 * These localizations require information that may not be loaded even by init.
		 *
		 * @since bbPress (r3732)
		 *
		 * @uses  bbp_is_single_forum() To check if it's the forum page
		 * @uses  bbp_is_single_topic() To check if it's the topic page
		 * @uses  is_user_logged_in() To check if user is logged in
		 * @uses  bbp_get_current_user_id() To get the current user id
		 * @uses  bbp_get_forum_id() To get the forum id
		 * @uses  bbp_get_topic_id() To get the topic id
		 * @uses  bbp_get_favorites_permalink() To get the favorites permalink
		 * @uses  bbp_is_user_favorite() To check if the topic is in user's favorites
		 * @uses  bb_is_enabled_subscription() To check if the subscriptions are active
		 * @uses  bbp_is_user_subscribed() To check if the user is subscribed to topic
		 * @uses  bbp_get_topic_permalink() To get the topic permalink
		 * @uses  wp_localize_script() To localize the script
		 */
		public function localize_topic_script() {

			// Single forum.
			if ( bbp_is_single_forum() ) {
				wp_localize_script(
					'bbpress-forum',
					'bbpForumJS',
					array(
						'bbp_ajaxurl'        => bbp_get_ajax_url(),
						'generic_ajax_error' => __( 'Something went wrong. Refresh your browser and try again.', 'buddyboss' ),
						'is_user_logged_in'  => is_user_logged_in(),
						'subs_nonce'         => wp_create_nonce( 'toggle-subscription_' . get_the_ID() ),
					)
				);

				// Single topic.
			} elseif ( bbp_is_single_topic() ) {
				wp_localize_script(
					'bbpress-topic',
					'bbpTopicJS',
					array(
						'bbp_ajaxurl'        => bbp_get_ajax_url(),
						'generic_ajax_error' => __( 'Something went wrong. Refresh your browser and try again.', 'buddyboss' ),
						'is_user_logged_in'  => is_user_logged_in(),
						'fav_nonce'          => wp_create_nonce( 'toggle-favorite_' . bbp_get_topic_id() ),
						'subs_nonce'         => wp_create_nonce( 'toggle-subscription_' . bbp_get_topic_id() ),
					)
				);
			}
		}

		/**
		 * AJAX handler to Subscribe/Unsubscribe a user from a forum
		 *
		 * @since bbPress (r5155)
		 *
		 * @uses  bb_is_enabled_subscription() To check if the subscriptions are active
		 * @uses  bbp_is_user_logged_in() To check if user is logged in
		 * @uses  bbp_get_current_user_id() To get the current user id
		 * @uses  current_user_can() To check if the current user can edit the user
		 * @uses  bbp_get_forum() To get the forum
		 * @uses  wp_verify_nonce() To verify the nonce
		 * @uses  bbp_is_user_subscribed() To check if the forum is in user's subscriptions
		 * @uses  bbp_remove_user_subscriptions() To remove the forum from user's subscriptions
		 * @uses  bbp_add_user_subscriptions() To add the forum from user's subscriptions
		 * @uses  bbp_ajax_response() To return JSON
		 */
		public function ajax_forum_subscription() {

			// Bail if subscriptions are not active.
			if ( ! bb_is_enabled_subscription( 'forum' ) ) {
				bbp_ajax_response( false, __( 'Subscriptions are no longer active.', 'buddyboss' ), 300 );
			}

			// Bail if user is not logged in.
			if ( ! is_user_logged_in() ) {
				bbp_ajax_response( false, __( 'Please login to subscribe to this forum.', 'buddyboss' ), 301 );
			}

			// Get user and forum data.
			$user_id  = bbp_get_current_user_id();
			$forum_id = intval( $_POST['id'] );

			// Bail if user cannot add favorites for this user.
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				bbp_ajax_response( false, __( 'You do not have permission to do this.', 'buddyboss' ), 302 );
			}

			// Get the forum.
			$forum = bbp_get_forum( $forum_id );

			// Bail if forum cannot be found.
			if ( empty( $forum ) ) {
				bbp_ajax_response( false, __( 'The forum could not be found.', 'buddyboss' ), 303 );
			}

			// Bail if user did not take this action.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-subscription_' . $forum->ID ) ) {
				bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'buddyboss' ), 304 );
			}

			// Take action.
			$status = bbp_is_user_subscribed( $user_id, $forum->ID ) ? bbp_remove_user_subscription( $user_id, $forum->ID ) : bbp_add_user_subscription( $user_id, $forum->ID );

			// Bail if action failed.
			if ( empty( $status ) ) {
				bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'buddyboss' ), 305 );
			}

			// Put subscription attributes in convenient array.
			$attrs = array(
				'forum_id' => $forum->ID,
				'user_id'  => $user_id,
			);

			// Action succeeded.
			bbp_ajax_response( true, bbp_get_forum_subscription_link( $attrs, $user_id, false ), 200 );
		}

		/**
		 * AJAX handler to add or remove a topic from a user's favorites
		 *
		 * @since bbPress (r3732)
		 *
		 * @uses  bbp_is_favorites_active() To check if favorites are active
		 * @uses  bbp_is_user_logged_in() To check if user is logged in
		 * @uses  bbp_get_current_user_id() To get the current user id
		 * @uses  current_user_can() To check if the current user can edit the user
		 * @uses  bbp_get_topic() To get the topic
		 * @uses  wp_verify_nonce() To verify the nonce & check the referer
		 * @uses  bbp_is_user_favorite() To check if the topic is user's favorite
		 * @uses  bbp_remove_user_favorite() To remove the topic from user's favorites
		 * @uses  bbp_add_user_favorite() To add the topic from user's favorites
		 * @uses  bbp_ajax_response() To return JSON
		 */
		public function ajax_favorite() {

			// Bail if favorites are not active.
			if ( ! bbp_is_favorites_active() ) {
				bbp_ajax_response( false, __( 'Saving discussions is no longer active.', 'buddyboss' ), 300 );
			}

			// Bail if user is not logged in.
			if ( ! is_user_logged_in() ) {
				bbp_ajax_response( false, __( 'Please login to make this discussion a favorite.', 'buddyboss' ), 301 );
			}

			// Get user and topic data.
			$user_id  = bbp_get_current_user_id();
			$topic_id = ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

			// Bail if user cannot add favorites for this user.
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				bbp_ajax_response( false, __( 'You do not have permission to do this.', 'buddyboss' ), 302 );
			}

			// Get the topic.
			$topic = bbp_get_topic( $topic_id );

			// Bail if topic cannot be found.
			if ( empty( $topic ) ) {
				bbp_ajax_response( false, __( 'The discussion could not be found.', 'buddyboss' ), 303 );
			}

			// Bail if user did not take this action.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-favorite_' . $topic->ID ) ) {
				bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'buddyboss' ), 304 );
			}

			// Take action.
			$status = bbp_is_user_favorite( $user_id, $topic->ID ) ? bbp_remove_user_favorite( $user_id, $topic->ID ) : bbp_add_user_favorite( $user_id, $topic->ID );

			// Bail if action failed.
			if ( empty( $status ) ) {
				bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'buddyboss' ), 305 );
			}

			// Put subscription attributes in convenient array.
			$attrs = array(
				'topic_id' => $topic->ID,
				'user_id'  => $user_id,
			);

			// Action succeeded.
			bbp_ajax_response( true, bbp_get_user_favorites_link( $attrs, $user_id, false ), 200 );
		}

		/**
		 * AJAX handler to Subscribe/Unsubscribe a user from a topic
		 *
		 * @since bbPress (r3732)
		 *
		 * @uses  bb_is_enabled_subscription() To check if the subscriptions are active
		 * @uses  bbp_is_user_logged_in() To check if user is logged in
		 * @uses  bbp_get_current_user_id() To get the current user id
		 * @uses  current_user_can() To check if the current user can edit the user
		 * @uses  bbp_get_topic() To get the topic
		 * @uses  wp_verify_nonce() To verify the nonce
		 * @uses  bbp_is_user_subscribed() To check if the topic is in user's subscriptions
		 * @uses  bbp_remove_user_subscriptions() To remove the topic from user's subscriptions
		 * @uses  bbp_add_user_subscriptions() To add the topic from user's subscriptions
		 * @uses  bbp_ajax_response() To return JSON
		 */
		public function ajax_subscription() {

			// Bail if subscriptions are not active.
			if ( ! bb_is_enabled_subscription( 'topic' ) ) {
				bbp_ajax_response( false, __( 'Subscriptions are no longer active.', 'buddyboss' ), 300 );
			}

			// Bail if user is not logged in.
			if ( ! is_user_logged_in() ) {
				bbp_ajax_response( false, __( 'Please login to subscribe to this discussion.', 'buddyboss' ), 301 );
			}

			// Get user and topic data.
			$user_id  = bbp_get_current_user_id();
			$topic_id = intval( $_POST['id'] );

			// Bail if user cannot add favorites for this user.
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				bbp_ajax_response( false, __( 'You do not have permission to do this.', 'buddyboss' ), 302 );
			}

			// Get the topic.
			$topic = bbp_get_topic( $topic_id );

			// Bail if topic cannot be found.
			if ( empty( $topic ) ) {
				bbp_ajax_response( false, __( 'The discussion could not be found.', 'buddyboss' ), 303 );
			}

			// Bail if user did not take this action.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-subscription_' . $topic->ID ) ) {
				bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'buddyboss' ), 304 );
			}

			// Take action.
			$status = bbp_is_user_subscribed( $user_id, $topic->ID ) ? bbp_remove_user_subscription( $user_id, $topic->ID ) : bbp_add_user_subscription( $user_id, $topic->ID );

			// Bail if action failed.
			if ( empty( $status ) ) {
				bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'buddyboss' ), 305 );
			}

			// Put subscription attributes in convenient array.
			$attrs = array(
				'topic_id' => $topic->ID,
				'user_id'  => $user_id,
			);

			// Action succeeded.
			bbp_ajax_response( true, bbp_get_user_subscribe_link( $attrs, $user_id, false ), 200 );
		}
	}

	new BBP_Default();
endif;
