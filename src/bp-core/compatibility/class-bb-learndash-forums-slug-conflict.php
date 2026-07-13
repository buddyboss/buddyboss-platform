<?php
/**
 * LearnDash and Forums Slug Conflict Resolution
 *
 * This file handles the URL conflict between BuddyBoss Forums (topics archive)
 * and LearnDash LMS (sfwd-topic post type) when both use the same slug "topics".
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * LearnDash Forums Slug Conflict Helper Class
 *
 * This class resolves the URL conflict between BuddyBoss Forums discussions
 * pagination (e.g., /topics/page/2/) and LearnDash topics post type.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_LearnDash_Forums_Slug_Conflict {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var BB_LearnDash_Forums_Slug_Conflict
	 */
	protected static $instance = null;

	/**
	 * Flag indicating whether there is a slug conflict.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var bool
	 */
	protected $has_conflict = null;

	/**
	 * Main BB_LearnDash_Forums_Slug_Conflict Instance.
	 *
	 * Ensures only one instance of BB_LearnDash_Forums_Slug_Conflict is loaded or can be loaded.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @static
	 * @return BB_LearnDash_Forums_Slug_Conflict - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		// Check for conflict early and set up hooks.
		add_action( 'init', array( $this, 'setup_conflict_resolution' ), 999 );

		// Add admin notice for slug conflict.
		add_action( 'admin_notices', array( $this, 'display_slug_conflict_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'display_slug_conflict_notice' ) );

		// Handle the AJAX dismiss action.
		add_action( 'wp_ajax_bb_dismiss_learndash_forum_slug_notice', array( $this, 'dismiss_notice' ) );

		// Reset notice dismissal when relevant settings change.
		add_action( 'update_option__bbp_topic_archive_slug', array( __CLASS__, 'reset_notice_dismissal' ) );
		add_action( 'update_option_learndash_settings_topics_cpt', array( __CLASS__, 'reset_notice_dismissal' ) );
		add_action( 'update_option_learndash_settings_permalinks', array( __CLASS__, 'reset_notice_dismissal' ) );
	}

	/**
	 * Setup conflict resolution hooks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setup_conflict_resolution() {
		// Only proceed if there's a conflict.
		if ( ! $this->has_slug_conflict() ) {
			return;
		}

		// Add higher priority rewrite rules for forum topics pagination.
		add_action( 'generate_rewrite_rules', array( $this, 'add_forum_topics_rewrite_rules' ), 5 );

		// Filter the request to handle forum topics pagination.
		add_filter( 'request', array( $this, 'filter_forum_topics_request' ), 5 );

		// Modify pagination URLs to use query parameter when conflict exists.
		add_filter( 'bbp_get_topics_pagination_base', array( $this, 'modify_topics_pagination_base' ), 999 );
	}

	/**
	 * Check if there is a slug conflict between LearnDash topics and Forum topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if there is a conflict, false otherwise.
	 */
	public function has_slug_conflict() {
		// Cache the result.
		if ( null !== $this->has_conflict ) {
			return $this->has_conflict;
		}

		$this->has_conflict = false;

		// Check if LearnDash is active.
		if ( ! class_exists( 'SFWD_LMS' ) ) {
			return $this->has_conflict;
		}

		if ( ! class_exists( 'LearnDash_Settings_Section' ) ) {
			return $this->has_conflict;
		}

		// Check if Forums component is active.
		if ( ! function_exists( 'bp_is_active' ) ) {
			return $this->has_conflict;
		}

		if ( ! bp_is_active( 'forums' ) ) {
			return $this->has_conflict;
		}

		// Get the LearnDash topics slug.
		$ld_topics_slug = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'topics' );
		if ( empty( $ld_topics_slug ) ) {
			$ld_topics_slug = 'topics';
		}

		// Get the BuddyBoss forum topics archive slug.
		$bbp_topics_slug = function_exists( 'bbp_get_topic_archive_slug' ) ? bbp_get_topic_archive_slug() : 'topics';

		// Check if they conflict.
		$this->has_conflict = ( strtolower( $ld_topics_slug ) === strtolower( $bbp_topics_slug ) );

		return $this->has_conflict;
	}

	/**
	 * Add custom rewrite rules for forum topics pagination that take priority.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_Rewrite $wp_rewrite The WP_Rewrite object.
	 */
	public function add_forum_topics_rewrite_rules( $wp_rewrite ) {
		if ( ! $this->has_slug_conflict() ) {
			return;
		}

		$topic_archive_slug = function_exists( 'bbp_get_topic_archive_slug' ) ? bbp_get_topic_archive_slug() : 'topics';
		$paged_slug         = function_exists( 'bbp_get_paged_slug' ) ? bbp_get_paged_slug() : 'page';
		$topic_post_type    = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : 'topic';

		// Add specific rewrite rule for forum topics pagination that takes priority.
		$new_rules = array(
			// Match /topics/page/X/ for forum pagination.
			$topic_archive_slug . '/' . $paged_slug . '/?([0-9]{1,})/?$' => 'index.php?post_type=' . $topic_post_type . '&paged=$matches[1]&bbp_topics_archive=1',
			// Match /topics/ for forum archive.
			$topic_archive_slug . '/?$' => 'index.php?post_type=' . $topic_post_type . '&bbp_topics_archive=1',
		);

		$wp_rewrite->rules = array_merge( $new_rules, $wp_rewrite->rules );
	}

	/**
	 * Filter the request to handle forum topics pagination.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $query_vars The query variables.
	 *
	 * @return array Modified query variables.
	 */
	public function filter_forum_topics_request( $query_vars ) {
		// Get the current request URI.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Only process for topics-related URLs.
		if ( strpos( $request_uri, 'topics' ) === false ) {
			return $query_vars;
		}

		if ( ! $this->has_slug_conflict() ) {
			return $query_vars;
		}

		$topic_archive_slug = function_exists( 'bbp_get_topic_archive_slug' ) ? bbp_get_topic_archive_slug() : 'topics';
		$paged_slug         = function_exists( 'bbp_get_paged_slug' ) ? bbp_get_paged_slug() : 'page';
		$topic_post_type    = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : 'topic';

		// Get the request path relative to WordPress home.
		$request_path = trim( wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );

		// Strip the WordPress installation subdirectory if present.
		$home_path = trim( wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( ! empty( $home_path ) && strpos( $request_path, $home_path ) === 0 ) {
			$request_path = trim( substr( $request_path, strlen( $home_path ) ), '/' );
		}

		// Check if this is a forum topics pagination request.
		// Pattern: topics/page/X or topics/
		$pattern = '#^' . preg_quote( $topic_archive_slug, '#' ) . '(?:/' . preg_quote( $paged_slug, '#' ) . '/(\d+))?/?$#i';

		if ( preg_match( $pattern, $request_path, $matches ) ) {
			// This is a forum topics archive or pagination request.
			$paged = isset( $matches[1] ) ? absint( $matches[1] ) : 1;

			// Force this to be a forum topics archive request.
			$query_vars['post_type'] = $topic_post_type;
			$query_vars['paged']     = $paged;

			// Remove any LearnDash-specific query vars that might interfere.
			if ( isset( $query_vars['sfwd-topic'] ) ) {
				unset( $query_vars['sfwd-topic'] );
			}
			if ( isset( $query_vars['name'] ) && 'page' === $query_vars['name'] ) {
				unset( $query_vars['name'] );
			}

			// Set a flag to identify this as a forum topics archive.
			$query_vars['bbp_topics_archive'] = 1;
		}

		return $query_vars;
	}

	/**
	 * Modify the topics pagination base URL when there's a slug conflict.
	 *
	 * Falls back to query parameter pagination instead of pretty URLs.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $base The pagination base URL.
	 *
	 * @return string Modified pagination base URL.
	 */
	public function modify_topics_pagination_base( $base ) {
		if ( ! $this->has_slug_conflict() ) {
			return $base;
		}

		// Only modify on the topics archive page.
		if ( ! function_exists( 'bbp_is_topic_archive' ) || ! bbp_is_topic_archive() ) {
			return $base;
		}

		// Use query parameter pagination to avoid URL conflicts.
		$topics_url = function_exists( 'bbp_get_topics_url' ) ? bbp_get_topics_url() : home_url( '/topics/' );
		$paged_slug = function_exists( 'bbp_get_paged_slug' ) ? bbp_get_paged_slug() : 'page';

		// Return a more specific base that helps WordPress route correctly.
		$new_base = trailingslashit( $topics_url ) . user_trailingslashit( $paged_slug . '/%#%/' );

		return $new_base;
	}

	/**
	 * Display an admin notice when there's a slug conflict.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function display_slug_conflict_notice() {
		// Only show on specific admin pages.
		if ( ! $this->should_show_notice() ) {
			return;
		}

		// Check if the notice has been dismissed.
		$dismissed = get_option( 'bb_learndash_forum_slug_notice_dismissed', false );
		if ( $dismissed ) {
			return;
		}

		// Check if there's actually a conflict.
		if ( ! $this->has_slug_conflict() ) {
			return;
		}

		$settings_url       = admin_url( 'admin.php?page=bp-settings&tab=bp-forums' );
		$ld_settings_url    = admin_url( 'admin.php?page=learndash_lms_settings&section-settings=settings-sections-permalinks' );
		$topic_archive_slug = function_exists( 'bbp_get_topic_archive_slug' ) ? bbp_get_topic_archive_slug() : 'topics';

		?>
		<div class="notice notice-warning is-dismissible bb-learndash-forum-slug-notice" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'bb_dismiss_learndash_forum_slug_notice' ) ); ?>">
			<p>
				<strong><?php esc_html_e( 'BuddyBoss Platform - LearnDash URL Conflict Detected', 'buddyboss' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: The conflicting slug. */
					esc_html__( 'Both BuddyBoss Forums and LearnDash LMS are using "%1$s" as their URL slug. This may cause pagination issues on the forum discussions archive page.', 'buddyboss' ),
					'<code>/' . esc_html( $topic_archive_slug ) . '/</code>'
				);
				?>
			</p>
			<p>
				<?php esc_html_e( 'To resolve this conflict, please change one of the following:', 'buddyboss' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li>
					<?php
					printf(
						/* translators: 1: Link to forums settings. */
						esc_html__( 'Change the BuddyBoss Forums "Discussions base" slug in %s', 'buddyboss' ),
						'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings > Forums > Forum Permalinks', 'buddyboss' ) . '</a>'
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: 1: Link to LearnDash settings. */
						esc_html__( 'Change the LearnDash "Topics" permalink slug in %s', 'buddyboss' ),
						'<a href="' . esc_url( $ld_settings_url ) . '">' . esc_html__( 'LearnDash > Settings > Permalinks', 'buddyboss' ) . '</a>'
					);
					?>
				</li>
			</ul>
			<p>
				<em><?php esc_html_e( 'After changing either slug, please visit Settings > Permalinks and click "Save Changes" to flush rewrite rules.', 'buddyboss' ); ?></em>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('.bb-learndash-forum-slug-notice').on('click', '.notice-dismiss', function() {
				var $notice = $(this).closest('.bb-learndash-forum-slug-notice');
				var nonce = $notice.data('dismiss-nonce');
				$.post(ajaxurl, {
					action: 'bb_dismiss_learndash_forum_slug_notice',
					nonce: nonce
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Check if the admin notice should be shown.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if notice should be shown.
	 */
	protected function should_show_notice() {
		// Only show to users who can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Show on these admin pages.
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $current_screen ) {
			return false;
		}

		$show_on_screens = array(
			'buddyboss_page_bp-settings',
			'toplevel_page_bp-components',
			'buddyboss_page_bp-integrations',
			'settings_page_bp-settings-network',
			'sfwd-courses_page_learndash_lms_settings',
			'admin_page_learndash_lms_settings',
			'dashboard',
			'plugins',
		);

		// Check current screen ID.
		if ( in_array( $current_screen->id, $show_on_screens, true ) ) {
			return true;
		}

		// Also show if we're on any BuddyBoss settings page.
		if ( isset( $_GET['page'] ) && ( strpos( $_GET['page'], 'bp-' ) === 0 || 'learndash_lms_settings' === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	/**
	 * Handle the AJAX dismiss action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function dismiss_notice() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_dismiss_learndash_forum_slug_notice' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		// Save dismissal.
		update_option( 'bb_learndash_forum_slug_notice_dismissed', true );

		wp_send_json_success();
	}

	/**
	 * Get the recommended slug to avoid conflict.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Recommended alternative slug.
	 */
	public function get_recommended_forum_slug() {
		return 'discussions';
	}

	/**
	 * Reset the notice dismissal (useful when slug settings change).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function reset_notice_dismissal() {
		delete_option( 'bb_learndash_forum_slug_notice_dismissed' );
	}
}

// Initialize the class.
BB_LearnDash_Forums_Slug_Conflict::instance();
