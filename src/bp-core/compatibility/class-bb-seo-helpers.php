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
	 * page source for anonymous visitors and social scrapers. That is the leak this guards against, arriving
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
	 * the member name filters in bp-members-filters.php, with two boundaries this layer does not
	 * reach. Both were established by reading the plugins' source rather than at runtime:
	 *
	 * - All in One SEO resolves its own smart tags AFTER the graph filter has run - getOutput()
	 *   calls cleanAndParseData() on the filtered graph (Schema/Helpers.php:83), which resolves
	 *   `#author_name` through a raw WP_User property read (Utils/Tags.php:1038). A graph string
	 *   still holding that tag is therefore resolved past this redaction; the Article graph takes
	 *   its author name from the schema type options, which hold the literal tag on a site whose
	 *   options were seeded with it (Schema/Graphs/Article/Article.php:39, Main/Updates.php:884).
	 *   AIOSEO exposes no filter between that resolution and the output, so there is no hook here
	 *   to take.
	 * - Rank Math's visible breadcrumb trail reads the same raw property its schema breadcrumb
	 *   does (class-breadcrumbs.php:455) and is printed as HTML, not through the JSON-LD graph.
	 *
	 * The interception is the same for every one of these plugins - take the structure they are
	 * about to output and replace the member names the current viewer may not see - so the plugins
	 * are configuration rather than code. `bb_seo_schema_graph_filters` and
	 * `bb_seo_author_name_filters` are the extension points for an SEO plugin not covered here.
	 *
	 * A plugin's head is not built only for a page view. Yoast serialises the same head into the
	 * REST API - `yoast_head` and `yoast_head_json` on wp/v2/users and wp/v2/posts, both readable
	 * without authentication - where there is no main query to identify the member from. The
	 * member is therefore taken from the plugin's own context object first and from the queried
	 * object only as a fallback, so the same redaction applies on both.
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
		 * Per-request memo of each resolved member's raw => viewer-visible name entry.
		 *
		 * Resolving a name is several cached reads and each registered filter asks for the same
		 * answer, so it is resolved once per member per request. An entry is an empty array when that
		 * member has nothing hidden from this viewer.
		 *
		 * Memoised per member rather than per request because one REST request can render the head
		 * of a whole collection, each item written by a different author.
		 *
		 * Keyed by member, VIEWER and display-name format - the same key the memo this wraps uses
		 * (bb_core_get_redacted_core_author_name()). The entry is viewer-dependent: an administrator
		 * sees every name part and produces an empty entry, and under a user-id-only key that empty
		 * entry then answered for a guest, handing the unredacted name to the JSON-LD graph and the
		 * author meta tag. One request really can resolve for more than one viewer or format -
		 * wp_set_current_user(), a REST batch, and the `bb_core_get_viewer_user_id` and
		 * `bp_core_display_name_format` filters all change them mid-request.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var array
		 */
		private $name_map = array();

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

			// Two accepted args: Yoast passes the Meta_Tags_Context describing the entity whose head
			// it is building, which is the only reliable way to know the member on a REST request.
			foreach ( $this->get_schema_graph_filters() as $hook => $priority ) {
				add_filter( $hook, array( $this, 'redact_schema_graph' ), (int) $priority, 2 );
			}

			foreach ( $this->get_author_name_filters() as $hook => $priority ) {
				add_filter( $hook, array( $this, 'redact_author_name' ), (int) $priority, 2 );
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
		 * class-jsonld.php:149 via the `do_filter()` helper, which prefixes `rank_math/`;
		 * class-jsonld.php:73 dispatches the same filter for the admin schema preview).
		 *
		 * Priority 20 is not a promise that the structure is finished. The narrower claim it does
		 * carry is that it is above every point at which a plugin writes a member name into its
		 * own graph:
		 *
		 * - Yoast and All in One SEO register no callback of their own on these filters, so 20 is
		 *   last by default. Yoast reads the member name while building each graph piece
		 *   (generators/schema/person.php:145 and :243), which is before the graph filter
		 *   (schema-generator.php:161); what it does afterwards only appends block-authored
		 *   schema and drops an empty breadcrumb (schema-generator.php:73-74).
		 * - Rank Math registers up to seven, module activation permitting: 8
		 *   (Block_Parser::parse), 9 (Local_Seo::organization_or_person), 10 twice
		 *   (JsonLD::add_context_data and Frontend::add_schema), 11 (BuddyPress::json_ld) and 99
		 *   twice (Frontend::connect_schema_entities and Web_Stories::change_publisher_logo).
		 *   The two at 99 run after this one and neither writes a name: connect_schema_entities
		 *   rewrites only `@id` references, `isPartOf`, `inLanguage`, `mainEntityOfPage` and
		 *   `@type` (class-frontend.php:157-240 with class-jsonld.php:488-535), and
		 *   change_publisher_logo swaps a logo ImageObject (class-web-stories.php:58). Raise this
		 *   priority above 99 if either of those stops being true.
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
					// Rank Math - its own registrations run at 8, 9, 10, 11 and 99; see above for
					// why the two at 99 do not need this to move.
					'rank_math/json_ld'    => 20,
				)
			);
		}

		/**
		 * The SEO plugin filters that carry a lone member name on its way to the page.
		 *
		 * A plugin that builds its author meta tag from the WP_User property rather than through
		 * get_the_author_meta() needs its own filter here: the value is a bare string, not a graph,
		 * so it never reaches redact_schema_graph().
		 *
		 * Verified against the plugins' own source: Yoast reads `$user_data->display_name` in
		 * Meta_Author_Presenter::get() (src/presenters/meta-author-presenter.php:57) and offers
		 * `wpseo_meta_author`. All in One SEO builds the same tag with get_the_author_meta()
		 * (app/Common/Views/main/meta.php:38), which the member name filters already redact, and
		 * Rank Math emits no author meta tag - so neither needs an entry.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Map of filter name to priority.
		 */
		public function get_author_name_filters() {

			/**
			 * Filters the SEO plugin hooks a lone member name is redacted on.
			 *
			 * The callback receives the name the plugin is about to print and returns the name this
			 * viewer may see. Add an entry to cover an SEO plugin BuddyBoss does not ship support
			 * for.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $filters Map of `filter name => priority`.
			 */
			return (array) apply_filters(
				'bb_seo_author_name_filters',
				array(
					// Yoast SEO.
					'wpseo_meta_author' => 20,
				)
			);
		}

		/**
		 * Replace a lone member name an SEO plugin is about to print.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $name   The name the plugin resolved, normally a string.
		 * @param mixed $source Optional. The plugin's context/presentation object for this entity.
		 * @return mixed The name this viewer may see, or the input untouched.
		 */
		public function redact_author_name( $name, $source = null ) {

			if ( ! is_string( $name ) || '' === $name ) {
				return $name;
			}

			$map = $this->get_name_map( $this->resolve_user_ids( $source ) );

			if ( empty( $map ) ) {
				return $name;
			}

			return bb_core_replace_names( $name, $map );
		}

		/**
		 * Replace the member names this viewer may not see inside an SEO plugin's graph.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $graph  The graph the plugin is about to output.
		 * @param mixed $source Optional. The plugin's context object for the entity being rendered.
		 *                      Yoast passes its Meta_Tags_Context here; Rank Math passes its JsonLD
		 *                      instance and All in One SEO passes nothing, both of which fall back
		 *                      to the queried object.
		 * @return mixed The same structure with hidden name parts removed.
		 */
		public function redact_schema_graph( $graph, $source = null ) {
			$map = $this->get_name_map( $this->resolve_user_ids( $source ) );

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
		 * The walk is not limited to name keys - a member's name is redacted wherever it appears,
		 * a headline or a description included - so the replacement matches whole words only. An
		 * unbounded substitution rewrote the middle of unrelated ones: a member called "Ann" turned
		 * "Annapolis Anniversary" into "A.apolis A.iversary". See bb_core_replace_names().
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

			return bb_core_replace_names( $value, $map );
		}

		/**
		 * The members this request can emit a name for.
		 *
		 * Two sources, in order of reliability:
		 *
		 * 1. The SEO plugin's own context for the entity it is rendering. Yoast hands its
		 *    Meta_Tags_Context to both `wpseo_schema_graph` and `wpseo_meta_author`, and its
		 *    `indexable` names the entity - `object_type` 'user' with the user id, or 'post' with
		 *    the post id and `author_id`. This is the only source that works on a REST request,
		 *    where there is no main query to ask, and the only one that stays correct when a single
		 *    request renders a whole collection of posts by different authors.
		 * 2. The queried object, for a front-end page view and for any plugin that passes no
		 *    context of its own.
		 *
		 * Only the member the page is about is considered. Resolving every user mentioned anywhere
		 * in a graph would mean resolving names for members the page is not about.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $source Optional. The context object the SEO plugin passed, when it passed one.
		 * @return int[] User ids, possibly empty.
		 */
		private function resolve_user_ids( $source = null ) {

			$user_ids = $this->resolve_user_ids_from_context( $source );

			if ( ! empty( $user_ids ) ) {
				return $user_ids;
			}

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

			return $user_ids;
		}

		/**
		 * The member ids an SEO plugin's own context object names, if it carries one.
		 *
		 * Deliberately duck-typed rather than type-hinted against a plugin class: the argument is
		 * whatever the hook happened to pass, the plugin may not be the one whose class we would
		 * name, and a wrong guess here must degrade to the queried object rather than fatal.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $source The second argument the filter passed.
		 * @return int[] User ids, empty when the object carries none.
		 */
		private function resolve_user_ids_from_context( $source ) {

			if ( ! is_object( $source ) ) {
				return array();
			}

			// Yoast hands presenters an Indexable_Presentation, whose `model` is the indexable the
			// context wraps; schema filters get the context itself.
			$indexable = null;

			if ( isset( $source->indexable ) && is_object( $source->indexable ) ) {
				$indexable = $source->indexable;
			} elseif ( isset( $source->model ) && is_object( $source->model ) ) {
				$indexable = $source->model;
			}

			if ( null === $indexable || empty( $indexable->object_type ) ) {
				return array();
			}

			if ( 'user' === $indexable->object_type && ! empty( $indexable->object_id ) ) {
				return array( (int) $indexable->object_id );
			}

			if ( 'post' !== $indexable->object_type ) {
				return array();
			}

			// author_id is what the plugin itself builds the author graph from; the post's own
			// author is the fallback for an indexable that has not stored one.
			if ( ! empty( $indexable->author_id ) ) {
				return array( (int) $indexable->author_id );
			}

			if ( empty( $indexable->object_id ) ) {
				return array();
			}

			$post_author = (int) get_post_field( 'post_author', (int) $indexable->object_id );

			return $post_author ? array( $post_author ) : array();
		}

		/**
		 * The raw => viewer-visible name map for the given members.
		 *
		 * A member with nothing hidden produces no entry, so on the ordinary community - "First
		 * Name & Last Name" format, nobody restricting a name field - this map is empty and every
		 * registered filter returns its input untouched.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int[] $user_ids The members to resolve.
		 * @return array Raw display name => the name this viewer may see.
		 */
		private function get_name_map( $user_ids ) {

			$map = array();

			// Resolved once per call rather than per member: neither can change inside the loop.
			$viewer_key = bb_core_get_viewer_user_id() . ':' . bp_core_display_name_format();

			foreach ( array_unique( array_filter( array_map( 'intval', (array) $user_ids ) ) ) as $user_id ) {

				$memo_key = $user_id . ':' . $viewer_key;

				if ( ! isset( $this->name_map[ $memo_key ] ) ) {
					$this->name_map[ $memo_key ] = $this->build_name_entry( $user_id );
				}

				$map += $this->name_map[ $memo_key ];
			}

			return $map;
		}

		/**
		 * The raw => viewer-visible entry for one member.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id The member to resolve.
		 * @return array Single-entry map, or an empty array when nothing is hidden from this viewer.
		 */
		private function build_name_entry( $user_id ) {

			// The raw wp_users row. get_userdata() resolves to the same value, but wraps the row in
			// a WP_User whose construction also loads and maps the member's capabilities - work
			// nothing here reads, and this method runs once per member in get_name_map()'s loop.
			$user_data = BP_Core_User::get_core_userdata( $user_id );

			if ( empty( $user_data ) ) {
				return array();
			}

			// The WP_User property, deliberately: get_the_author_meta() would come back already
			// redacted and the map would have nothing to match against in the plugin's output.
			$raw = (string) $user_data->display_name;

			if ( '' === $raw ) {
				return array();
			}

			$redacted = bb_core_get_redacted_core_author_name( $user_id );

			if ( null === $redacted || $redacted === $raw ) {
				return array();
			}

			return array( $raw => $redacted );
		}
	}
}
