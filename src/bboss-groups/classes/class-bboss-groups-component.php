<?php
/**
 * BuddyBoss Groups Component Class.
 *
 * @package BuddyBoss\Groups\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Groups component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BBoss_Groups_Component extends BBoss_Component {

	/**
	 * Auto-join group when non group member performs group activity.
	 *
	 * @since BuddyPress 1.5.0
	 * @var bool
	 */
	public $auto_join;

	/**
	 * The group being currently accessed.
	 *
	 * @since BuddyPress 1.5.0
	 * @var BBoss_Groups_Group
	 */
	public $current_group;

	/**
	 * Default group extension.
	 *
	 * @since BuddyPress 1.6.0
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 * @var string
	 */
	var $default_component;

	/**
	 * Default group extension.
	 *
	 * @since BuddyPress 1.6.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal group names/slugs.
	 *
	 * @since BuddyPress 1.5.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * Group creation/edit steps (e.g. Details, Settings, Avatar, Invites).
	 *
	 * @since BuddyPress 1.5.0
	 * @var array
	 */
	public $group_creation_steps;

	/**
	 * Types of group statuses (Public, Private, Hidden).
	 *
	 * @since BuddyPress 1.5.0
	 * @var array
	 */
	public $valid_status;

	/**
	 * Group types.
	 *
	 * @see bboss_groups_register_group_type()
	 *
	 * @since BuddyPress 2.6.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Current directory group type.
	 *
	 * @see bboss_groups_directory_groups_setup()
	 *
	 * @since BuddyPress 2.7.0
	 * @var string
	 */
	public $current_directory_type = '';

	/**
	 * Start the groups component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'groups',
			'Social Groups',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 70,
				'search_query_arg'         => 'groups_search',
			)
		);
	}

	/**
	 * Include Groups component files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BBoss_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cache',
			'filters',
			'widgets',
			'template',
			'adminbar',
			'functions',
			'notifications',
		);

		// Conditional includes.
		if ( bboss_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bboss_is_groups_component() ) {
			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bboss_current_action(), array( 'create', 'join', 'leave-group' ), true )
			) {
				require $this->path . 'bboss-groups/actions/' . bboss_current_action() . '.php';
			}

			// Actions - RSS feed handler.
			if ( bboss_is_active( 'activity' ) && bboss_is_current_action( 'feed' ) ) {
				require $this->path . 'bboss-groups/actions/feed.php';
			}

			// Actions - Random group handler.
			if ( isset( $_GET['random-group'] ) ) {
				require $this->path . 'bboss-groups/actions/random.php';
			}

			// Screens - Directory.
			if (
				bboss_is_groups_directory() &&
				(
					! bboss_is_current_action( 'type' ) ||
					(
						bboss_is_current_action( 'type' ) &&
						! empty( bboss_action_variable( 0 ) ) &&
						bboss_group_get_group_type_id( bboss_action_variable( 0 ) )
					)
				)
			) {
				require $this->path . 'bboss-groups/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bboss_is_user() ) {
				require $this->path . 'bboss-groups/screens/user/my-groups.php';

				if ( bboss_is_current_action( 'invites' ) ) {
					require $this->path . 'bboss-groups/screens/user/invites.php';
				}
			}

			// Single group.
			if ( bboss_is_group() ) {
				// Actions - Access protection.
				require $this->path . 'bboss-groups/actions/access.php';

				// Public nav items.
				if ( in_array( bboss_current_action(), array( 'home', 'request-membership', 'activity', 'members', 'photos', 'albums', 'subgroups' ), true ) ) {
					require $this->path . 'bboss-groups/screens/single/' . bboss_current_action() . '.php';
				}

				if ( in_array( bboss_get_group_current_members_tab(), array( 'all-members', 'leaders' ), true ) ) {
					require $this->path . 'bboss-groups/screens/single/members/' . bboss_get_group_current_members_tab() . '.php';
				}

				if ( bboss_is_group_invites() && is_user_logged_in() ) {
					require $this->path . 'bboss-groups/screens/single/invite.php';
				}

				if ( bboss_is_group_messages() && is_user_logged_in() ) {
					require $this->path . 'bboss-groups/screens/single/messages.php';
				}

				// Admin nav items.
				if ( bboss_is_item_admin() && is_user_logged_in() ) {
					require $this->path . 'bboss-groups/screens/single/admin.php';

					if ( in_array( bboss_get_group_current_admin_tab(), array( 'edit-details', 'group-settings', 'group-avatar', 'group-cover-image', 'manage-members', 'membership-requests', 'delete-group' ), true ) ) {
						require $this->path . 'bboss-groups/screens/single/admin/' . bboss_get_group_current_admin_tab() . '.php';
					}
				}
			}

			// Theme compatibility.
			new BBoss_Groups_Theme_Compat();
		}
	}

	/**
	 * Set up component global data.
	 *
	 * The BP_GROUPS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BBoss_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = bboss();

		// Define a slug, if necessary.
		if ( ! defined( 'BBOSS_GROUPS_SLUG' ) ) {
			define( 'BBOSS_GROUPS_SLUG', $this->id );
		}

		// Global tables for groups component.
		$global_tables = array(
			'table_name'            => $bp->table_prefix . 'bp_groups',
			'table_name_members'    => $bp->table_prefix . 'bp_groups_members',
			'table_name_groupmeta'  => $bp->table_prefix . 'bp_groups_groupmeta',
			'table_name_membermeta' => $bp->table_prefix . 'bp_groups_membermeta',
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'group'  => $bp->table_prefix . 'bp_groups_groupmeta',
			'member' => $bp->table_prefix . 'bp_groups_membermeta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bboss_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for groups component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BBOSS_GROUPS_SLUG,
			'root_slug'             => isset( $bp->pages->groups->slug ) ? $bp->pages->groups->slug : BBOSS_GROUPS_SLUG,
			'has_directory'         => true,
			'directory_title'       => isset( $bp->pages->groups->title ) ? $bp->pages->groups->title : $default_directory_title,
			'notification_callback' => 'bboss_groups_format_notifications',
			'search_string'         => __( 'Search Groups&hellip;', 'buddyboss-platform' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $args );

		/* Single Group Globals **********************************************/

		// Are we viewing a single group?
		if ( bboss_is_groups_component()
			&& ( ( $group_id = BBoss_Groups_Group::group_exists( bboss_current_action() ) )
				|| ( $group_id = BBoss_Groups_Group::get_id_by_previous_slug( bboss_current_action() ) ) )
			) {
			$bp->is_single_item = true;

			/**
			 * Filters the current PHP Class being used.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param string $value Name of the class being used.
			 */
			$current_group_class = apply_filters( 'bp_groups_current_group_class', 'BBoss_Groups_Group' );

			if ( $current_group_class == 'BBoss_Groups_Group' ) {
				$this->current_group = bboss_groups_get_group( $group_id );

			} else {

				/**
				 * Filters the current group object being instantiated from previous filter.
				 *
				 * @since BuddyPress 1.5.0
				 *
				 * @param object $value Newly instantiated object for the group.
				 */
				$this->current_group = apply_filters( 'bp_groups_current_group_object', new $current_group_class( $group_id ) );
			}

			// When in a single group, the first action is bumped down one because of the
			// group name, so we need to adjust this and set the group name to current_item.
			$bp->current_item   = bboss_current_action();
			$bp->current_action = bboss_action_variable( 0 );
			array_shift( $bp->action_variables );

			// Using "item" not "group" for generic support in other components.
			if ( bboss_current_user_can( 'bp_moderate' ) ) {
				bboss_update_is_item_admin( true, 'groups' );
			} else {
				bboss_update_is_item_admin( bboss_groups_is_user_admin( bboss_loggedin_user_id(), $this->current_group->id ), 'groups' );
			}

			// If the user is not an admin, check if they are a moderator.
			if ( ! bboss_is_item_admin() ) {
				bboss_update_is_item_mod( bboss_groups_is_user_mod( bboss_loggedin_user_id(), $this->current_group->id ), 'groups' );
			}

			// Check once if the current group has a custom front template.
			$this->current_group->front_template = bboss_groups_get_front_template( $this->current_group );

			// Initialize the nav for the groups component.
			$this->nav = new BBoss_Core_Nav( $this->current_group->id );

			// Set current_group to 0 to prevent debug errors.
		} else {
			$this->current_group = 0;
		}

		// Set group type if available.
		if ( bboss_is_groups_directory() && bboss_is_current_action( bboss_get_groups_group_type_base() ) && bboss_action_variable() ) {
			$matched_types = bboss_groups_get_group_types(
				array(
					'has_directory'  => true,
					'directory_slug' => bboss_action_variable(),
				)
			);

			// Set 404 if we do not have a valid group type.
			if ( empty( $matched_types ) ) {
				bboss_do_404();
				return;
			}

			// Set our directory type marker.
			$this->current_directory_type = reset( $matched_types );
		}

		// Set up variables specific to the group creation process.
		if ( bboss_is_groups_component() && bboss_is_current_action( 'create' ) && bboss_user_can_create_groups() && isset( $_COOKIE['bp_new_group_id'] ) ) {
			$bp->groups->new_group_id = (int) $_COOKIE['bp_new_group_id'];
		}

		/**
		 * Filters the list of illegal groups names/slugs.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param array $value Array of illegal group names/slugs.
		 */
		$this->forbidden_names = apply_filters(
			'groups_forbidden_names',
			array(
				'my-groups',
				'create',
				'invites',
				'send-invites',
				'invite',
				'pending-invites',
				'forum',
				'delete',
				'add',
				'admin',
				'request-membership',
				'members',
				'settings',
				'avatar',
				$this->slug,
				$this->root_slug,
			)
		);

		// If the user was attempting to access a group, but no group by that name was found, 404.
		if ( bboss_is_groups_component() && empty( $this->current_group ) && empty( $this->current_directory_type ) && bboss_current_action() && ! in_array( bboss_current_action(), $this->forbidden_names ) ) {
			bboss_do_404();
			return;
		}

		/**
		 * Filters the preconfigured groups creation steps.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param array $value Array of preconfigured group creation steps.
		 */
		$this->group_creation_steps = apply_filters(
			'groups_create_group_steps',
			array(
				'group-details'  => array(
					'name'     => __( 'Details', 'buddyboss-platform' ),
					'position' => 0,
				),
				'group-settings' => array(
					'name'     => __( 'Settings', 'buddyboss-platform' ),
					'position' => 10,
				),
			)
		);

		// If avatar uploads are not disabled, add avatar option.
		$disabled_avatar_uploads = (int) bboss_disable_group_avatar_uploads();
		if ( ! $disabled_avatar_uploads && $bp->avatar->show_avatars ) {
			$this->group_creation_steps['group-avatar'] = array(
				'name'     => __( 'Photo', 'buddyboss-platform' ),
				'position' => 20,
			);
		}

		if ( bboss_group_use_cover_image_header() ) {
			$this->group_creation_steps['group-cover-image'] = array(
				'name'     => __( 'Cover Photo', 'buddyboss-platform' ),
				'position' => 25,
			);
		}

		// If friends component is active, add invitations.
		if ( bboss_is_active( 'friends' ) ) {
			$this->group_creation_steps['group-invites'] = array(
				'name'     => __( 'Invites', 'buddyboss-platform' ),
				'position' => 30,
			);
		}

		/**
		 * Filters the list of valid groups statuses.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param array $value Array of valid group statuses.
		 */
		$this->valid_status = apply_filters(
			'groups_valid_status',
			array(
				'public',
				'private',
				'hidden',
			)
		);

		// Auto Group Membership Approval when non group member performs group activity.
		$this->auto_join = defined( 'BP_DISABLE_AUTO_GROUP_JOIN' ) && BP_DISABLE_AUTO_GROUP_JOIN ? false : true;
	}

	/**
	 * Set up canonical stack for this component.
	 *
	 * @since BuddyPress 2.1.0
	 */
	public function setup_canonical_stack() {
		if ( ! bboss_is_groups_component() ) {
			return;
		}

		if ( empty( $this->current_group ) ) {
			return;
		}

		$customizer_option = 'group_default_tab';
		$default_tab       = '';

		if ( function_exists( 'bboss_nouveau_get_temporary_setting' ) && function_exists( 'bboss_nouveau_get_appearance_settings' ) ) {
			$default_tab = bboss_nouveau_get_temporary_setting( $customizer_option, bboss_nouveau_get_appearance_settings( $customizer_option ) );
		}

		if ( in_array( $default_tab, array( 'photos', 'albums', 'documents', 'videos' ), true ) ) {
			$default_tab = $this->bb_is_media_default_tab_available( $default_tab ) ? $default_tab : 'members';
		} else {
			$default_tab = bboss_is_active( $default_tab ) ? $default_tab : 'members';
		}

		// Check if the default tab is hidden by admin in Navigation Order settings.
		if ( function_exists( 'bboss_nouveau_get_appearance_settings' ) ) {
			$hidden_tabs = bboss_nouveau_get_appearance_settings( 'group_nav_hide' );
			if ( is_array( $hidden_tabs ) && in_array( $default_tab, $hidden_tabs, true ) ) {
				// Fall back to the first visible tab in saved order, or 'members' as last resort.
				$default_tab = 'members';
				$nav_order   = bboss_nouveau_get_appearance_settings( 'group_nav_order' );
				if ( is_array( $nav_order ) ) {
					foreach ( $nav_order as $slug ) {
						// Skip media tabs whose component or group support is unavailable.
						if (
							in_array( $slug, array( 'photos', 'albums', 'documents', 'videos' ), true ) &&
							! $this->bb_is_media_default_tab_available( $slug )
						) {
							continue;
						}

						if ( ! in_array( $slug, $hidden_tabs, true ) ) {
							$default_tab = $slug;
							break;
						}
					}
				}
			}
		}

		/**
		 * Filters the default groups extension.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param string $value BP_GROUPS_DEFAULT_EXTENSION constant if defined,
		 *                      else 'members'.
		 */
		$default_tab             = ( '' === $default_tab ) ? 'members' : $default_tab;
		$this->default_extension = apply_filters( 'bp_groups_default_extension', defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ? BP_GROUPS_DEFAULT_EXTENSION : $default_tab );

		$bp = bboss();

		$user_has_access = $this->current_group->user_has_access;
		$is_visible      = $this->current_group->is_visible;

		if ( ! $user_has_access && $is_visible && is_user_logged_in() ) {
			$bp->current_action = 'request-membership';
		}

		// members are displayed in the front nav.
		if ( bboss_is_current_action( 'home' ) || ! bboss_current_action() ) {
			$bp->current_action = $this->default_extension;
		}

		// Prepare for a redirect to the canonical URL.
		$bp->canonical_stack['base_url'] = bboss_get_group_permalink( $this->current_group );

		if ( bboss_current_action() ) {
			$bp->canonical_stack['action'] = bboss_current_action();
		}

		if ( ! empty( $bp->action_variables ) ) {
			$bp->canonical_stack['action_variables'] = bboss_action_variables();
		}

		// When viewing the default extension, the canonical URL should not have
		// that extension's slug, unless more has been tacked onto the URL via
		// action variables.
		if ( bboss_is_current_action( $this->default_extension ) && empty( $bp->action_variables ) ) {
			unset( $bp->canonical_stack['action'] );
		}
	}

	/**
	 * Whether a media-family group tab can be used as the group's default tab.
	 *
	 * Videos and Documents are provided by the BuddyBoss Add-ons plugin, so they can be
	 * unavailable while Media stays active. A default tab pointing at a missing sub-nav
	 * would send every group home page to a 404.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @param string $tab Tab slug: 'photos', 'albums', 'documents' or 'videos'.
	 *
	 * @return bool True when the tab's sub-nav is registered for groups.
	 */
	private function bb_is_media_default_tab_available( $tab ) {
		if ( ! bboss_is_active( 'media' ) ) {
			return false;
		}

		switch ( $tab ) {
			case 'photos':
				return bboss_is_group_media_support_enabled();
			case 'albums':
				return bboss_is_group_media_support_enabled() && bboss_is_group_albums_support_enabled();
			case 'documents':
				return bboss_is_active( 'document' ) && bboss_is_group_document_support_enabled();
			case 'videos':
				return bboss_is_active( 'video' ) && bboss_is_group_video_support_enabled();
		}

		return false;
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BBoss_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BBoss_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		$bp = bboss();

		// Determine user to use.
		if ( bboss_displayed_user_domain() ) {
			$user_domain = bboss_displayed_user_domain();
		} elseif ( bboss_loggedin_user_domain() ) {
			$user_domain = bboss_loggedin_user_domain();
		} else {
			$user_domain = false;
		}

		$nav_name = __( 'Groups', 'buddyboss-platform' );

		$slug = bboss_get_groups_slug();

		// Add 'Groups' to the main navigation.
		$main_nav = array(
			'name'                => $nav_name,
			'slug'                => $slug,
			'position'            => 70,
			'screen_function'     => 'bboss_groups_screen_my_groups',
			'default_subnav_slug' => 'my-groups',
			'item_css_id'         => $this->id,
		);

		if ( ! empty( $user_domain ) ) {
			$access      = bboss_core_can_edit_settings();
			$groups_link = trailingslashit( $user_domain . $slug );

			// Add the My Groups nav item.
			$sub_nav[] = array(
				'name'            => __( 'My Groups', 'buddyboss-platform' ),
				'slug'            => 'my-groups',
				'parent_url'      => $groups_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_groups_screen_my_groups',
				'position'        => 10,
				'item_css_id'     => 'groups-my-groups',
			);

			// Add the Group Invites nav item.
			$sub_nav[] = array(
				'name'            => __( 'Invitations', 'buddyboss-platform' ),
				'slug'            => 'invites',
				'parent_url'      => $groups_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_groups_screen_group_invites',
				'user_has_access' => $access,
				'position'        => 30,
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}

		if ( bboss_is_groups_component() && bboss_is_single_item() ) {

			// Reset sub nav.
			$sub_nav = array();

			/*
			 * The top-level Groups item is called 'Memberships' for legacy reasons.
			 * It does not appear in the interface.
			 */
			bboss_core_new_nav_item(
				array(
					'name'                => __( 'My Groups', 'buddyboss-platform' ),
					'slug'                => $this->current_group->slug,
					'position'            => -1, // Do not show in BuddyBar.
					'screen_function'     => 'bboss_groups_screen_group_home',
					'default_subnav_slug' => $this->default_extension,
					'item_css_id'         => $this->id,
				),
				'groups'
			);

			$group_link = bboss_get_group_permalink( $this->current_group );

			$sub_nav[] = array(
				'name'            => apply_filters( 'group_single_members_label', __( 'Members', 'buddyboss-platform' ) ),
				'slug'            => 'members',
				'parent_url'      => $group_link,
				'parent_slug'     => $this->current_group->slug,
				'screen_function' => 'bboss_groups_screen_group_members',
				'user_has_access' => $this->current_group->user_has_access,
				'position'        => 10,
				'item_css_id'     => 'members',
			);

			$members_link = trailingslashit( $group_link . 'members' );

			// Common params to all member sub nav items.
			$default_params = array(
				'parent_url'        => $members_link,
				'parent_slug'       => $this->current_group->slug . '_members',
				'screen_function'   => 'bboss_groups_screen_group_members',
				'user_has_access'   => $this->current_group->user_has_access,
				'show_in_admin_bar' => true,
			);

			// $sub_nav[] = array_merge( array(
			// 'name'              => __( 'All Members', 'buddyboss' ),
			// 'slug'              => 'all-members',
			// 'position'          => 0,
			// ), $default_params );
			//
			// $sub_nav[] = array_merge( array(
			// 'name'              => __( 'Group Leaders', 'buddyboss' ),
			// 'slug'              => 'leaders',
			// 'position'          => 10,
			// ), $default_params );

			if ( bboss_is_active( 'activity' ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Feed', 'buddyboss-platform' ),
					'slug'            => 'activity',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'bboss_groups_screen_group_activity',
					'position'        => 20,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'activity',
					'no_access_url'   => $group_link,
				);
			}

			if ( bboss_enable_group_hierarchies() ) {
				$descendant_groups = bboss_get_descendent_groups( bboss_get_current_group_id(), bboss_loggedin_user_id() );
				if ( $total_descendant = count( $descendant_groups ) ) {
					$sub_nav[] = array(
						'name'            => sprintf( __( 'Subgroups', 'buddyboss-platform' ), '<span>' . number_format( $total_descendant ) . '</span>' ),
						'slug'            => 'subgroups',
						'parent_url'      => $group_link,
						'parent_slug'     => $this->current_group->slug,
						'screen_function' => 'groups_screen_group_subgroups',
						'position'        => 30,
						'user_has_access' => $this->current_group->user_has_access,
						'item_css_id'     => 'subgroups',
						'no_access_url'   => $group_link,
					);
				}
			}

			// If this is a private group, and the user is not a
			// member and does not have an outstanding invitation,
			// show a "Request Membership" nav item.
			if ( bboss_groups_check_for_membership_request( bboss_loggedin_user_id(), $this->current_group->id ) || bboss_current_user_can( 'groups_request_membership', array( 'group_id' => $this->current_group->id ) ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Request Access', 'buddyboss-platform' ),
					'slug'            => 'request-membership',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'bboss_groups_screen_group_request_membership',
					'position'        => 30,
				);
			}

			if ( bboss_is_active( 'friends' ) && bboss_groups_user_can_send_invites() ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Invites', 'buddyboss-platform' ),
					'slug'            => 'invite',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'bboss_groups_screen_group_invite',
					'item_css_id'     => 'invite',
					'position'        => 30,
					'user_has_access' => $this->current_group->user_has_access,
					'no_access_url'   => $group_link,
				);

				$admin_link_invite = trailingslashit( $group_link . 'invite' );
				// Common params to all nav items.
				$default_params_invite = array(
					'parent_url'        => $admin_link_invite,
					'parent_slug'       => $this->current_group->slug . '_invite',
					'screen_function'   => 'bboss_groups_screen_group_invite',
					'user_has_access'   => $this->current_group->user_has_access,
					'show_in_admin_bar' => true,
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Send Invites', 'buddyboss-platform' ),
						'slug'     => 'send-invites',
						'position' => 30,
					),
					$default_params_invite
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Pending Invites', 'buddyboss-platform' ),
						'slug'     => 'pending-invites',
						'position' => 1,
					),
					$default_params_invite
				);
			}

			if ( bboss_is_active( 'media' ) && bboss_is_group_media_support_enabled() ) {
				$sub_nav[] = array(
					'name'            => __( 'Photos', 'buddyboss-platform' ),
					'slug'            => 'photos',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_media',
					'position'        => 21,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'photos',
					'no_access_url'   => $group_link,
				);

				if ( bboss_is_group_albums_support_enabled() ) {
					$sub_nav[] = array(
						'name'            => __( 'Albums', 'buddyboss-platform' ),
						'slug'            => 'albums',
						'parent_url'      => $group_link,
						'parent_slug'     => $this->current_group->slug,
						'screen_function' => 'groups_screen_group_albums',
						'position'        => 23,
						'user_has_access' => $this->current_group->user_has_access,
						'item_css_id'     => 'albums',
						'no_access_url'   => $group_link,
					);
				}
			}

			if ( bboss_is_active( 'media' ) && bboss_is_active( 'document' ) && bboss_is_group_document_support_enabled() ) {
				$sub_nav[] = array(
					'name'            => __( 'Documents', 'buddyboss-platform' ),
					'slug'            => 'documents',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_document',
					'position'        => 24,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'documents',
					'no_access_url'   => $group_link,
				);
			}

			if ( bboss_is_active( 'media' ) && bboss_is_active( 'video' ) && bboss_is_group_video_support_enabled() ) {
				// Checked if order already set before, New menu(video) will be added at last
				$video_menu_position = 22;
				$orders              = get_option( 'bp_nouveau_appearance' );
				if ( isset( $orders['group_nav_order'] ) && ! empty( $orders['group_nav_order'] ) && ! in_array( 'vide', $orders['group_nav_order'] ) ) {
					$video_menu_position = 1001;
				}
				$sub_nav[] = array(
					'name'            => __( 'Videos', 'buddyboss-platform' ),
					'slug'            => 'videos',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_video',
					'position'        => $video_menu_position,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'videos',
					'no_access_url'   => $group_link,
				);
			}

			$message_status = bboss_group_get_message_status( $this->current_group->id );
			$show           = false;
			if ( 'mods' === $message_status ) {
				$admin     = bboss_groups_is_user_admin( bboss_loggedin_user_id(), $this->current_group->id );
				$moderator = bboss_groups_is_user_mod( bboss_loggedin_user_id(), $this->current_group->id );
				if ( $admin || $moderator ) {
					$show = true;
				}
			} elseif ( 'members' === $message_status ) {
				$member    = bboss_groups_is_user_member( bboss_loggedin_user_id(), $this->current_group->id );
				$admin     = bboss_groups_is_user_admin( bboss_loggedin_user_id(), $this->current_group->id );
				$moderator = bboss_groups_is_user_mod( bboss_loggedin_user_id(), $this->current_group->id );
				if ( $member || $admin || $moderator ) {
					$show = true;
				}
			} else {
				$admin = bboss_groups_is_user_admin( bboss_loggedin_user_id(), $this->current_group->id );
				if ( $admin ) {
					$show = true;
				}
			}
			if ( true === bboss_disable_group_messages() && bboss_is_active( 'messages' ) && $show ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Messages', 'buddyboss-platform' ),
					'slug'            => 'messages',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_messages',
					'item_css_id'     => 'group-messages',
					'position'        => 25,
					'user_has_access' => $this->current_group->user_has_access,
					'no_access_url'   => $group_link,
				);

				$admin_link_message = trailingslashit( $group_link . 'messages' );
				// Common params to all nav items.
				$default_params_message = array(
					'parent_url'        => $admin_link_message,
					'parent_slug'       => $this->current_group->slug . '_messages',
					'screen_function'   => 'groups_screen_group_messages',
					'user_has_access'   => $this->current_group->user_has_access,
					'show_in_admin_bar' => true,
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Send Group Message', 'buddyboss-platform' ),
						'slug'     => 'public-message',
						'position' => 0,
					),
					$default_params_message
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Send Private Message', 'buddyboss-platform' ),
						'slug'     => 'private-message',
						'position' => 1,
					),
					$default_params_message
				);
			}

			// If the user is a group admin, then show the group admin nav item.
			if ( bboss_is_item_admin() ) {
				$sub_nav[] = array(
					'name'            => __( 'Manage', 'buddyboss-platform' ),
					'slug'            => 'admin',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'bboss_groups_screen_group_admin',
					'position'        => 1000,
					'user_has_access' => true,
					'item_css_id'     => 'admin',
					'no_access_url'   => $group_link,
				);

				$admin_link = trailingslashit( $group_link . 'admin' );

				// Common params to all nav items.
				$default_params = array(
					'parent_url'        => $admin_link,
					'parent_slug'       => $this->current_group->slug . '_manage',
					'screen_function'   => 'bboss_groups_screen_group_admin',
					'user_has_access'   => bboss_is_item_admin(),
					'show_in_admin_bar' => true,
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Details', 'buddyboss-platform' ),
						'slug'     => 'edit-details',
						'position' => 0,
					),
					$default_params
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Settings', 'buddyboss-platform' ),
						'slug'     => 'group-settings',
						'position' => 10,
					),
					$default_params
				);

				if ( ! bboss_disable_group_avatar_uploads() && bboss()->avatar->show_avatars ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Photo', 'buddyboss-platform' ),
							'slug'     => 'group-avatar',
							'position' => 20,
						),
						$default_params
					);
				}

				if ( bboss_group_use_cover_image_header() ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Cover Photo', 'buddyboss-platform' ),
							'slug'     => 'group-cover-image',
							'position' => 25,
						),
						$default_params
					);
				}

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Members', 'buddyboss-platform' ),
						'slug'     => 'manage-members',
						'position' => 30,
					),
					$default_params
				);

				if ( 'private' == $this->current_group->status ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Requests', 'buddyboss-platform' ),
							'slug'     => 'membership-requests',
							'position' => 40,
						),
						$default_params
					);
				}

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Delete', 'buddyboss-platform' ),
						'slug'     => 'delete-group',
						'position' => 1000,
					),
					$default_params
				);
			}

			foreach ( $sub_nav as $nav ) {
				bboss_core_new_subnav_item( $nav, 'groups' );
			}
		}

		if ( isset( $this->current_group->user_has_access ) ) {

			/**
			 * Fires at the end of the groups navigation setup if user has access.
			 *
			 * @since BuddyPress 1.0.2
			 *
			 * @param bool $user_has_access Whether or not user has access.
			 */
			do_action( 'groups_setup_nav', $this->current_group->user_has_access );
		} else {

			/** This action is documented in bboss-groups/bboss-groups-loader.php */
			do_action( 'groups_setup_nav' );
		}
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BBoss_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BBoss_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$groups_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() );

			// Pending group invites.
			$count   = bboss_groups_get_invite_count_for_user();
			$title   = __( 'Groups', 'buddyboss-platform' );
			$pending = __( 'No Pending Invites', 'buddyboss-platform' );

			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Group invitation count for the current user */
					__( 'Groups %s', 'buddyboss-platform' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);

				$pending = sprintf(
					/* translators: %s: Group invitation count for the current user */
					__( 'Pending Invites %s', 'buddyboss-platform' ),
					'<span class="count">' . bboss_core_number_format( $count ) . '</span>'
				);
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => bboss()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $groups_link,
			);

			// My Groups.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-memberships',
				'title'    => __( 'My Groups', 'buddyboss-platform' ),
				'href'     => $groups_link,
				'position' => 10,
			);

			// Invitations.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-invites',
				'title'    => $pending,
				'href'     => trailingslashit( $groups_link . 'invites' ),
				'position' => 30,
			);

			// Create a Group.
			if ( bboss_user_can_create_groups() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-create',
					'title'    => __( 'Create Group', 'buddyboss-platform' ),
					'href'     => trailingslashit( bboss_get_groups_directory_permalink() . 'create' ),
					'position' => 90,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function setup_title() {

		if ( bboss_is_groups_component() ) {
			$bp = bboss();

			if ( bboss_is_my_profile() && ! bboss_is_single_item() ) {
				$bp->bp_options_title = __( 'My Groups', 'buddyboss-platform' );

			} elseif ( ! bboss_is_my_profile() && ! bboss_is_single_item() ) {
				$bp->bp_options_avatar = bboss_core_fetch_avatar(
					array(
						'item_id' => bboss_displayed_user_id(),
						'type'    => 'thumb',
						/* translators: %s: member display name. */
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss-platform' ), bboss_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bboss_get_displayed_user_fullname();

				// We are viewing a single group, so set up the
				// group navigation menu using the $this->current_group global.
			} elseif ( bboss_is_single_item() ) {
				$bp->bp_options_title  = $this->current_group->name;
				$bp->bp_options_avatar = bboss_core_fetch_avatar(
					array(
						'item_id'    => $this->current_group->id,
						'object'     => 'group',
						'type'       => 'thumb',
						'avatar_dir' => 'group-avatars',
						'alt'        => __( 'Group Profile Photo', 'buddyboss-platform' ),
					)
				);

				if ( empty( $bp->bp_options_avatar ) ) {
					$bp->bp_options_avatar = '<img src="' . esc_url( bboss_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group', 'type' => 'thumb' ) ) ) . '" alt="' . esc_attr__( 'No Group Profile Photo', 'buddyboss-platform' ) . '" class="avatar" />';
				}
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyBoss 3.5.1 Added the `bb_nouveau_group_invites` global cache group.
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_groups',
				'bp_group_admins',
				'bp_group_invite_count',
				'group_meta',
				'bp_groups_memberships',
				'bp_groups_memberships_for_user',
				'bp_group_mods',
				'bp_groups_invitations_as_memberships',
				'bp_groups_group_type',
				'bp_groups_member',
				'bb_nouveau_group_invites',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Set up taxonomies.
	 *
	 * @since BuddyPress 2.6.0
	 */
	public function register_taxonomies() {
		// Group Type.
		register_taxonomy(
			'bp_group_type',
			'bp_group',
			array(
				'public' => false,
			)
		);
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BBoss_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BBoss_REST_Groups_Endpoint',
			'BBoss_REST_Groups_Details_Endpoint',
			'BBoss_REST_Group_Membership_Endpoint',
			'BBoss_REST_Group_Settings_Endpoint',
			'BBoss_REST_Group_Invites_Endpoint',
			'BBoss_REST_Group_Membership_Request_Endpoint',
			'BBoss_REST_Groups_Types_Endpoint',
			'BBoss_REST_Attachments_Group_Avatar_Endpoint',
		);

		// Support to Group Cover.
		if ( bboss_is_active( 'groups', 'cover_image' ) ) {
			$controllers[] = 'BBoss_REST_Attachments_Group_Cover_Endpoint';
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the Groups Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
