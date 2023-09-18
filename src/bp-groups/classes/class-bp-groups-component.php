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
class BP_Groups_Component extends BP_Component {

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
	 * @var BP_Groups_Group
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
	 * @see bp_groups_register_group_type()
	 *
	 * @since BuddyPress 2.6.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Current directory group type.
	 *
	 * @see groups_directory_groups_setup()
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
			__( 'Social Groups', 'buddyboss' ),
			buddypress()->plugin_dir,
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
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
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
		if ( bp_is_active( 'activity' ) ) {
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

		if ( bp_is_groups_component() ) {
			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'create', 'join', 'leave-group' ), true )
			) {
				require $this->path . 'bp-groups/actions/' . bp_current_action() . '.php';
			}

			// Actions - RSS feed handler.
			if ( bp_is_active( 'activity' ) && bp_is_current_action( 'feed' ) ) {
				require $this->path . 'bp-groups/actions/feed.php';
			}

			// Actions - Random group handler.
			if ( isset( $_GET['random-group'] ) ) {
				require $this->path . 'bp-groups/actions/random.php';
			}

			// Screens - Directory.
			if (
				bp_is_groups_directory() &&
				(
					! bp_is_current_action( 'type' ) ||
					(
						bp_is_current_action( 'type' ) &&
						! empty( bp_action_variable( 0 ) ) &&
						bp_group_get_group_type_id( bp_action_variable( 0 ) )
					)
				)
			) {
				require $this->path . 'bp-groups/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bp_is_user() ) {
				require $this->path . 'bp-groups/screens/user/my-groups.php';

				if ( bp_is_current_action( 'invites' ) ) {
					require $this->path . 'bp-groups/screens/user/invites.php';
				}
			}

			// Single group.
			if ( bp_is_group() ) {
				// Actions - Access protection.
				require $this->path . 'bp-groups/actions/access.php';

				// Public nav items.
				if ( in_array( bp_current_action(), array( 'home', 'request-membership', 'activity', 'members', 'photos', 'albums', 'subgroups', 'documents', 'folders', 'videos' ), true ) ) {
					require $this->path . 'bp-groups/screens/single/' . bp_current_action() . '.php';
				}

				if ( in_array( bp_get_group_current_members_tab(), array( 'all-members', 'leaders' ), true ) ) {
					require $this->path . 'bp-groups/screens/single/members/' . bp_get_group_current_members_tab() . '.php';
				}

				if ( bp_is_group_invites() && is_user_logged_in() ) {
					require $this->path . 'bp-groups/screens/single/invite.php';
				}

				if ( bp_is_group_messages() && is_user_logged_in() ) {
					require $this->path . 'bp-groups/screens/single/messages.php';
				}

				// Admin nav items.
				if ( bp_is_item_admin() && is_user_logged_in() ) {
					require $this->path . 'bp-groups/screens/single/admin.php';

					if ( in_array( bp_get_group_current_admin_tab(), array( 'edit-details', 'group-settings', 'group-avatar', 'group-cover-image', 'manage-members', 'membership-requests', 'delete-group' ), true ) ) {
						require $this->path . 'bp-groups/screens/single/admin/' . bp_get_group_current_admin_tab() . '.php';
					}
				}
			}

			// Theme compatibility.
			new BP_Groups_Theme_Compat();
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
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_GROUPS_SLUG' ) ) {
			define( 'BP_GROUPS_SLUG', $this->id );
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
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for groups component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BP_GROUPS_SLUG,
			'root_slug'             => isset( $bp->pages->groups->slug ) ? $bp->pages->groups->slug : BP_GROUPS_SLUG,
			'has_directory'         => true,
			'directory_title'       => isset( $bp->pages->groups->title ) ? $bp->pages->groups->title : $default_directory_title,
			'notification_callback' => 'groups_format_notifications',
			'search_string'         => __( 'Search Groups&hellip;', 'buddyboss' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $args );

		/* Single Group Globals **********************************************/

		// Are we viewing a single group?
		if ( bp_is_groups_component()
			&& ( ( $group_id = BP_Groups_Group::group_exists( bp_current_action() ) )
				|| ( $group_id = BP_Groups_Group::get_id_by_previous_slug( bp_current_action() ) ) )
			) {
			$bp->is_single_item = true;

			/**
			 * Filters the current PHP Class being used.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param string $value Name of the class being used.
			 */
			$current_group_class = apply_filters( 'bp_groups_current_group_class', 'BP_Groups_Group' );

			if ( $current_group_class == 'BP_Groups_Group' ) {
				$this->current_group = groups_get_group( $group_id );

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
			$bp->current_item   = bp_current_action();
			$bp->current_action = bp_action_variable( 0 );
			array_shift( $bp->action_variables );

			// Using "item" not "group" for generic support in other components.
			if ( bp_current_user_can( 'bp_moderate' ) ) {
				bp_update_is_item_admin( true, 'groups' );
			} else {
				bp_update_is_item_admin( groups_is_user_admin( bp_loggedin_user_id(), $this->current_group->id ), 'groups' );
			}

			// If the user is not an admin, check if they are a moderator.
			if ( ! bp_is_item_admin() ) {
				bp_update_is_item_mod( groups_is_user_mod( bp_loggedin_user_id(), $this->current_group->id ), 'groups' );
			}

			// Check once if the current group has a custom front template.
			$this->current_group->front_template = bp_groups_get_front_template( $this->current_group );

			// Initialize the nav for the groups component.
			$this->nav = new BP_Core_Nav( $this->current_group->id );

			// Set current_group to 0 to prevent debug errors.
		} else {
			$this->current_group = 0;
		}

		// Set group type if available.
		if ( bp_is_groups_directory() && bp_is_current_action( bp_get_groups_group_type_base() ) && bp_action_variable() ) {
			$matched_types = bp_groups_get_group_types(
				array(
					'has_directory'  => true,
					'directory_slug' => bp_action_variable(),
				)
			);

			// Set 404 if we do not have a valid group type.
			if ( empty( $matched_types ) ) {
				bp_do_404();
				return;
			}

			// Set our directory type marker.
			$this->current_directory_type = reset( $matched_types );
		}

		// Set up variables specific to the group creation process.
		if ( bp_is_groups_component() && bp_is_current_action( 'create' ) && bp_user_can_create_groups() && isset( $_COOKIE['bp_new_group_id'] ) ) {
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
		if ( bp_is_groups_component() && empty( $this->current_group ) && empty( $this->current_directory_type ) && bp_current_action() && ! in_array( bp_current_action(), $this->forbidden_names ) ) {
			bp_do_404();
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
					'name'     => __( 'Details', 'buddyboss' ),
					'position' => 0,
				),
				'group-settings' => array(
					'name'     => __( 'Settings', 'buddyboss' ),
					'position' => 10,
				),
			)
		);

		// If avatar uploads are not disabled, add avatar option.
		$disabled_avatar_uploads = (int) bp_disable_group_avatar_uploads();
		if ( ! $disabled_avatar_uploads && $bp->avatar->show_avatars ) {
			$this->group_creation_steps['group-avatar'] = array(
				'name'     => __( 'Photo', 'buddyboss' ),
				'position' => 20,
			);
		}

		if ( bp_group_use_cover_image_header() ) {
			$this->group_creation_steps['group-cover-image'] = array(
				'name'     => __( 'Cover Photo', 'buddyboss' ),
				'position' => 25,
			);
		}

		// If friends component is active, add invitations.
		if ( bp_is_active( 'friends' ) ) {
			$this->group_creation_steps['group-invites'] = array(
				'name'     => __( 'Invites', 'buddyboss' ),
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
		if ( ! bp_is_groups_component() ) {
			return;
		}

		if ( empty( $this->current_group ) ) {
			return;
		}

		$customizer_option = 'group_default_tab';
		$default_tab       = '';

		if ( function_exists( 'bp_nouveau_get_temporary_setting' ) && function_exists( 'bp_nouveau_get_appearance_settings' ) ) {
			$default_tab = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );
		}

		if ( 'photos' === $default_tab || 'albums' === $default_tab || 'documents' === $default_tab || 'videos' === $default_tab ) {
			$default_tab = bp_is_active( 'media' ) ? $default_tab : 'members';
		} else {
			$default_tab = bp_is_active( $default_tab ) ? $default_tab : 'members';
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

		$bp = buddypress();

		$user_has_access = $this->current_group->user_has_access;
		$is_visible      = $this->current_group->is_visible;

		if ( ! $user_has_access && $is_visible && is_user_logged_in() ) {
			$bp->current_action = 'request-membership';
		}

		// members are displayed in the front nav.
		if ( bp_is_current_action( 'home' ) || ! bp_current_action() ) {
			$bp->current_action = $this->default_extension;
		}

		// Prepare for a redirect to the canonical URL.
		$bp->canonical_stack['base_url'] = bp_get_group_permalink( $this->current_group );

		if ( bp_current_action() ) {
			$bp->canonical_stack['action'] = bp_current_action();
		}

		if ( ! empty( $bp->action_variables ) ) {
			$bp->canonical_stack['action_variables'] = bp_action_variables();
		}

		// When viewing the default extension, the canonical URL should not have
		// that extension's slug, unless more has been tacked onto the URL via
		// action variables.
		if ( bp_is_current_action( $this->default_extension ) && empty( $bp->action_variables ) ) {
			unset( $bp->canonical_stack['action'] );
		}
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		$bp = buddypress();

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			$user_domain = false;
		}

		// Only grab count if we're on a user page.
		if ( bp_is_user() ) {
			$class     = ( 0 === groups_total_groups_for_user( bp_displayed_user_id() ) ) ? 'no-count' : 'count';
			$nav_name  = __( 'Groups', 'buddyboss' );
			$nav_name .= sprintf(
				' <span class="%s">%s</span>',
				esc_attr( $class ),
				bp_get_total_group_count_for_user()
			);
		} else {
			$nav_name = __( 'Groups', 'buddyboss' );
		}

		$slug = bp_get_groups_slug();

		// Add 'Groups' to the main navigation.
		$main_nav = array(
			'name'                => $nav_name,
			'slug'                => $slug,
			'position'            => 70,
			'screen_function'     => 'groups_screen_my_groups',
			'default_subnav_slug' => 'my-groups',
			'item_css_id'         => $this->id,
		);

		if ( ! empty( $user_domain ) ) {
			$access      = bp_core_can_edit_settings();
			$groups_link = trailingslashit( $user_domain . $slug );

			// Add the My Groups nav item.
			$sub_nav[] = array(
				'name'            => __( 'My Groups', 'buddyboss' ),
				'slug'            => 'my-groups',
				'parent_url'      => $groups_link,
				'parent_slug'     => $slug,
				'screen_function' => 'groups_screen_my_groups',
				'position'        => 10,
				'item_css_id'     => 'groups-my-groups',
			);

			// Add the Group Invites nav item.
			$sub_nav[] = array(
				'name'            => __( 'Invitations', 'buddyboss' ),
				'slug'            => 'invites',
				'parent_url'      => $groups_link,
				'parent_slug'     => $slug,
				'screen_function' => 'groups_screen_group_invites',
				'user_has_access' => $access,
				'position'        => 30,
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}

		if ( bp_is_groups_component() && bp_is_single_item() ) {

			// Reset sub nav.
			$sub_nav = array();

			/*
			 * The top-level Groups item is called 'Memberships' for legacy reasons.
			 * It does not appear in the interface.
			 */
			bp_core_new_nav_item(
				array(
					'name'                => __( 'My Groups', 'buddyboss' ),
					'slug'                => $this->current_group->slug,
					'position'            => -1, // Do not show in BuddyBar.
					'screen_function'     => 'groups_screen_group_home',
					'default_subnav_slug' => $this->default_extension,
					'item_css_id'         => $this->id,
				),
				'groups'
			);

			$group_link = bp_get_group_permalink( $this->current_group );

			// Add the "Members" subnav item, as this will always be present.
			$sub_nav[] = array(
				'name'            => sprintf( apply_filters( 'group_single_members_label', __( 'Members', 'buddyboss' ) ) . __( ' %s', 'buddyboss' ), '<span>' . number_format( $this->current_group->total_member_count ) . '</span>' ),
				'slug'            => 'members',
				'parent_url'      => $group_link,
				'parent_slug'     => $this->current_group->slug,
				'screen_function' => 'groups_screen_group_members',
				'user_has_access' => $this->current_group->user_has_access,
				'position'        => 10,
				'item_css_id'     => 'members',
			);

			$members_link = trailingslashit( $group_link . 'members' );

			// Common params to all member sub nav items.
			$default_params = array(
				'parent_url'        => $members_link,
				'parent_slug'       => $this->current_group->slug . '_members',
				'screen_function'   => 'groups_screen_group_members',
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

			if ( bp_is_active( 'activity' ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Feed', 'buddyboss' ),
					'slug'            => 'activity',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_activity',
					'position'        => 20,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'activity',
					'no_access_url'   => $group_link,
				);
			}

			if ( bp_enable_group_hierarchies() ) {
				$descendant_groups = bp_get_descendent_groups( bp_get_current_group_id(), bp_loggedin_user_id() );
				if ( $total_descendant = count( $descendant_groups ) ) {
					$sub_nav[] = array(
						'name'            => sprintf( __( 'Subgroups', 'buddyboss' ), '<span>' . number_format( $total_descendant ) . '</span>' ),
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
			if ( groups_check_for_membership_request( bp_loggedin_user_id(), $this->current_group->id ) || bp_current_user_can( 'groups_request_membership', array( 'group_id' => $this->current_group->id ) ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Request Access', 'buddyboss' ),
					'slug'            => 'request-membership',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_request_membership',
					'position'        => 30,
				);
			}

			if ( bp_is_active( 'friends' ) && bp_groups_user_can_send_invites() ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Invites', 'buddyboss' ),
					'slug'            => 'invite',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_invite',
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
					'screen_function'   => 'groups_screen_group_invite',
					'user_has_access'   => $this->current_group->user_has_access,
					'show_in_admin_bar' => true,
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Send Invites', 'buddyboss' ),
						'slug'     => 'send-invites',
						'position' => 30,
					),
					$default_params_invite
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Pending Invites', 'buddyboss' ),
						'slug'     => 'pending-invites',
						'position' => 1,
					),
					$default_params_invite
				);
			}

			if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
				$sub_nav[] = array(
					'name'            => __( 'Photos', 'buddyboss' ),
					'slug'            => 'photos',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_media',
					'position'        => 21,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'photos',
					'no_access_url'   => $group_link,
				);

				if ( bp_is_group_albums_support_enabled() ) {
					$sub_nav[] = array(
						'name'            => __( 'Albums', 'buddyboss' ),
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

			if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) {
				$sub_nav[] = array(
					'name'            => __( 'Documents', 'buddyboss' ),
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

			if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() ) {
				// Checked if order already set before, New menu(video) will be added at last
				$video_menu_position = 22;
				$orders              = get_option( 'bp_nouveau_appearance' );
				if ( isset( $orders['group_nav_order'] ) && ! empty( $orders['group_nav_order'] ) && ! in_array( 'vide', $orders['group_nav_order'] ) ) {
					$video_menu_position = 1001;
				}
				$sub_nav[] = array(
					'name'            => __( 'Videos', 'buddyboss' ),
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

			$message_status = bp_group_get_message_status( $this->current_group->id );
			$show           = false;
			if ( 'mods' === $message_status ) {
				$admin     = groups_is_user_admin( bp_loggedin_user_id(), $this->current_group->id );
				$moderator = groups_is_user_mod( bp_loggedin_user_id(), $this->current_group->id );
				if ( $admin || $moderator ) {
					$show = true;
				}
			} elseif ( 'members' === $message_status ) {
				$member    = groups_is_user_member( bp_loggedin_user_id(), $this->current_group->id );
				$admin     = groups_is_user_admin( bp_loggedin_user_id(), $this->current_group->id );
				$moderator = groups_is_user_mod( bp_loggedin_user_id(), $this->current_group->id );
				if ( $member || $admin || $moderator ) {
					$show = true;
				}
			} else {
				$admin = groups_is_user_admin( bp_loggedin_user_id(), $this->current_group->id );
				if ( $admin ) {
					$show = true;
				}
			}
			if ( true === bp_disable_group_messages() && bp_is_active( 'messages' ) && $show ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Messages', 'buddyboss' ),
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
						'name'     => __( 'Send Group Message', 'buddyboss' ),
						'slug'     => 'public-message',
						'position' => 0,
					),
					$default_params_message
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Send Private Message', 'buddyboss' ),
						'slug'     => 'private-message',
						'position' => 1,
					),
					$default_params_message
				);
			}

			// If the user is a group admin, then show the group admin nav item.
			if ( bp_is_item_admin() ) {
				$sub_nav[] = array(
					'name'            => __( 'Manage', 'buddyboss' ),
					'slug'            => 'admin',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_admin',
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
					'screen_function'   => 'groups_screen_group_admin',
					'user_has_access'   => bp_is_item_admin(),
					'show_in_admin_bar' => true,
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Details', 'buddyboss' ),
						'slug'     => 'edit-details',
						'position' => 0,
					),
					$default_params
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Settings', 'buddyboss' ),
						'slug'     => 'group-settings',
						'position' => 10,
					),
					$default_params
				);

				if ( ! bp_disable_group_avatar_uploads() && buddypress()->avatar->show_avatars ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Photo', 'buddyboss' ),
							'slug'     => 'group-avatar',
							'position' => 20,
						),
						$default_params
					);
				}

				if ( bp_group_use_cover_image_header() ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Cover Photo', 'buddyboss' ),
							'slug'     => 'group-cover-image',
							'position' => 25,
						),
						$default_params
					);
				}

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Members', 'buddyboss' ),
						'slug'     => 'manage-members',
						'position' => 30,
					),
					$default_params
				);

				if ( 'private' == $this->current_group->status ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Requests', 'buddyboss' ),
							'slug'     => 'membership-requests',
							'position' => 40,
						),
						$default_params
					);
				}

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Delete', 'buddyboss' ),
						'slug'     => 'delete-group',
						'position' => 1000,
					),
					$default_params
				);
			}

			foreach ( $sub_nav as $nav ) {
				bp_core_new_subnav_item( $nav, 'groups' );
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

			/** This action is documented in bp-groups/bp-groups-loader.php */
			do_action( 'groups_setup_nav' );
		}
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$groups_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() );

			// Pending group invites.
			$count   = groups_get_invite_count_for_user();
			$title   = __( 'Groups', 'buddyboss' );
			$pending = __( 'No Pending Invites', 'buddyboss' );

			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Group invitation count for the current user */
					__( 'Groups %s', 'buddyboss' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);

				$pending = sprintf(
					/* translators: %s: Group invitation count for the current user */
					__( 'Pending Invites %s', 'buddyboss' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $groups_link,
			);

			// My Groups.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-memberships',
				'title'    => __( 'My Groups', 'buddyboss' ),
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
			if ( bp_user_can_create_groups() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-create',
					'title'    => __( 'Create Group', 'buddyboss' ),
					'href'     => trailingslashit( bp_get_groups_directory_permalink() . 'create' ),
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

		if ( bp_is_groups_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() && ! bp_is_single_item() ) {
				$bp->bp_options_title = __( 'My Groups', 'buddyboss' );

			} elseif ( ! bp_is_my_profile() && ! bp_is_single_item() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => bp_displayed_user_id(),
						'type'    => 'thumb',
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bp_get_displayed_user_fullname();

				// We are viewing a single group, so set up the
				// group navigation menu using the $this->current_group global.
			} elseif ( bp_is_single_item() ) {
				$bp->bp_options_title  = $this->current_group->name;
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id'    => $this->current_group->id,
						'object'     => 'group',
						'type'       => 'thumb',
						'avatar_dir' => 'group-avatars',
						'alt'        => __( 'Group Profile Photo', 'buddyboss' ),
					)
				);

				if ( empty( $bp->bp_options_avatar ) ) {
					$bp->bp_options_avatar = '<img src="' . esc_url( bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group', 'type' => 'thumb' ) ) ) . '" alt="' . esc_attr__( 'No Group Profile Photo', 'buddyboss' ) . '" class="avatar" />';
				}
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress 2.2.0
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
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BP_REST_Groups_Endpoint',
			'BP_REST_Groups_Details_Endpoint',
			'BP_REST_Group_Membership_Endpoint',
			'BP_REST_Group_Settings_Endpoint',
			'BP_REST_Group_Invites_Endpoint',
			'BP_REST_Group_Membership_Request_Endpoint',
			'BP_REST_Groups_Types_Endpoint',
			'BP_REST_Attachments_Group_Avatar_Endpoint',
		);

		// Support to Group Cover.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$controllers[] = 'BP_REST_Attachments_Group_Cover_Endpoint';
		}

		parent::rest_api_init( $controllers );
	}
}
