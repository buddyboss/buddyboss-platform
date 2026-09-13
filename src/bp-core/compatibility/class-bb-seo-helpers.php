<?php
/**
 * Helper class for third party SEO plugins.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BB_SEO_Helpers' ) ) {

	/**
	 * BB_SEO_Helpers Class
	 *
	 * SEO plugins build their own output - the document title, Open Graph tags and the JSON-LD
	 * graph - and they resolve a member's name by reading the WP_User object's `display_name`
	 * PROPERTY rather than through get_the_author_meta(). A property read passes through no filter,
	 * so BuddyBoss' member name visibility cannot reach it: on a community using the "First Name"
	 * display format, or where a member restricted their surname, the full name is emitted into the
	 * page source for anonymous visitors and social scrapers. That is PROD-9896 exactly, arriving
	 * through a third party rather than through Platform.
	 *
	 * Verified against all three major plugins on a live install, each carrying the raw surname
	 * through a property read its own schema builder makes:
	 *
	 * - All in One SEO   - breadcrumb crumbs (Breadcrumbs.php:317) and the ProfilePage `mainEntity`
	 *                      name (ProfilePage.php:86). Reproduced end to end in the browser.
	 * - Yoast SEO        - the Person graph piece `name` (src/generators/schema/person.php:145) and
	 *                      the avatar `caption` (:243). Reproduced end to end in the browser: two
	 *                      raw surnames in the JSON-LD with this layer removed, zero with it.
	 * - Rank Math        - the author breadcrumb crumb (class-breadcrumbs.php:455).
	 *
	 * Each plugin's remaining schema goes through get_the_author_meta() and is already redacted by
	 * the member name filters in bp-members-filters.php.
	 *
	 * The interception is the same for every one of these plugins - take the structure they are
	 * about to output and replace the member names the current viewer may not see - so the plugins
	 * are configuration rather than code. `bb_seo_schema_graph_filters` is the extension point for
	 * an SEO plugin not covered here.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_SEO_Helpers {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Per-request memo of the raw => viewer-visible name map.
		 *
		 * Resolving a name is several cached reads and each registered filter asks for the same
		 * answer, so it is resolved once per request. Null until built.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var array|null
		 */
		private $name_map = null;

		/**
		 * BB_SEO_Helpers constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->compatibility_init();
		}

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return BB_SEO_Helpers
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Register the compatibility hooks for whichever SEO plugin is present.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function compatibility_init() {

			foreach ( $this->get_schema_graph_filters() as $hook => $priority ) {
				add_filter( $hook, array( $this, 'redact_schema_graph' ), (int) $priority );
			}
		}

		/**
		 * The SEO plugin filters that carry a JSON-LD graph on its way to the page.
		 *
		 * Each entry is `hook name => priority`. Registering a hook an inactive plugin never fires
		 * costs nothing, so the list is not gated on plugin detection - which also means a site can
		 * add an SEO plugin later without this stopping working.
		 *
		 * All three hook names are verified against the plugins' own source:
		 * `aioseo_schema_output` (AIOSEO Schema/Helpers.php:82), `wpseo_schema_graph` (Yoast
		 * src/generators/schema-generator.php:161) and `rank_math/json_ld` (Rank Math
		 * class-jsonld.php:73 via the `do_filter()` helper, which prefixes `rank_math/`). The
		 * priority is above every registration those plugins make on their own graph, so this runs
		 * on the finished structure.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Map of filter name to priority.
		 */
		public function get_schema_graph_filters() {

			/**
			 * Filters the SEO plugin hooks a member name is redacted out of.
			 *
			 * The callback receives whatever the plugin passes - an array of graph pieces, a nested
			 * array, or an object - and returns it with the names this viewer may not see replaced.
			 * Add an entry to cover an SEO plugin BuddyBoss does not ship support for.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $filters Map of `filter name => priority`.
			 */
			return (array) apply_filters(
				'bb_seo_schema_graph_filters',
				array(
					// All in One SEO.
					'aioseo_schema_output' => 20,
					// Yoast SEO.
					'wpseo_schema_graph'   => 20,
					// Rank Math - its own registrations run at 8, 10 and 11.
					'rank_math/json_ld'    => 20,
				)
			);
		}

		/**
		 * Replace the member names this viewer may not see inside an SEO plugin's graph.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $graph The graph the plugin is about to output.
		 * @return mixed The same structure with hidden name parts removed.
		 */
		public function redact_schema_graph( $graph ) {
			$map = $this->get_name_map();

			if ( empty( $map ) ) {
				return $graph;
			}

			return $this->redact_recursive( $graph, $map, 0 );
		}

		/**
		 * Walk a graph replacing raw names with the viewer-visible ones.
		 *
		 * Keys are left alone and only string VALUES are rewritten, so the shape the plugin built
		 * is preserved exactly. A value that is a URL is skipped: permalinks are built from
		 * user_nicename, never from the display name, so a name found inside one would be a
		 * coincidence and rewriting it would break the link.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $value The value to walk.
		 * @param array $map   Raw name => viewer-visible name.
		 * @param int   $depth Current recursion depth.
		 * @return mixed
		 */
		private function redact_recursive( $value, $map, $depth ) {

			// A schema graph is a handful of levels deep. The bound is here so a malformed or
			// deliberately nested structure cannot turn this into unbounded work.
			if ( $depth > 10 ) {
				return $value;
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $key => $item ) {
					$value[ $key ] = $this->redact_recursive( $item, $map, $depth + 1 );
				}

				return $value;
			}

			if ( is_object( $value ) ) {
				foreach ( get_object_vars( $value ) as $key => $item ) {
					$value->{$key} = $this->redact_recursive( $item, $map, $depth + 1 );
				}

				return $value;
			}

			if ( ! is_string( $value ) || '' === $value ) {
				return $value;
			}

			if ( 0 === strpos( $value, 'http://' ) || 0 === strpos( $value, 'https://' ) ) {
				return $value;
			}

			return strtr( $value, $map );
		}

		/**
		 * The raw => viewer-visible name map for the members this request can emit a name for.
		 *
		 * Only the author of the current query is considered: an author archive's queried user and
		 * a singular post's author are the members an SEO plugin builds a name from. Resolving
		 * every user mentioned anywhere in a graph would mean resolving names for members the page
		 * is not about.
		 *
		 * A member with nothing hidden produces no entry, so on the ordinary community - "First
		 * Name & Last Name" format, nobody restricting a name field - this map is empty and every
		 * registered filter returns its input untouched.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Raw display name => the name this viewer may see.
		 */
		private function get_name_map() {

			if ( is_array( $this->name_map ) ) {
				return $this->name_map;
			}

			$this->name_map = array();

			$user_ids = array();

			if ( is_author() ) {
				$queried = get_queried_object();

				if ( ! empty( $queried->ID ) ) {
					$user_ids[] = (int) $queried->ID;
				}
			}

			if ( is_singular() ) {
				$post_author = (int) get_post_field( 'post_author', get_queried_object_id() );

				if ( $post_author ) {
					$user_ids[] = $post_author;
				}
			}

			foreach ( array_unique( array_filter( $user_ids ) ) as $user_id ) {
				$user_data = get_userdata( $user_id );

				if ( empty( $user_data ) ) {
					continue;
				}

				// The WP_User property, deliberately: get_the_author_meta() would come back already
				// redacted and the map would have nothing to match against in the plugin's output.
				$raw = (string) $user_data->display_name;

				if ( '' === $raw ) {
					continue;
				}

				$redacted = bb_core_get_redacted_core_author_name( $user_id );

				if ( null === $redacted || $redacted === $raw ) {
					continue;
				}

				$this->name_map[ $raw ] = $redacted;
			}

			return $this->name_map;
		}
	}
}
