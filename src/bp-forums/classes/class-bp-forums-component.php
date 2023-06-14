<?php

/**
 * Forums BuddyBoss Component Class
 *
 * Forums and BuddyBoss are designed to connect together seamlessly and
 * invisibly, and this is the hunk of code necessary to make that happen.
 *
 * The code in this BuddyBoss Extension does some pretty complicated stuff,
 * far outside the realm of the simplicity Forums is traditionally known for.
 *
 * While the rest of Forums serves as an example of how to write pretty, simple
 * code, what's in these files is pure madness. It should not be used as an
 * example of anything other than successfully juggling chainsaws and puppy-dogs.
 *
 * @package BuddyBoss\Forums
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Forums_Component' ) ) :
	/**
	 * Loads Forums Component
	 *
	 * @since bbPress (r3552)
	 */
	#[\AllowDynamicProperties]
	class BP_Forums_Component extends BP_Component {

		/**
		 * Start the forums component creation process
		 *
		 * @since bbPress (r3552)
		 */
		public function __construct() {
			parent::start(
				'forums',
				__( 'Forums', 'buddyboss' ),
				buddypress()->plugin_dir
			);
			// $this->includes();
			// $this->setup_globals();
			// $this->setup_actions();
			// $this->fully_loaded();
		}

		/**
		 * Include BuddyBoss classes and functions
		 */
		public function includes( $includes = array() ) {
			$includes = $includes ?: array();

			// Helper BuddyBoss functions
			$includes[] = 'admin.php';

			// Helper BuddyBoss functions
			$includes[] = 'functions.php';

			// Members modifications
			$includes[] = 'members.php';

			// BuddyBoss Notfications Extension functions
			if ( bp_is_active( 'notifications' ) ) {
				$includes[] = 'notifications.php';
			}

			// BuddyBoss Activity Extension class
			if ( bp_is_active( 'activity' ) ) {
				$includes[] = 'activity.php';
			}

			// BuddyBoss Group Extension class
			if ( bp_is_active( 'groups' ) ) {
				$includes[] = 'groups.php';
			}

			parent::includes( $includes );
		}

		/**
		 * Setup globals
		 *
		 * The BP_FORUMS_SLUG constant is deprecated, and only used here for
		 * backwards compatibility.
		 *
		 * @since bbPress (r3552)
		 */
		public function setup_globals( $args = array() ) {
			$bp = buddypress();

			// Define the parent forum ID
			if ( ! defined( 'BP_FORUMS_PARENT_FORUM_ID' ) ) {
				define( 'BP_FORUMS_PARENT_FORUM_ID', 1 );
			}

			// Define a slug, if necessary
			if ( ! defined( 'BP_FORUMS_SLUG' ) ) {
				define( 'BP_FORUMS_SLUG', $this->id );
			}

			// All arguments for forums component
			$args = array(
				'path'          => BP_PLUGIN_DIR,
				'slug'          => BP_FORUMS_SLUG,
				'root_slug'     => isset( $bp->pages->forums->slug ) ? $bp->pages->forums->slug : BP_FORUMS_SLUG,
				'has_directory' => false,
				'search_string' => __( 'Search Forums&hellip;', 'buddyboss' ),
			);

			parent::setup_globals( $args );
		}

		/**
		 * Setup the actions
		 *
		 * @since bbPress (r3395)
		 * @access private
		 * @uses add_filter() To add various filters
		 * @uses add_action() To add various actions
		 * @link http://bbpress.trac.wordpress.org/ticket/2176
		 */
		public function setup_actions() {

			// Setup the components
			add_action( 'bp_init', array( $this, 'setup_components' ), 7 );
			// Setup meta title.
			add_filter( 'pre_get_document_title', array( $this, 'bb_group_forums_set_title_tag' ), 10, 1 );
			if ( current_user_can( 'administrator' ) ) {
				// Admin bar menu for forum and discussion.
				add_action( 'admin_bar_menu', array( $this, 'bb_forums_admin_bar_menu' ), 100 );
			}

			parent::setup_actions();
		}

		/**
		 * Instantiate classes for BuddyBoss integration
		 *
		 * @since bbPress (r3395)
		 */
		public function setup_components() {

			// Always load the members component
			bbpress()->extend->buddypress->members = new BBP_BuddyPress_Members();

			// Create new activity class
			if ( bp_is_active( 'activity' ) ) {
				bbpress()->extend->buddypress->activity = new BBP_BuddyPress_Activity();
			}

			// Register the group extension only if groups are active
			if ( bbp_is_group_forums_active() && bp_is_active( 'groups' ) ) {

				/**
				 * need to remove this hooks before group extension because
				 * it was checking for access to that post before wp handles the post id assign
				 */
				if ( bp_is_group() ) {
					remove_action( 'bbp_template_redirect', 'bbp_check_forum_edit', 10 );
					remove_action( 'bbp_template_redirect', 'bbp_check_topic_edit', 10 );
					remove_action( 'bbp_template_redirect', 'bbp_check_reply_edit', 10 );
				}

				bp_register_group_extension( 'BBP_Forums_Group_Extension' );
			}
		}

		/**
		 * Allow the variables, actions, and filters to be modified by third party
		 * plugins and themes.
		 *
		 * @since bbPress (r3902)
		 */
		private function fully_loaded() {
			do_action_ref_array( 'bbp_buddypress_loaded', array( $this ) );
		}

		/**
		 * Setup BuddyBar navigation
		 *
		 * @since bbPress (r3552)
		 */
		public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

			// Stop if there is no user displayed or logged in
			if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
				return;
			}

			// Define local variable(s)
			$user_domain = '';

			// Add 'Forums' to the main navigation
			$main_nav = array(
				'name'                => __( 'Forums', 'buddyboss' ),
				'slug'                => $this->slug,
				'position'            => 80,
				'screen_function'     => 'bbp_member_forums_screen_topics',
				'default_subnav_slug' => bbp_get_topic_archive_slug(),
				'item_css_id'         => $this->id,
			);

			// Determine user to use
			if ( bp_displayed_user_id() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			// User link
			$forums_link = trailingslashit( $user_domain . $this->slug );

			// Topics started
			$sub_nav[] = array(
				'name'            => ( bp_loggedin_user_id() === bp_displayed_user_id() ? __( 'My Discussions', 'buddyboss' ) : __( 'Discussions', 'buddyboss' ) ),
				'slug'            => bbp_get_topic_archive_slug(),
				'parent_url'      => $forums_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bbp_member_forums_screen_topics',
				'position'        => 20,
				'item_css_id'     => 'topics',
			);

			// Replies to topics
			$sub_nav[] = array(
				'name'            => ( bp_loggedin_user_id() === bp_displayed_user_id() ? __( 'My Replies', 'buddyboss' ) : __( 'Replies', 'buddyboss' ) ),
				'slug'            => bbp_get_reply_archive_slug(),
				'parent_url'      => $forums_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bbp_member_forums_screen_replies',
				'position'        => 40,
				'item_css_id'     => 'replies',
			);

			if ( bbp_is_favorites_active() ) {
				// Favorite topics
				$sub_nav[] = array(
					'name'            => ( bp_loggedin_user_id() === bp_displayed_user_id() ? __( 'My Favorites', 'buddyboss' ) : __( 'Favorites', 'buddyboss' ) ),
					'slug'            => bbp_get_user_favorites_slug(),
					'parent_url'      => $forums_link,
					'parent_slug'     => $this->slug,
					'screen_function' => 'bbp_member_forums_screen_favorites',
					'position'        => 60,
					'item_css_id'     => 'favorites',
				);
			}

			parent::setup_nav( $main_nav, $sub_nav );
		}

		/**
		 * Set up the admin bar
		 *
		 * @since bbPress (r3552)
		 */
		public function setup_admin_bar( $wp_admin_nav = array() ) {

			// Menus for logged in user
			if ( is_user_logged_in() ) {

				// Setup the logged in user variables
				$user_domain = bp_loggedin_user_domain();
				$forums_link = trailingslashit( $user_domain . $this->slug );

				// Add the "My Account" sub menus
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => __( 'Forums', 'buddyboss' ),
					'href'   => trailingslashit( $forums_link ),
				);

				// Topics
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-topics',
					'title'  => __( 'My Discussions', 'buddyboss' ),
					'href'   => trailingslashit( $forums_link . bbp_get_topic_archive_slug() ),
				);

				// Replies
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-replies',
					'title'  => __( 'My Replies', 'buddyboss' ),
					'href'   => trailingslashit( $forums_link . bbp_get_reply_archive_slug() ),
				);

				// Favorites
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-favorites',
					'title'  => __( 'My Favorites', 'buddyboss' ),
					'href'   => trailingslashit( $forums_link . bbp_get_user_favorites_slug() ),
				);
			}

			parent::setup_admin_bar( $wp_admin_nav );
		}

		/**
		 * Sets up the title for pages and <title>
		 *
		 * @since bbPress (r3552)
		 */
		public function setup_title() {
			$bp = buddypress();

			// Adjust title based on view
			if ( bp_is_forums_component() ) {
				if ( bp_is_my_profile() ) {
					$bp->bp_options_title = __( 'Forums', 'buddyboss' );
				} elseif ( bp_is_user() ) {
					$bp->bp_options_avatar = bp_core_fetch_avatar(
						array(
							'item_id' => bp_displayed_user_id(),
							'type'    => 'thumb',
						)
					);
					$bp->bp_options_title  = bp_get_displayed_user_fullname();
				}
			}

			parent::setup_title();
		}

		/**
		 * Init the BuddyBoss REST API.
		 *
		 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
		 *
		 * @since BuddyBoss 1.3.5
		 */
		public function rest_api_init( $controllers = array() ) {

			$path = buddypress()->plugin_dir . 'bp-forums/classes/class-bp-rest-bbp-walker-reply.php';

			if ( file_exists( $path ) ) {
				require_once $path;
			}

			parent::rest_api_init(
				array(
					'BP_REST_Forums_Endpoint',
					'BP_REST_Topics_Endpoint',
					'BP_REST_Topics_Actions_Endpoint',
					'BP_REST_Reply_Endpoint',
					'BP_REST_Reply_Actions_Endpoint',
					'BB_REST_Forums_Link_Preview_Endpoint',
				)
			);
		}

		/**
		 * Setup title tag for the group forum discussion page.
		 *
		 * @since BuddyBoss 1.8.3
		 *
		 * @param string $title Page title.
		 * @return mixed
		 */
		public function bb_group_forums_set_title_tag( $title ) {

			$sep = apply_filters( 'document_title_separator', '-' );

			if ( bbp_is_group_forums_active() && bp_is_active( 'groups' ) && bp_is_group() ) {

				$group = groups_get_current_group();

				if ( ! empty( $group ) && bp_is_current_action( get_option( '_bbp_forum_slug', 'forum' ) ) && function_exists( 'bbpress' ) ) {

					if ( bp_is_action_variable( get_option( '_bbp_topic_slug', 'discussion' ), 0 ) && bp_action_variable( 1 ) ) {

						// get the topic as post.
						$topics = get_posts(
							array(
								'name'      => bp_action_variable( 1 ),
								'post_type' => bbp_get_topic_post_type(),
								'per_page'  => 1,
							)
						);

						if ( ! empty( $topics ) ) {
							return esc_html( bbp_get_topic_title( $topics[0]->ID ) ) . ' ' . $sep . ' ' . esc_html( $group->name ) . ' ' . $sep . ' ' . bp_get_site_name();
						}
					}
				}
			} elseif ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) && get_option( '_bbp_forum_slug', 'forum' ) === buddypress()->current_action || bbp_is_single_topic() || bp_current_action() === get_option( '_bbp_forum_slug' ) ) {

				$topic = get_queried_object();

				if ( bbp_is_single_topic() && ! empty( $topic ) && isset( $topic->ID ) && ! empty( $topic->ID ) ) {

					$forum_id = bbp_get_topic_forum_id( $topic->ID );

					if ( ! empty( $forum_id ) && $topic->ID !== $forum_id ) {

						$forum_title = bbp_get_forum_title( $forum_id );

						if ( ! empty( $forum_title ) ) {
							return esc_html( bbp_get_topic_title( $topic->ID ) ) . ' ' . $sep . ' ' . esc_html( $forum_title ) . ' ' . $sep . ' ' . bp_get_site_name();
						}
					}
				}
			}

			return $title;
		}

		/**
		 * Admin bar menu for forum and topic.
		 *
		 * @since BuddyBoss 1.9.0
		 */
		public function bb_forums_admin_bar_menu() {
			global $wp_admin_bar;
			if ( bp_is_single_item() && bp_is_group() && get_option( '_bbp_forum_slug', 'forum' ) === bp_current_action() && ! bp_is_group_forum_topic() ) {
				$args  = array(
					'name'        => get_query_var( 'name' ),
					'post_type'   => bbp_get_forum_post_type(),
					'numberposts' => 1,
				);
				$forum = get_posts( $args );
				if ( empty( $forum ) ) {
					return;
				}
				$forum_id = isset( $forum[0] ) && isset( $forum[0]->ID ) ? $forum[0]->ID : '';
			} else {
				if ( is_single() && bbp_is_single_forum() ) {
					$forum_id = bbp_get_forum_id();
				}
			}
			if ( bp_is_single_item() && bp_is_group() && get_option( '_bbp_forum_slug', 'forum' ) === bp_current_action() && bp_is_group_forum_topic() ) {
				$args  = array(
					'name'        => bp_action_variable( 1 ),
					'post_type'   => bbp_get_topic_post_type(),
					'numberposts' => 1,
					'post_status' => array( 'publish', 'trash', 'closed', 'spam' ),
				);
				$topic = get_posts( $args );
				if ( empty( $topic ) ) {
					return;
				}
				$topic_id = isset( $topic[0] ) && isset( $topic[0]->ID ) ? $topic[0]->ID : '';
			} else {
				if ( is_single() && bbp_is_single_topic() ) {
					$topic_id = bbp_get_topic_id();
				}
			}
			if ( ! empty( $forum_id ) ) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => '',
						'id'     => 'edit-forum',
						'title'  => __( 'Edit Forum', 'buddyboss' ),
						'href'   => get_edit_post_link( $forum_id ),
					)
				);
			}
			if ( ! empty( $topic_id ) ) {
				$menu_id = 'discussion';
				$wp_admin_bar->add_menu(
					array(
						'title' => esc_html__( 'Edit Discussion', 'buddyboss' ),
						'id'    => $menu_id,
						'href'  => bbp_get_topic_edit_url( $topic_id ),
					)
				);
				if ( ! bbp_is_topic_trash( $topic_id ) && ! bbp_is_topic_spam( $topic_id ) ) {
					$wp_admin_bar->add_menu(
						array(
							'parent' => $menu_id,
							'title'  => ! bbp_is_topic_open( $topic_id ) ? esc_html__( 'Open Discussion', 'buddyboss' ) : esc_html__( 'Close Discussion', 'buddyboss' ),
							'id'     => 'open-' . $menu_id,
							'href'   => wp_nonce_url(
								add_query_arg(
									array(
										'action'   => 'bbp_toggle_topic_close',
										'topic_id' => $topic_id,
									)
								),
								'close-topic_' . $topic_id
							),
						)
					);
				}
				$wp_admin_bar->add_menu(
					array(
						'parent' => $menu_id,
						'title'  => esc_html__( 'Merge Discussion', 'buddyboss' ),
						'id'     => 'merge-' . $menu_id,
						'href'   => add_query_arg(
							array(
								'action' => 'merge',
							),
							bbp_get_topic_edit_url( $topic_id )
						),
					)
				);
				if ( ! bbp_is_topic_spam( $topic_id ) ) {
					if ( bbp_is_topic_trash( $topic_id ) || EMPTY_TRASH_DAYS ) {
						$wp_admin_bar->add_menu(
							array(
								'parent' => $menu_id,
								'title'  => bbp_is_topic_trash( $topic_id ) ? esc_html__( 'Restore from Trash', 'buddyboss' ) : esc_html__( 'Move to Trash', 'buddyboss' ),
								'id'     => 'trash-' . $menu_id,
								'href'   => wp_nonce_url(
									add_query_arg(
										array(
											'action'     => 'bbp_toggle_topic_trash',
											'sub_action' => bbp_is_topic_trash( $topic_id ) ? 'untrash' : 'trash',
											'topic_id'   => $topic_id,
										)
									),
									bbp_is_topic_trash( $topic_id ) ? 'untrash-' . bbp_get_topic_post_type() . '_' . $topic_id : 'trash-' . bbp_get_topic_post_type() . '_' . $topic_id
								),
							)
						);
					}
					if ( bbp_is_topic_trash( $topic_id ) || ! EMPTY_TRASH_DAYS ) {
						$wp_admin_bar->add_menu(
							array(
								'parent' => $menu_id,
								'title'  => esc_html__( 'Delete', 'buddyboss' ),
								'id'     => 'delete-' . $menu_id,
								'href'   => wp_nonce_url(
									add_query_arg(
										array(
											'action'     => 'bbp_toggle_topic_trash',
											'sub_action' => 'delete',
											'topic_id'   => $topic_id,
										)
									),
									'delete-' . bbp_get_topic_post_type() . '_' . $topic_id
								),
							)
						);
					}
				}
				if ( ! bbp_is_topic_trash( $topic_id ) ) {
					$wp_admin_bar->add_menu(
						array(
							'parent' => $menu_id,
							'title'  => bbp_is_topic_spam( $topic_id ) ? esc_html__( 'Mark as Unspam', 'buddyboss' ) : esc_html__( 'Mark as Spam', 'buddyboss' ),
							'id'     => 'spam-' . $menu_id,
							'href'   => wp_nonce_url(
								add_query_arg(
									array(
										'action'   => 'bbp_toggle_topic_spam',
										'topic_id' => $topic_id,
									)
								),
								'spam-topic_' . $topic_id
							),
						)
					);
				}
			}
		}

		/**
		 * Setup forum cache.
		 *
		 * @since BuddyBoss 1.9.0
		 */

		public function setup_cache_groups() {
			// Global groups.
			wp_cache_add_global_groups(
				array(
					'bbpress_posts',
					'bbpress_users',
				)
			);

			parent::setup_cache_groups();
		}
	}
endif;

