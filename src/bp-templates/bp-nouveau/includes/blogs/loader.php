<?php
/**
 * BP Nouveau Blogs
 *
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Blogs Loader class
 *
 * @since BuddyPress 3.0.0
 */
#[\AllowDynamicProperties]
class BBoss_Nouveau_Blogs {
	/**
	 * Constructor
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
		$this->setup_filters();
	}

	/**
	 * Globals
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_globals() {
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function includes() {
		require $this->dir . 'functions.php';
		require $this->dir . 'template-tags.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

		// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'BBOSS_DOING_AJAX' ) && true === BBOSS_DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'blogs_' ) ) {
					require $this->dir . 'ajax.php';
				}
			} );
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_actions() {
		if ( ! is_admin() || ( defined( 'BBOSS_DOING_AJAX' ) && BBOSS_DOING_AJAX ) ) {
			// Avoid Notices for BuddyPress Legacy Backcompat
			remove_action( 'bp_blogs_directory_blog_types', 'bboss_blog_backcompat_create_nav_item', 1000 );
		}

		add_action( 'bp_nouveau_enqueue_scripts', function() {
			if ( bboss_get_blog_signup_allowed() && bboss_is_register_page() ) {
				wp_add_inline_script( 'bp-nouveau', bboss_nouveau_get_blog_signup_inline_script() );
			}
		} );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {}
}

/**
 * Launch the Blogs loader class.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_blogs( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->blogs = new BBoss_Nouveau_Blogs();
}
add_action( 'bp_nouveau_includes', 'bboss_nouveau_blogs', 10, 1 );
