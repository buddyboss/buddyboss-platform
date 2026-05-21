<?php
/**
 * BuddyBoss Admin Tools AJAX Handler
 *
 * AJAX handlers for the Settings 2.0 Tools area (Default Data, Repair
 * Platform, Import Content). Each handler wraps the existing legacy
 * functionality (`bp_admin_tools_default_data_save()`, the repair
 * callbacks registered via the `bp_repair_list` filter, the bbPress
 * `BBP_Converter` pipeline) so this migration is a pure UI swap — the
 * legacy backend logic is unchanged and remains the source of truth.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Tools_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Tools_Ajax {

	/**
	 * Nonce action — reused for every tools endpoint so the React
	 * Tools shell only needs to localize one nonce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_tools';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_action( 'wp_ajax_bb_admin_tools_get_panel_data', array( $this, 'get_panel_data' ) );
		add_action( 'wp_ajax_bb_admin_tools_default_data_run', array( $this, 'default_data_run' ) );
		add_action( 'wp_ajax_bb_admin_tools_default_data_clear', array( $this, 'default_data_clear' ) );
		add_action( 'wp_ajax_bb_admin_tools_get_repair_list', array( $this, 'get_repair_list' ) );
		add_action( 'wp_ajax_bb_admin_tools_get_import_content_data', array( $this, 'get_import_content_data' ) );
		add_action( 'wp_ajax_bb_admin_tools_import_profile_types', array( $this, 'import_profile_types' ) );
		add_action( 'wp_ajax_bb_admin_tools_get_converter_data', array( $this, 'get_converter_data' ) );
		add_action( 'wp_ajax_bb_admin_tools_save_converter_settings', array( $this, 'save_converter_settings' ) );
		add_action( 'wp_ajax_bb_admin_tools_run_bbp_repair', array( $this, 'run_bbp_repair' ) );
	}

	/**
	 * Shared capability + nonce check.
	 *
	 * Capability check runs before nonce check per project convention
	 * (a logged-out attacker shouldn't even see the nonce-mismatch
	 * response — they get a generic permission denial instead).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void Sends a JSON error response and exits on failure.
	 */
	private function verify_request() {
		$cap = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'buddyboss' ) ),
				403
			);
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'buddyboss' ) ),
				403
			);
		}
	}

	/**
	 * Return the panel state the React Tools shell needs on open.
	 *
	 * Lists which Default Data categories have already been imported
	 * (driven by `bp_dd_is_imported()`) so the React panel can disable
	 * the matching checkboxes — same UX as the legacy admin tools form,
	 * just rendered by React instead of PHP.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_panel_data() {
		$this->verify_request();

		require_once buddypress()->plugin_dir . 'bp-core/bp-core-tools-default-data.php';

		// Match the legacy form's checkbox set. Each entry mirrors a
		// `bp_dd_is_imported( $category, $subtype )` pair used by the
		// legacy `bp_admin_tools_default_data_save()`.
		$default_data_state = array(
			'import-users'      => bp_dd_is_imported( 'users', 'users' ),
			'import-profile'    => bp_dd_is_imported( 'users', 'xprofile' ),
			'import-friends'    => bp_dd_is_imported( 'users', 'friends' ),
			'import-activity'   => bp_dd_is_imported( 'users', 'activity' ),
			'import-messages'   => bp_dd_is_imported( 'users', 'messages' ),
			'import-groups'     => bp_dd_is_imported( 'groups', 'groups' ),
			'import-g-members'  => bp_dd_is_imported( 'groups', 'members' ),
			'import-g-activity' => bp_dd_is_imported( 'groups', 'activity' ),
			'import-g-forums'   => bp_dd_is_imported( 'groups', 'forums' ),
			'import-forums'     => bp_dd_is_imported( 'forums', 'forums' ),
			'import-f-topics'   => bp_dd_is_imported( 'forums', 'topics' ),
			'import-f-replies'  => bp_dd_is_imported( 'forums', 'replies' ),
		);

		wp_send_json_success(
			array(
				'default_data' => array(
					'imported'      => $default_data_state,
					'components'    => array(
						'xprofile' => bp_is_active( 'xprofile' ),
						'friends'  => bp_is_active( 'friends' ),
						'activity' => bp_is_active( 'activity' ),
						'messages' => bp_is_active( 'messages' ),
						'groups'   => bp_is_active( 'groups' ),
						'forums'   => bp_is_active( 'forums' ),
					),
				),
			)
		);
	}

	/**
	 * Run the Default Data import for the selected categories.
	 *
	 * Builds the same `$_POST` shape the legacy
	 * `bp_admin_tools_default_data_save()` expects, then invokes the
	 * legacy function under output buffering. Returns the per-category
	 * count summary the legacy function already produces.
	 *
	 * Backend unchanged — this is a pure adapter that lets the React
	 * panel reuse the legacy pipeline verbatim.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function default_data_run() {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_request().
		$raw_types = isset( $_POST['types'] ) ? wp_unslash( $_POST['types'] ) : array();
		if ( ! is_array( $raw_types ) ) {
			$raw_types = array();
		}

		$allowed_types = array(
			'import-users',
			'import-profile',
			'import-friends',
			'import-activity',
			'import-messages',
			'import-groups',
			'import-g-members',
			'import-g-activity',
			'import-g-forums',
			'import-forums',
			'import-f-topics',
			'import-f-replies',
		);

		$selected = array();
		foreach ( $raw_types as $type ) {
			$type = sanitize_key( $type );
			if ( in_array( $type, $allowed_types, true ) ) {
				$selected[ $type ] = '1';
			}
		}

		if ( empty( $selected ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No data categories were selected.', 'buddyboss' ) ),
				400
			);
		}

		// Auto-include the parent-section toggles when any child sub-item
		// is selected. The legacy form rendered "Members" / "Groups" /
		// "Forums" parent checkboxes that admins had to tick alongside
		// nested items because the legacy import functions depend on the
		// parent's data being seeded first (e.g. `bp_dd_import_users_profile`
		// reads user IDs from `bp_dd_import_users`'s output, group activity
		// uses group IDs from group import, etc.). The Figma design dropped
		// those parent toggles to keep the form clean — we re-instate the
		// dependency here so the legacy `bp_admin_tools_default_data_save()`
		// pipeline runs the same as if the admin had ticked them manually.
		//
		// Safe to set even when the parent is already imported:
		// `bp_admin_tools_default_data_save()` guards each branch with
		// `! bp_dd_is_imported( ... )` so re-including a completed parent
		// is a no-op.
		$members_children = array( 'import-profile', 'import-friends', 'import-activity', 'import-messages' );
		$groups_children  = array( 'import-g-members', 'import-g-activity', 'import-g-forums' );
		$forums_children  = array( 'import-f-topics', 'import-f-replies' );

		if ( ! isset( $selected['import-users'] ) ) {
			foreach ( $members_children as $child ) {
				if ( isset( $selected[ $child ] ) ) {
					$selected['import-users'] = '1';
					break;
				}
			}
		}

		if ( ! isset( $selected['import-groups'] ) ) {
			foreach ( $groups_children as $child ) {
				if ( isset( $selected[ $child ] ) ) {
					$selected['import-groups'] = '1';
					break;
				}
			}
		}

		if ( ! isset( $selected['import-forums'] ) ) {
			foreach ( $forums_children as $child ) {
				if ( isset( $selected[ $child ] ) ) {
					$selected['import-forums'] = '1';
					break;
				}
			}
		}

		require_once buddypress()->plugin_dir . 'bp-core/bp-core-tools-default-data.php';
		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-functions.php';
		if ( ! defined( 'BP_DEFAULT_DATA_DIR' ) ) {
			// Loaded by `bp_core_admin_tools()` page callback on the legacy
			// route; we're bypassing that callback so define the constant
			// the helpers below expect, then load the users seed data.
			define( 'BP_DEFAULT_DATA_DIR', buddypress()->plugin_dir . 'bp-core/' );
		}
		if ( file_exists( BP_DEFAULT_DATA_DIR . 'data/users.php' ) ) {
			require_once BP_DEFAULT_DATA_DIR . 'data/users.php';
		}

		// Impersonate the legacy form POST so the existing function runs
		// unchanged. `check_admin_referer` reads `$_POST['_wpnonce']`, so we
		// generate a fresh nonce keyed to the legacy action; the AJAX
		// request itself has already been authenticated via verify_request().
		$_POST['_wpnonce']         = wp_create_nonce( 'bp-admin-tools-default-data' );
		$_POST['bp-admin-submit']  = '1';
		$_POST['bp']               = $selected;

		ob_start();
		bp_admin_tools_default_data_save();
		$html = trim( ob_get_clean() );

		wp_send_json_success(
			array(
				'message' => __( 'Selected data imported successfully.', 'buddyboss' ),
				// Legacy HTML output (count summary) — kept for parity with
				// the legacy admin notice. React renders a structured toast
				// and currently doesn't parse this; included so a future
				// "show legacy details" UX can opt in without backend changes.
				'html'    => $html,
			)
		);
	}

	/**
	 * Return the categorized repair-tool list for the React Repair
	 * Platform panel.
	 *
	 * Reads `bp_admin_repair_list()` (which already applies the
	 * `bp_repair_list` filter that Pro and TutorLMS hook into) and
	 * groups each entry into one of the Figma's five categories using
	 * `bb_admin_tools_repair_slug_categories`. Items not in the map land
	 * in an "other" bucket — never dropped — so third-party items
	 * registered via the `bp_repair_list` filter (e.g. TutorLMS's
	 * `bb_migrate_tutor_group_course`) always show up in the UI.
	 *
	 * Execution endpoint is the existing
	 * `wp_ajax_bp_admin_repair_tools_wrapper_function` AJAX action —
	 * unchanged. React calls it directly using `bbAdminData.repairNonce`
	 * (the legacy `bp-do-counts` nonce).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_repair_list() {
		$this->verify_request();

		// Load the legacy admin-tools file that defines
		// `bp_admin_repair_list()`. The function lives in the admin-tools
		// page callback file, which isn't autoloaded — only included when
		// the legacy `bp-tools` page is rendered or when an AJAX call hits
		// `bp_admin_repair_tools_wrapper_function` (which require_once's
		// the same file). We need the same include here so the function
		// resolves on the React side.
		if ( ! function_exists( 'bp_admin_repair_list' ) ) {
			$tools_file = buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php';
			if ( file_exists( $tools_file ) ) {
				require_once $tools_file;
			}
		}

		if ( ! function_exists( 'bp_admin_repair_list' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Repair tools are unavailable on this site.', 'buddyboss' ) ),
				500
			);
		}

		// `bp_admin_repair_list()` already runs `apply_filters(
		// 'bp_repair_list', ... )` internally — third-party items added
		// via that filter (TutorLMS migration, Pro repairs, etc.) come
		// through automatically.
		$raw_list = bp_admin_repair_list();

		// Slug → category map. Pro and third-party plugins register
		// additional items via the `bp_repair_list` filter — slugs
		// outside this map fall through to a pattern-based fallback
		// below, then to 'other' if nothing matches. The patterns
		// catch the common naming conventions (media-*, video-*,
		// message-*, etc.) so third-party items auto-categorize.
		$slug_categories = array(
			// Members & Profiles
			'bp-total-member-count'                          => 'members_profiles',
			'bp-last-activity'                               => 'members_profiles',
			'bp-xprofile-fields'                             => 'members_profiles',
			'bp-xprofile-wordpress-resync'                   => 'members_profiles',
			'bp-wordpress-xprofile-resync'                   => 'members_profiles',
			'bp-wordpress-update-display-name'               => 'members_profiles',
			'bp-assign-member-type'                          => 'members_profiles',
			'bp-sync-profile-completion-widget'              => 'members_profiles',
			'bp-repair-repeater-field-sets'                  => 'members_profiles',
			'bp-repair-nicknames'                            => 'members_profiles',
			'bp-member-profile-links'                        => 'members_profiles',
			'bp-migrate-xprofile-visibility'                 => 'members_profiles',
			'bp-repair-user-nicknames'                       => 'members_profiles',

			// Groups
			'bp-group-count'                                 => 'groups',
			'bp-group-members-count'                         => 'groups',
			'bp-invitations-table'                           => 'groups',
			'bp-migrate-group-forum-subscriptions'           => 'groups',
			'bp-migrate-group-forum-discussion-subscription' => 'groups',

			// Connections
			'bp-user-friends'                                => 'connections',

			// Activity & Reactions
			'bp-sync-activity-favourite'                     => 'activity_reactions',

			// Emails
			'bp-missing-emails'                              => 'emails',
			'bp-reinstall-emails'                            => 'emails',

			// Messages
			'bp-message-media'                               => 'messages',
			'bp-repair-messages-media'                       => 'messages',
			'bp-unread-message-count'                        => 'messages',
			'bp-repair-unread-messages-count'                => 'messages',

			// Media (covers photos / videos / documents — the three
			// media types share storage & privacy logic in Platform).
			'bp-repair-media'                                => 'media',
			'bp-repair-videos'                               => 'media',
			'bp-repair-documents'                            => 'media',
			'bp-media-records'                               => 'media',
			'bp-video-records'                               => 'media',
			'bp-document-records'                            => 'media',

			// Moderation
			'bp-repair-moderation-data'                      => 'moderation',
			'bb-repair-moderation-data'                      => 'moderation',

			// Forums-specific privacy repairs (forum video / media
			// privacy run forum-side, so bucket them in Forums even
			// though their data is media — same scope the legacy
			// Repair Forums page covered).
			'bp-forum-video-privacy'                         => 'forums_discussions',
			'bp-forum-media-privacy'                         => 'forums_discussions',

			// Other (kept visible but bucketed separately):
			//   bp-blog-records
		);

		/**
		 * Filters the slug → category mapping used by the React Repair
		 * Platform panel to bucket repair items.
		 *
		 * Third-party plugins adding items via `bp_repair_list` can use
		 * this filter to place their items into a specific category
		 * instead of the default "other" bucket.
		 *
		 * Allowed category keys: `members_profiles`, `groups`,
		 * `forums_discussions`, `connections`, `activity_reactions`,
		 * `other`. Unknown values fall back to `other`.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $slug_categories Map of repair slug => category key.
		 */
		$slug_categories = apply_filters( 'bb_admin_tools_repair_slug_categories', $slug_categories );

		$category_labels = array(
			'members_profiles'   => __( 'Members & Profiles', 'buddyboss' ),
			'groups'             => __( 'Groups', 'buddyboss' ),
			'forums_discussions' => __( 'Forums & Discussions', 'buddyboss' ),
			'connections'        => __( 'Connections', 'buddyboss' ),
			'activity_reactions' => __( 'Activity & Reactions', 'buddyboss' ),
			'media'              => __( 'Media', 'buddyboss' ),
			'messages'           => __( 'Messages', 'buddyboss' ),
			'emails'             => __( 'Emails', 'buddyboss' ),
			'moderation'         => __( 'Moderation', 'buddyboss' ),
			'other'              => __( 'Other', 'buddyboss' ),
		);
		$valid_categories = array_keys( $category_labels );

		// Substring-based fallback rules — apply in declaration order
		// when a slug isn't in the explicit map above. Lets third-party
		// items auto-categorize without us having to know their exact
		// slug. Forum-specific privacy repairs come first so a slug
		// like `bp-forum-video-privacy` lands in `forums_discussions`
		// instead of the broader `media` bucket.
		$fallback_patterns = array(
			// Order matters — first match wins.
			array( 'pattern' => 'forum-video',           'category' => 'forums_discussions' ),
			array( 'pattern' => 'forum-media',           'category' => 'forums_discussions' ),
			array( 'pattern' => 'forum-document',        'category' => 'forums_discussions' ),
			array( 'pattern' => 'bbp-',                  'category' => 'forums_discussions' ),
			array( 'pattern' => '-forum',                'category' => 'forums_discussions' ),
			array( 'pattern' => '-topic',                'category' => 'forums_discussions' ),
			array( 'pattern' => '-reply',                'category' => 'forums_discussions' ),
			array( 'pattern' => 'message',               'category' => 'messages' ),
			array( 'pattern' => 'email',                 'category' => 'emails' ),
			array( 'pattern' => 'moderation',            'category' => 'moderation' ),
			array( 'pattern' => 'media',                 'category' => 'media' ),
			array( 'pattern' => 'video',                 'category' => 'media' ),
			array( 'pattern' => 'document',              'category' => 'media' ),
			array( 'pattern' => 'group',                 'category' => 'groups' ),
			array( 'pattern' => 'invitation',            'category' => 'groups' ),
			array( 'pattern' => 'friend',                'category' => 'connections' ),
			array( 'pattern' => 'activity',              'category' => 'activity_reactions' ),
			array( 'pattern' => 'reaction',              'category' => 'activity_reactions' ),
			array( 'pattern' => 'xprofile',              'category' => 'members_profiles' ),
			array( 'pattern' => 'member-type',           'category' => 'members_profiles' ),
			array( 'pattern' => 'member',                'category' => 'members_profiles' ),
			array( 'pattern' => 'profile',               'category' => 'members_profiles' ),
			array( 'pattern' => 'nickname',              'category' => 'members_profiles' ),
			array( 'pattern' => 'user-',                 'category' => 'members_profiles' ),
		);

		$categorized = array_fill_keys( $valid_categories, array() );

		foreach ( $raw_list as $priority => $item ) {
			if ( ! is_array( $item ) || empty( $item[0] ) || empty( $item[1] ) ) {
				continue;
			}
			$slug     = (string) $item[0];
			$category = $this->categorize_repair_slug( $slug, $slug_categories, $fallback_patterns, $valid_categories );
			$categorized[ $category ][] = array(
				'id'       => $slug,
				'label'    => (string) $item[1],
				'priority' => (int) $priority,
				// `source` tells React which tick endpoint to use:
				// `bp` items are paginated through the legacy
				// `bp_admin_repair_tools_wrapper_function` AJAX, while
				// `bbp` items run single-shot through our
				// `bb_admin_tools_run_bbp_repair` wrapper (because
				// bbPress callbacks return `array( $status, $msg )`
				// immediately, no batch/offset contract).
				'source'   => 'bp',
				// 4th element of the legacy list is an optional
				// disabled-flag (e.g. `bp-missing-emails` vs
				// `bp-reinstall-emails` mutually exclude each other on
				// the legacy page). Pass it through so React renders the
				// matching state.
				'disabled' => isset( $item[3] ) ? (bool) $item[3] : false,
			);
		}

		// Merge bbPress's separate forum-repair list into the
		// `forums_discussions` bucket. The legacy admin renders these on
		// a different page (`?page=bbp-repair`) with its own form
		// handler — Settings 2.0 unifies them under one panel so admins
		// don't have to context-switch between two repair tools.
		if ( bp_is_active( 'forums' ) && function_exists( 'bbp_admin_repair_list' ) ) {
			$bbp_list = bbp_admin_repair_list();
			foreach ( $bbp_list as $priority => $item ) {
				if ( ! is_array( $item ) || empty( $item[0] ) || empty( $item[1] ) ) {
					continue;
				}
				// Offset bbPress priorities so they sort AFTER any
				// `bp_admin_repair_list` items that happened to land
				// in `forums_discussions` via the slug-category map.
				$categorized['forums_discussions'][] = array(
					'id'       => (string) $item[0],
					'label'    => (string) $item[1],
					'priority' => 1000 + (int) $priority,
					'source'   => 'bbp',
					'disabled' => isset( $item[3] ) ? (bool) $item[3] : false,
				);
			}
		}

		// Build the response in the order categories appear in the
		// Figma. Empty categories are omitted so the panel doesn't
		// render an empty "Forums & Discussions" header on sites
		// without third-party forum-repair items.
		$response_categories = array();
		foreach ( $valid_categories as $cat_key ) {
			if ( empty( $categorized[ $cat_key ] ) ) {
				continue;
			}
			// Sort within a category by the legacy list's priority
			// (numeric key from `$repair_list[N]`) so the order matches
			// how the legacy admin tools page renders them.
			usort(
				$categorized[ $cat_key ],
				function ( $a, $b ) {
					return $a['priority'] - $b['priority'];
				}
			);
			$response_categories[] = array(
				'id'    => $cat_key,
				'label' => $category_labels[ $cat_key ],
				'items' => $categorized[ $cat_key ],
			);
		}

		wp_send_json_success(
			array(
				'categories' => $response_categories,
			)
		);
	}

	/**
	 * Return the panel state the React Import Content shell needs on open.
	 *
	 * Reports availability of each sub-panel — Profile Types is gated on
	 * the members component being active and the legacy
	 * `bp_member_type_import_submenu_page` helper functions being loaded;
	 * Forums import is gated on the forums component being active so
	 * `BBP_Converter` is present.
	 *
	 * For Profile Types the response also reports how many registered
	 * member types are not yet imported as BuddyBoss `bp-member-type`
	 * posts — same delta the legacy import-submenu callback computed via
	 * `bp_get_member_types() - bp_get_active_member_types()`. Lets the
	 * React panel disable the "Run Migration" button when there's nothing
	 * to import and surface an empty-state message.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_import_content_data() {
		$this->verify_request();

		$response = array(
			'profile_types' => array(
				'available'     => false,
				'pending_count' => 0,
				'pending_names' => array(),
			),
			'forums'        => array(
				'available' => false,
			),
			'media'         => array(
				'available'      => false,
				'tables_exist'   => false,
				'legacy_url'     => '',
			),
		);

		// Profile Types — requires members component. Members is a
		// required core component in BuddyPress, but the guard handles
		// edge cases (component filtered off, function not loaded).
		if (
			bp_is_active( 'members' ) &&
			function_exists( 'bp_get_member_types' ) &&
			function_exists( 'bp_get_active_member_types' ) &&
			function_exists( 'bp_get_member_type_key' )
		) {
			$response['profile_types']['available'] = true;

			$pending = $this->profile_types_pending_list();
			$response['profile_types']['pending_count'] = count( $pending );
			$response['profile_types']['pending_names'] = array_values( $pending );
		}

		// Forums converter — requires forums component (loads `BBP_Converter`).
		$response['forums']['available'] = bp_is_active( 'forums' );

		// Media importer — migrates data from the discontinued standalone
		// "BuddyBoss Media" plugin (tables `*buddyboss_media` and
		// `*buddyboss_media_albums`) into the bp-media component. Section
		// is only meaningful when bp-media is active; we additionally
		// report whether the legacy tables exist so the React panel can
		// render the matching empty-state or "tables found" copy from
		// the Figma.
		if ( bp_is_active( 'media' ) ) {
			$response['media']['available']    = true;
			$response['media']['tables_exist'] = $this->buddyboss_media_legacy_tables_exist();
			$response['media']['legacy_url']   = esc_url_raw(
				bp_get_admin_url( add_query_arg( array( 'page' => 'bp-media-import' ), 'admin.php' ) )
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Check whether the legacy "BuddyBoss Media" plugin tables exist.
	 *
	 * Mirrors the SHOW TABLES probe in `bp_media_import_submenu_page()`
	 * (`bp-media/bp-media-filters.php:1322`). When both tables are
	 * present the site is a candidate for the legacy media-import
	 * wizard; when either is missing there's nothing to migrate and
	 * the React panel can render the "nothing to import" empty state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True when both legacy tables exist.
	 */
	private function buddyboss_media_legacy_tables_exist() {
		global $wpdb, $bp;

		if ( empty( $bp ) || empty( $bp->table_prefix ) ) {
			return false;
		}

		$media_table  = $bp->table_prefix . 'buddyboss_media';
		$albums_table = $bp->table_prefix . 'buddyboss_media_albums';

		$media_check = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $media_table )
		);
		if ( empty( $media_check ) ) {
			return false;
		}

		$albums_check = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $albums_table )
		);
		return ! empty( $albums_check );
	}

	/**
	 * Compute the set of registered member types not yet imported as
	 * BuddyBoss `bp-member-type` posts.
	 *
	 * Mirrors the diff that the legacy
	 * `bp_member_type_import_submenu_page()` form-processing branch did:
	 *  `bp_get_member_types() - keys(bp_get_active_member_types())`.
	 * The result drives both the panel-open state and the run-import
	 * loop so both code paths see the same source-of-truth list.
	 *
	 * Multisite: legacy code switches to the root blog before reading;
	 * we mirror that switch and restore on exit so member types defined
	 * on the root blog are visible even from a sub-site's admin.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Member-type slugs (string) yet to be imported.
	 */
	private function profile_types_pending_list() {
		$switched = false;
		if ( is_multisite() && function_exists( 'bp_is_network_activated' ) && bp_is_network_activated() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched = true;
		}

		$registered = (array) bp_get_member_types();
		$active     = (array) bp_get_active_member_types();
		$active_keys = array();

		foreach ( $active as $active_post ) {
			$key = bp_get_member_type_key( $active_post );
			if ( ! empty( $key ) ) {
				$active_keys[] = $key;
			}
		}

		$pending = array_diff( $registered, $active_keys );

		if ( $switched ) {
			restore_current_blog();
		}

		return $pending;
	}

	/**
	 * Run the Profile Types import.
	 *
	 * Replicates the legacy form-processing branch inside
	 * `bp_member_type_import_submenu_page()` verbatim — same diff
	 * computation, same `wp_insert_post()` call shape with `post_type =
	 * bp_get_member_type_post_type()`, same post-meta keys
	 * (`_bp_member_type_key`, `_bp_member_type_label_name`,
	 * `_bp_member_type_label_singular_name`), and the same uniqueness
	 * fallback (3-digit random suffix when the slug collides with an
	 * existing taxonomy term). Multisite switching to the root blog is
	 * preserved.
	 *
	 * Returns the imported count + any skipped names so the React panel
	 * can render a precise success toast ("Imported 3 of 5 profile
	 * types; 2 were already imported.").
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function import_profile_types() {
		$this->verify_request();

		if (
			! bp_is_active( 'members' ) ||
			! function_exists( 'bp_get_member_types' ) ||
			! function_exists( 'bp_get_active_member_types' ) ||
			! function_exists( 'bp_get_member_type_post_type' ) ||
			! function_exists( 'bp_get_member_type_tax_name' )
		) {
			wp_send_json_error(
				array( 'message' => __( 'Profile types are unavailable on this site.', 'buddyboss' ) ),
				400
			);
		}

		$pending = $this->profile_types_pending_list();

		if ( empty( $pending ) ) {
			wp_send_json_success(
				array(
					'imported_count' => 0,
					'message'        => __( 'Nothing to import — all registered profile types are already imported.', 'buddyboss' ),
				)
			);
		}

		$switched = false;
		if ( is_multisite() && function_exists( 'bp_is_network_activated' ) && bp_is_network_activated() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched = true;
		}

		$imported_count = 0;
		foreach ( $pending as $import_types_data ) {
			$sing_name = ucfirst( $import_types_data );

			$post_id = wp_insert_post(
				array(
					'post_type'   => bp_get_member_type_post_type(),
					'post_title'  => $sing_name,
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				$key  = get_post_field( 'post_name', $post_id );
				$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );

				// If the slug derived from the post title collides with
				// an existing term, append a 3-digit random suffix —
				// same uniqueness strategy the legacy form used.
				if ( 0 !== $term && null !== $term ) {
					$digits = 3;
					$unique = wp_rand( pow( 10, $digits - 1 ), pow( 10, $digits ) - 1 );
					$key    = $key . $unique;
				}

				update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) );
				update_post_meta( $post_id, '_bp_member_type_label_name', $sing_name );
				update_post_meta( $post_id, '_bp_member_type_label_singular_name', $sing_name );

				$imported_count++;
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}

		wp_send_json_success(
			array(
				'imported_count' => $imported_count,
				'message'        => sprintf(
					/* translators: %d: number of profile types imported. */
					_n(
						'Imported %d profile type successfully.',
						'Imported %d profile types successfully.',
						$imported_count,
						'buddyboss'
					),
					$imported_count
				),
			)
		);
	}

	/**
	 * Return the data the React Forums Converter section needs to render.
	 *
	 * Reports the list of available source-forum platforms (each `*.php`
	 * file under `bp-forums/admin/converters/` is one platform), the
	 * current values of every `_bbp_converter_*` option, and the current
	 * pipeline step + progress. Lets React rehydrate state if the admin
	 * left the page mid-import and came back later — same persistent
	 * state model the legacy converter UI used.
	 *
	 * Backend behaviour is unchanged: option keys, sanitize callbacks,
	 * default values, and the converter-discovery glob all live in
	 * `BBP_Converter` — this method only reads them.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function get_converter_data() {
		$this->verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Forums import is unavailable on this site.', 'buddyboss' ) ),
				400
			);
		}

		$platforms = $this->list_converter_platforms();

		// Step total — keep in sync with `BBP_Converter::$steps` (17 steps
		// in the current `setup_globals()`). Hard-coded here because the
		// `$steps` property is private. The legacy localizer reads
		// `step_percent` / `total_percent` directly from the running
		// converter instance after a tick — we surface the option-stored
		// step count instead so React can hydrate without instantiating
		// the converter just to read state.
		$current_step  = (int) get_option( '_bbp_converter_step', 0 );
		$total_steps   = 17;
		$total_percent = ( $current_step > 0 ) ? min( 100, round( ( $current_step / $total_steps ) * 100 ) ) : 0;

		wp_send_json_success(
			array(
				'platforms' => $platforms,
				'options'   => array(
					'platform'      => get_option( '_bbp_converter_platform', '' ),
					'db_server'     => get_option( '_bbp_converter_db_server', 'localhost' ),
					'db_port'       => (int) get_option( '_bbp_converter_db_port', 3306 ),
					'db_name'       => get_option( '_bbp_converter_db_name', '' ),
					'db_user'       => get_option( '_bbp_converter_db_user', '' ),
					// Password is intentionally NOT returned — keep it
					// server-side-only. The legacy form did expose it
					// (pre-filled in a password field) but echoing it
					// back to the browser on every panel-open expands
					// the breach window for the existing plaintext
					// option-storage gap. React renders an empty
					// password field; admins re-enter on each session.
					// `save_converter_settings` only overwrites the
					// stored value when the field arrives non-empty,
					// so leaving it blank preserves the prior value.
					'db_pass'       => '',
					'db_prefix'     => get_option( '_bbp_converter_db_prefix', '' ),
					'rows'          => (int) get_option( '_bbp_converter_rows', 100 ),
					'delay_time'    => (int) get_option( '_bbp_converter_delay_time', 1 ),
					'convert_users' => (int) get_option( '_bbp_converter_convert_users', 0 ),
					'restart'       => (int) get_option( '_bbp_converter_restart', 0 ),
					'clean'         => (int) get_option( '_bbp_converter_clean', 0 ),
				),
				'state'     => array(
					'step'          => $current_step,
					'total_steps'   => $total_steps,
					'total_percent' => $total_percent,
					'started'       => $current_step > 0,
				),
			)
		);
	}

	/**
	 * Discover available converter platforms.
	 *
	 * Mirrors `BBP_Converter`'s own discovery (glob of `*.php` files
	 * under `bp-forums/admin/converters/` — each filename is a platform
	 * label). Excludes `Example.php` (template stub for new converters,
	 * not a real platform).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array List of `{ value, label }` pairs sorted by label.
	 */
	private function list_converter_platforms() {
		$dir = buddypress()->plugin_dir . 'bp-forums/admin/converters';
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$files = glob( $dir . '/*.php' );
		if ( empty( $files ) ) {
			return array();
		}

		$platforms = array();
		foreach ( $files as $file ) {
			$name = basename( $file, '.php' );
			if ( 'Example' === $name ) {
				continue;
			}
			$platforms[] = array(
				'value' => $name,
				'label' => $name,
			);
		}

		usort(
			$platforms,
			function ( $a, $b ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $platforms;
	}

	/**
	 * Persist the React converter form to the `_bbp_converter_*` options.
	 *
	 * Same option keys + same sanitize callbacks the legacy
	 * `register_setting()` calls in `BBP_Converter::register_admin_settings()`
	 * used — every value passes through `sanitize_text_field` or `intval`
	 * exactly as the WP Settings API would have on the legacy admin page.
	 * Password is only overwritten when the field arrives non-empty so a
	 * panel-reload after a successful save doesn't blank it.
	 *
	 * Backend converter behaviour is unchanged: `BBP_Converter::process_callback()`
	 * still reads these options via `setup_options()`/`maybe_update_options()`
	 * on every AJAX tick.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function save_converter_settings() {
		$this->verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Forums import is unavailable on this site.', 'buddyboss' ) ),
				400
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$post = wp_unslash( $_POST );

		$text_field = function ( $key ) use ( $post ) {
			return isset( $post[ $key ] ) ? sanitize_text_field( (string) $post[ $key ] ) : '';
		};
		$int_field = function ( $key, $default = 0 ) use ( $post ) {
			return isset( $post[ $key ] ) ? intval( $post[ $key ] ) : $default;
		};
		$bool_field = function ( $key ) use ( $post ) {
			return isset( $post[ $key ] ) && ! empty( $post[ $key ] ) ? 1 : 0;
		};

		update_option( '_bbp_converter_platform', $text_field( 'platform' ) );
		update_option( '_bbp_converter_db_server', $text_field( 'db_server' ) );
		update_option( '_bbp_converter_db_port', $int_field( 'db_port', 3306 ) );
		update_option( '_bbp_converter_db_name', $text_field( 'db_name' ) );
		update_option( '_bbp_converter_db_user', $text_field( 'db_user' ) );

		// Only overwrite the stored password when the React form
		// supplies a non-empty value — keeps the prior credential
		// usable after the admin re-opens the panel (the GET endpoint
		// intentionally never returns the stored password).
		$db_pass = $text_field( 'db_pass' );
		if ( '' !== $db_pass ) {
			update_option( '_bbp_converter_db_pass', $db_pass );
		}

		update_option( '_bbp_converter_db_prefix', $text_field( 'db_prefix' ) );
		update_option( '_bbp_converter_rows', $int_field( 'rows', 100 ) );
		update_option( '_bbp_converter_delay_time', $int_field( 'delay_time', 1 ) );
		update_option( '_bbp_converter_convert_users', $bool_field( 'convert_users' ) );
		update_option( '_bbp_converter_restart', $bool_field( 'restart' ) );
		update_option( '_bbp_converter_clean', $bool_field( 'clean' ) );

		wp_send_json_success(
			array( 'message' => __( 'Forum import settings saved.', 'buddyboss' ) )
		);
	}

	/**
	 * Resolve a repair-slug to one of the Repair Platform categories.
	 *
	 * Two-layer resolution:
	 *  1. Explicit slug → category map for items we ship (high
	 *     precision, exact-match).
	 *  2. Substring pattern fallback for items registered by Pro or
	 *     third-party plugins via the `bp_repair_list` filter — lets
	 *     unknown items auto-bucket by naming convention (e.g. a slug
	 *     containing "message" lands in messages, "media" in media).
	 *
	 * Falls back to `'other'` so a slug that matches nothing is still
	 * visible (never silently dropped). Returned value is always a key
	 * present in `$valid_categories`.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $slug            Repair slug to categorize.
	 * @param array  $explicit_map    Slug → category map (exact match).
	 * @param array  $fallbacks       Ordered list of `{ pattern, category }` rules.
	 * @param array  $valid_categories Allow-list of category keys.
	 * @return string Category key.
	 */
	private function categorize_repair_slug( $slug, $explicit_map, $fallbacks, $valid_categories ) {
		if ( isset( $explicit_map[ $slug ] ) && in_array( $explicit_map[ $slug ], $valid_categories, true ) ) {
			return $explicit_map[ $slug ];
		}
		foreach ( $fallbacks as $rule ) {
			if (
				! empty( $rule['pattern'] ) &&
				! empty( $rule['category'] ) &&
				in_array( $rule['category'], $valid_categories, true ) &&
				false !== strpos( $slug, $rule['pattern'] )
			) {
				return $rule['category'];
			}
		}
		return 'other';
	}

	/**
	 * Run a single bbPress repair callback (from `bbp_admin_repair_list()`).
	 *
	 * bbPress's forum-repair callbacks return `array( $status_code,
	 * $message )` immediately — no batch/offset contract like the BP
	 * repair callbacks — so this wrapper is a single-shot endpoint
	 * instead of a polled one. `$status_code === 0` is success, anything
	 * else is failure. We resolve the slug against the legacy
	 * `bbp_admin_repair_list()` so any item added via the `bbp_repair_list`
	 * filter (third-party forum plugins) is reachable from the React UI.
	 *
	 * Backend behaviour is unchanged: the same callable the legacy
	 * `bbp_admin_repair_handler()` invokes runs here too, with no edits
	 * to the bbPress callbacks themselves.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function run_bbp_repair() {
		$this->verify_request();

		if ( ! bp_is_active( 'forums' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Forum repair tools are unavailable on this site.', 'buddyboss' ) ),
				400
			);
		}

		if ( ! function_exists( 'bbp_admin_repair_list' ) ) {
			$tools_file = buddypress()->plugin_dir . 'bp-forums/admin/tools.php';
			if ( file_exists( $tools_file ) ) {
				require_once $tools_file;
			}
		}

		if ( ! function_exists( 'bbp_admin_repair_list' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Forum repair tools are unavailable on this site.', 'buddyboss' ) ),
				500
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$slug = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		if ( empty( $slug ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No repair type was specified.', 'buddyboss' ) ),
				400
			);
		}

		$bbp_list = bbp_admin_repair_list();
		$match    = null;
		foreach ( $bbp_list as $item ) {
			if ( is_array( $item ) && ! empty( $item[0] ) && $slug === $item[0] && isset( $item[2] ) && is_callable( $item[2] ) ) {
				$match = $item;
				break;
			}
		}

		if ( null === $match ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: repair slug requested by the client. */
						__( 'Unknown forum repair item: %s', 'buddyboss' ),
						$slug
					),
				),
				404
			);
		}

		$result = call_user_func( $match[2] );

		// bbPress repair callbacks return `array( $status_code, $message )`.
		// `0` is success; anything else is failure.
		$status_code = isset( $result[0] ) ? (int) $result[0] : 1;
		$message     = isset( $result[1] ) ? (string) $result[1] : '';

		// Strip HTML entities from the legacy message strings
		// (`Counting&hellip;` etc.) so React renders clean text.
		$message = wp_strip_all_tags( html_entity_decode( $message, ENT_QUOTES, get_bloginfo( 'charset' ) ) );

		wp_send_json_success(
			array(
				'done'    => 0 === $status_code,
				'success' => 0 === $status_code,
				'message' => $message,
			)
		);
	}

	/**
	 * Wipe all imported default data.
	 *
	 * Wraps `bp_dd_clear_db()` — same delete flow the legacy
	 * "Clear Default Data" button triggers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function default_data_clear() {
		$this->verify_request();

		require_once buddypress()->plugin_dir . 'bp-core/bp-core-tools-default-data.php';

		bp_dd_clear_db();

		wp_send_json_success(
			array( 'message' => __( 'Default data cleared.', 'buddyboss' ) )
		);
	}
}

new BB_Admin_Tools_Ajax();
