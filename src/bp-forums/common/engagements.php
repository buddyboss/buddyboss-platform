<?php

/**
 * bbPress Common Engagements.
 *
 * This file contains the common classes and functions for interacting with the
 * bbPress engagements API. See `includes/users/engagements.php` for more.
 *
 * @since BuddyBoss 2.3.4
 *
 * @package    bbPress
 * @subpackage Common
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the strategy used for storing user engagements.
 *
 * @since 2.6.0 bbPress (r6722)
 * @since BuddyBoss 2.3.4
 *
 * @param string $rel_key  The key used to index this relationship.
 * @param string $rel_type The type of meta to look in.
 *
 * @return string
 */
function bbp_user_engagements_interface( $rel_key = '', $rel_type = 'post' ) {
	return apply_filters( 'bbp_user_engagements_interface', bbpress()->engagements, $rel_key, $rel_type );
}

/**
 * Base strategy class for interfacing with User Engagements, which other
 * classes will extend.
 *
 * @since 2.6.0 bbPress (r6722)
 * @since BuddyBoss 2.3.4
 */
class BBP_User_Engagements_Base {

	/**
	 * Type of strategy being used.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * Add a user id to an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 * @param bool   $unique    Whether meta key should be unique to the object.
	 */
	public function add_user_to_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post', $unique = false ) {

	}

	/**
	 * Remove a user id from an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 */
	public function remove_user_from_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {

	}

	/**
	 * Remove a user id from all objects.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 */
	public function remove_user_from_all_objects( $user_id = 0, $meta_key = '', $meta_type = 'post' ) {

	}

	/**
	 * Remove an object from all users.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 */
	public function remove_object_from_all_users( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {

	}

	/**
	 * Remove all users from all objects.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 */
	public function remove_all_users_from_all_objects( $meta_key = '', $meta_type = 'post' ) {

	}

	/**
	 * Get users of an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The key used to index this relationship.
	 * @param string $meta_type The type of meta to look in.
	 */
	public function get_users_for_object( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {

	}

	/**
	 * Get the part of the query responsible for JOINing objects to relationships.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param array  $args        Default query arguments.
	 * @param string $context_key Additional context.
	 * @param string $meta_key    The relationship key.
	 * @param string $meta_type   The relationship type (usually 'post').
	 */
	public function get_query( $args = array(), $context_key = '', $meta_key = '', $meta_type = 'post' ) {

	}
}

/**
 * Meta strategy for interfacing with User Engagements.
 *
 * @since 2.6.0 bbPress (r6722)
 * @since BuddyBoss 2.3.4
 */
class BBP_User_Engagements_Meta extends BBP_User_Engagements_Base {

	/**
	 * Type of strategy being used.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @var string
	 */
	public $type = 'meta';

	/**
	 * Add a user id to an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 * @param bool   $unique    Whether meta key should be unique to the object.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function add_user_to_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post', $unique = false ) {
		if ( function_exists( 'bb_forum_favourite_legacy_data_support' ) && bb_forum_favourite_legacy_data_support() ) {
			$bbp_user = new BBP_User_Engagements_User();
			$bbp_user->add_user_to_object( $object_id, $user_id, $meta_key );
		}
		return add_metadata( $meta_type, $object_id, $meta_key, $user_id, $unique );
	}

	/**
	 * Remove a user id from an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
		if ( function_exists( 'bb_forum_favourite_legacy_data_support' ) && bb_forum_favourite_legacy_data_support() ) {
			$bbp_user = new BBP_User_Engagements_User();
			$bbp_user->remove_user_from_object( $object_id, $user_id, $meta_key );
		}
		return delete_metadata( $meta_type, $object_id, $meta_key, $user_id, false );
	}

	/**
	 * Remove a user id from all objects.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_all_objects( $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
		return delete_metadata( $meta_type, null, $meta_key, $user_id, true );
	}

	/**
	 * Remove an object from all users.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_object_from_all_users( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {
		return delete_metadata( $meta_type, $object_id, $meta_key, null, false );
	}

	/**
	 * Remove all users from all objects.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_all_users_from_all_objects( $meta_key = '', $meta_type = 'post' ) {
		return delete_metadata( $meta_type, null, $meta_key, null, true );
	}

	/**
	 * Get users of an object.
	 *
	 * @since 2.6.0 bbPress (r6722)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The key used to index this relationship.
	 * @param string $meta_type The type of meta to look in.
	 *
	 * @return array Returns ids of users.
	 */
	public function get_users_for_object( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {
		return wp_parse_id_list( get_metadata( $meta_type, $object_id, $meta_key, false ) );
	}

	/**
	 * Get the part of the query responsible for JOINing objects to relationships.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param array  $args        Default query arguments.
	 * @param string $context_key Additional context.
	 * @param string $meta_key    The relationship key.
	 * @param string $meta_type   The relationship type (usually 'post').
	 *
	 * @return array
	 */
	public function get_query( $args = array(), $context_key = '', $meta_key = '', $meta_type = 'post' ) {

		// Backwards compat for pre-2.6.0.
		if ( is_numeric( $args ) ) {
			$args = array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => $meta_key,
						'value'   => bbp_get_user_id( $args, false, false ),
						'compare' => 'NUMERIC',
					),
				),
			);
		}

		// Default arguments.
		$defaults = array(
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => $meta_key,
					'value'   => bbp_get_displayed_user_id(),
					'compare' => 'NUMERIC',
				),
			),
		);

		// Parse arguments.
		return bbp_parse_args( $args, $defaults, $context_key );
	}
}

/**
 * Term strategy for interfacing with User Engagements.
 *
 * @since 2.6.0 bbPress (r6737)
 * @since BuddyBoss 2.3.4
 */
class BBP_User_Engagements_Term extends BBP_User_Engagements_Base {

	/**
	 * Type of strategy being used.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @var string
	 */
	public $type = 'term';

	/**
	 * Register an engagement taxonomy just-in-time for immediate use.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $tax_key     Taxonomy key.
	 * @param string $object_type The object type.
	 */
	private function jit_taxonomy( $tax_key = '', $object_type = 'user' ) {

		// Bail if taxonomy already exists.
		if ( taxonomy_exists( $tax_key ) ) {
			return;
		}

		// Register the taxonomy.
		register_taxonomy(
			$tax_key,
			'bbp_' . $object_type,
			array(
				'labels'                => array(),
				'description'           => '',
				'public'                => false,
				'publicly_queryable'    => false,
				'hierarchical'          => false,
				'show_ui'               => false,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => false,
				'show_admin_column'     => false,
				'meta_box_cb'           => false,
				'capabilities'          => array(),
				'rewrite'               => false,
				'query_var'             => '',
				'update_count_callback' => '',
				'show_in_rest'          => false,
				'rest_base'             => false,
				'rest_controller_class' => false,
				'_builtin'              => false,
			)
		);
	}

	/**
	 * Add a user id to an object.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 * @param bool   $unique    Whether meta key should be unique to the object.
	 *
	 * @return array|bool|void|WP_Error Returns true on success, false on failure.
	 */
	public function add_user_to_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post', $unique = false ) {
		$user_key = "{$meta_key}_user_id_{$user_id}";
		$tax_key  = "{$meta_key}_{$meta_type}";
		$this->jit_taxonomy( $tax_key );

		return wp_add_object_terms( $object_id, $user_key, $tax_key );
	}

	/**
	 * Remove a user id from an object.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
		$user_key = "{$meta_key}_user_id_{$user_id}";
		$tax_key  = "{$meta_key}_{$meta_type}";
		$this->jit_taxonomy( $tax_key );

		return wp_remove_object_terms( $object_id, $user_key, $tax_key );
	}

	/**
	 * Remove a user id from all objects.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_all_objects( $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
		$user_key = "{$meta_key}_user_id_{$user_id}";
		$tax_key  = "{$meta_key}_{$meta_type}";
		$this->jit_taxonomy( $tax_key );
		$term = get_term_by( 'slug', $user_key, $tax_key );

		return wp_delete_term( $term->term_id, $tax_key );
	}

	/**
	 * Remove an object from all users.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool|void Returns true on success, false on failure.
	 */
	public function remove_object_from_all_users( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {
		return wp_delete_object_term_relationships( $object_id, get_object_taxonomies( 'bbp_user' ) );
	}

	/**
	 * Remove all users from all objects.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool|void
	 */
	public function remove_all_users_from_all_objects( $meta_key = '', $meta_type = 'post' ) {
		// TODO.
	}

	/**
	 * Get users of an object.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The key used to index this relationship.
	 * @param string $meta_type The type of meta to look in.
	 *
	 * @return array Returns ids of users.
	 */
	public function get_users_for_object( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {
		$user_key = "{$meta_key}_user_id_";
		$tax_key  = "{$meta_key}_{$meta_type}";
		$this->jit_taxonomy( $tax_key );

		// Get terms.
		$terms = get_terms(
			array(
				'object_ids' => $object_id,
				'taxonomy'   => $tax_key,
			)
		);

		// Slug part to replace.
		$user_ids = array();

		// Loop through terms and get the user ID.
		foreach ( $terms as $term ) {
			$user_ids[] = str_replace( $user_key, '', $term->slug );
		}

		// Parse & return.
		return wp_parse_id_list( $user_ids );
	}

	/**
	 * Get the part of the query responsible for JOINing objects to relationships.
	 *
	 * @since 2.6.0 bbPress (r6737)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param array  $args        Default query arguments.
	 * @param string $context_key Additional context.
	 * @param string $meta_key    The relationship key.
	 * @param string $meta_type   The relationship type (usually 'post').
	 *
	 * @return array
	 */
	public function get_query( $args = array(), $context_key = '', $meta_key = '', $meta_type = 'post' ) {
		$tax_key  = "{$meta_key}_{$meta_type}";
		$user_key = "{$meta_key}_user_id_";

		// Make sure the taxonomy is registered.
		$this->jit_taxonomy( $tax_key );

		// Backwards compat for pre-2.6.0.
		if ( is_numeric( $args ) ) {
			$args = array(
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $tax_key,
						'terms'    => $user_key . bbp_get_user_id( $args, false, false ),
						'field'    => 'slug',
					),
				),
			);
		}

		// Default arguments.
		$defaults = array(
			'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $tax_key,
					'terms'    => $user_key . bbp_get_displayed_user_id(),
					'field'    => 'slug',
				),
			),
		);

		// Parse arguments.
		return bbp_parse_args( $args, $defaults, $context_key );
	}
}

/**
 * User strategy for interfacing with User Engagements.
 *
 * This strategy largely exists for backwards compatibility with bbPress 2.5,
 * or installations that have not upgraded their databases to 2.6 or above.
 *
 * Note: this strategy is going to be a bit less tidy than the others, because
 * it needs to do weird things to maintain the 2.5 status-quo. Do not use this
 * strategy as an example when building your own.
 *
 * @since 2.6.0 bbPress (r6844)
 * @since BuddyBoss 2.3.4
 */
class BBP_User_Engagements_User extends BBP_User_Engagements_Base {

	/**
	 * Type of strategy being used.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @var string
	 */
	public $type = 'user';

	/**
	 * Private function to map 2.6 meta keys to 2.5 user-option keys.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  Meta key.
	 * @param int    $object_id Object Id.
	 * @param bool   $prefix    Prefix.
	 *
	 * @return string
	 */
	private function get_user_option_key( $meta_key = '', $object_id = 0, $prefix = false ) {
		switch ( $meta_key ) {

			// Favorites.
			case '_bbp_favorite':
				$key = '_bbp_favorites';
				break;

			// Subscriptions.
			case '_bbp_subscription':
				// Maybe guess at post type.
				$post_type = ! empty( $object_id )
					? get_post_type( $object_id )
					: bbp_get_topic_post_type();

				// Forums & Topics used different keys.
				$key = ( bbp_get_forum_post_type() === $post_type )
					? '_bbp_forum_subscriptions'
					: '_bbp_subscriptions';

				break;

			// Unknown, so pluralize.
			default:
				$key = "{$meta_key}s";
				break;
		}

		// Maybe prefix the key (for use in raw database queries).
		if ( true === $prefix ) {
			$key = bbp_db()->get_blog_prefix() . $key;
		}

		// Return the old (pluralized) user option key.
		return $key;
	}

	/**
	 * Private function to get a 2.5 compatible cache key.
	 *
	 * This method exists to provide backwards compatibility with bbPress 2.5,
	 * which had caching surrounding the FIND_IN_SET usermeta queries.
	 *
	 * @since 2.6.3 bbPress (r6991)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The meta key.
	 * @param int    $object_id The object id.
	 *
	 * @return string
	 */
	private function get_cache_key( $meta_key = '', $object_id = 0 ) {

		// No negative numbers in cache keys (zero is weird, but not disallowed).
		$object_id = absint( $object_id );

		// Maybe guess at post type.
		$post_type = ! empty( $object_id )
			? get_post_type( $object_id )
			: bbp_get_topic_post_type();

		switch ( $meta_key ) {

			// Favorites.
			case '_bbp_favorite':
				$key = 'bbp_get_topic_favoriters_';
				break;

			// Subscriptions.
			case '_bbp_subscription':
				// Forums & Topics used different keys.
				$key = ( bbp_get_forum_post_type() === $post_type )
					? 'bbp_get_forum_subscribers_'
					: 'bbp_get_topic_subscribers_';

				break;

			// Unknown, so pluralize.
			default:
				$nounize = rtrim( $meta_key, 'e' );
				$key     = "bbp_get_{$post_type}_{$nounize}ers_";
				break;
		}

		// Return the old (pluralized) user option key with object ID appended.
		return "{$key}{$object_id}";
	}

	/**
	 * Get the user engagement cache for a given meta key and object ID.
	 *
	 * This method exists to provide backwards compatibility with bbPress 2.5,
	 * which had caching surrounding the FIND_IN_SET queries in usermeta.
	 *
	 * @since 2.6.3 bbPress (r6991)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The meta key.
	 * @param int    $object_id The object id.
	 *
	 * @return mixed Results from cache get.
	 */
	private function cache_get( $meta_key = '', $object_id = 0 ) {
		$cache_key = $this->get_cache_key( $meta_key, $object_id );

		return wp_cache_get( $cache_key, 'bbpress_engagements' );
	}

	/**
	 * Set the user engagement cache for a given meta key and object ID.
	 *
	 * This method exists to provide backwards compatibility with bbPress 2.5,
	 * which had caching surrounding the FIND_IN_SET queries in usermeta.
	 *
	 * @since 2.6.3 bbPress (r6991)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  Meta key.
	 * @param int    $object_id Object Id.
	 * @param array  $user_ids  The user ids.
	 *
	 * @return mixed Results from cache set.
	 */
	private function cache_set( $meta_key = '', $object_id = 0, $user_ids = array() ) {
		$cache_key = $this->get_cache_key( $meta_key, $object_id );
		$user_ids  = $this->parse_comma_list( $user_ids );

		return wp_cache_set( $cache_key, $user_ids, 'bbpress_engagements' );
	}

	/**
	 * Delete the user engagement cache for a given meta key and object ID.
	 *
	 * This method exists to provide backwards compatibility with bbPress 2.5,
	 * which had caching surrounding the FIND_IN_SET queries in usermeta.
	 *
	 * @since 2.6.3 bbPress (r6991)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  Meta key.
	 * @param int    $object_id Object Id.
	 *
	 * @return mixed Results from cache delete.
	 */
	private function cache_delete( $meta_key = '', $object_id = 0 ) {
		$cache_key = $this->get_cache_key( $meta_key, $object_id );

		return wp_cache_delete( $cache_key, 'bbpress_engagements' );
	}

	/**
	 * Turn a comma-separated string into an array of integers.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $results The comma-separated results.
	 *
	 * @return array
	 */
	private function parse_comma_list( $results = '' ) {
		return array_filter( wp_parse_id_list( $results ) );
	}

	/**
	 * Add a user id to an object.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 * @param bool   $unique    Whether meta key should be unique to the object.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function add_user_to_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post', $unique = false ) {
		$retval     = false;
		$option_key = $this->get_user_option_key( $meta_key, $object_id );
		$object_ids = $this->parse_comma_list( get_user_option( $option_key, $user_id ) );
		$exists     = array_search( $object_id, $object_ids, true );

		// Not already added, so add it.
		if ( false === $exists ) {
			$object_ids[] = $object_id;
			$object_ids   = implode( ',', $this->parse_comma_list( $object_ids ) );
			$retval       = update_user_option( $user_id, $option_key, $object_ids );

			// Delete cache if successful (accounts for int & true).
			if ( false !== $retval ) {
				$this->cache_delete( $meta_key, $object_id );
			}
		}

		// True if added, or false if not.
		return $retval;
	}

	/**
	 * Remove a user id from an object.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
		$retval     = false;
		$option_key = $this->get_user_option_key( $meta_key, $object_id );
		$object_ids = $this->parse_comma_list( get_user_option( $option_key, $user_id ) );
		$exists     = array_search( $object_id, $object_ids, true );

		// Exists, so remove it.
		if ( false !== $exists ) {
			unset( $object_ids[ $exists ] );

			$object_ids = implode( ',', $this->parse_comma_list( $object_ids ) );
			$retval     = ! empty( $object_ids )
				? update_user_option( $user_id, $option_key, $object_ids )
				: delete_user_option( $user_id, $option_key );

			// Delete cache if successful (accounts for int & true).
			if ( false !== $retval ) {
				$this->cache_delete( $meta_key, $object_id );
			}
		}

		// True if removed, or false if not.
		return $retval;
	}

	/**
	 * Remove a user id from all objects.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $user_id   The user id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_user_from_all_objects( $user_id = 0, $meta_key = '', $meta_type = 'post' ) {

		// Get the key.
		$option_key = $this->get_user_option_key( $meta_key );

		// Get the option.
		$object_ids = $this->parse_comma_list( get_user_option( $option_key, $user_id ) );

		// Attempt to delete the user option.
		$retval = delete_user_option( $user_id, $option_key );

		// Try to delete caches, but only if everything else succeeded.
		if ( ! empty( $retval ) && ! empty( $object_ids ) ) {
			foreach ( $object_ids as $object_id ) {
				$this->cache_delete( $meta_key, $object_id );
			}
		}

		// Return true if user was removed, or false if not.
		return $retval;
	}

	/**
	 * Remove an object from all users.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_object_from_all_users( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {

		// Query for users.
		$user_ids = $this->get_users_for_object( $object_id, $meta_key, $meta_type );
		$u_count  = count( $user_ids );

		// Count number of removals.
		$removed = array();
		$r_count = 0;

		// Users have engaged, so remove them.
		if ( ! empty( $u_count ) ) {

			// Loop through users and remove them from the object.
			foreach ( $user_ids as $user_id ) {
				$removed[] = $this->remove_user_from_object( $object_id, $user_id, $meta_key, $meta_type );
			}

			// Count the removed users.
			$r_count = count( $removed );
		}

		// Return true if successfully removed from all users.
		return ( $r_count === $u_count );
	}

	/**
	 * Remove all users from all objects.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param string $meta_key  The relationship key.
	 * @param string $meta_type The relationship type (usually 'post').
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function remove_all_users_from_all_objects( $meta_key = '', $meta_type = 'post' ) {

		// Query for users.
		$option_key = $this->get_user_option_key( $meta_key, 0, true );
		$bbp_db     = bbp_db();
		$user_ids   = $bbp_db->get_col( "SELECT user_id FROM {$bbp_db->usermeta} WHERE meta_key = '{$option_key}'" );
		$u_count    = count( $user_ids );

		// Count number of removals.
		$removed = array();
		$r_count = 0;

		// Users have engaged, so remove them.
		if ( ! empty( $u_count ) ) {

			// Loop through users and remove their user options.
			foreach ( $user_ids as $user_id ) {
				$removed[] = $this->remove_user_from_all_objects( $user_id, $meta_key );
			}

			// Count the removed users.
			$r_count = count( $removed );
		}

		// Return true if successfully removed from all users.
		return ( $r_count === $u_count );
	}

	/**
	 * Get users of an object.
	 *
	 * The database queries in this function were cached in bbPress versions
	 * older than 2.6, but no longer are to avoid cache pollution.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param int    $object_id The object id.
	 * @param string $meta_key  The key used to index this relationship.
	 * @param string $meta_type The type of meta to look in.
	 *
	 * @return array Returns ids of users.
	 */
	public function get_users_for_object( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {

		// Try to get user IDs from cache.
		$user_ids = $this->cache_get( $meta_key, $object_id );

		// Cache is empty, so hit the database.
		if ( false === $user_ids ) {
			$option_key = $this->get_user_option_key( $meta_key, $object_id, true );
			$bbp_db     = bbp_db();
			$user_ids   = $bbp_db->get_col( "SELECT user_id FROM {$bbp_db->usermeta} WHERE meta_key = '{$option_key}' and FIND_IN_SET('{$object_id}', meta_value) > 0" );

			// Always cache results (even if empty, to prevent multiple misses).
			$this->cache_set( $meta_key, $object_id, $user_ids );
		}

		// Return parsed IDs.
		return $this->parse_comma_list( $user_ids );
	}

	/**
	 * Get the part of the query responsible for JOINing objects to relationships.
	 *
	 * @since 2.6.0 bbPress (r6844)
	 * @since BuddyBoss 2.3.4
	 *
	 * @param array  $args        Default query arguments.
	 * @param string $context_key Additional context.
	 * @param string $meta_key    The relationship key.
	 * @param string $meta_type   The relationship type (usually 'post').
	 *
	 * @return array
	 */
	public function get_query( $args = array(), $context_key = '', $meta_key = '', $meta_type = 'post' ) {
		$user_id    = bbp_get_user_id( $args, true, true );
		$option_key = $this->get_user_option_key( $meta_key );
		$object_ids = $this->parse_comma_list( get_user_option( $option_key, $user_id ) );

		// Maybe trick WP_Query into ".ID IN (0)" to return no results.
		if ( empty( $object_ids ) ) {
			$object_ids = array( 0 );
		}

		// Maybe include these post IDs.
		$args = array(
			'post__in' => $object_ids,
		);

		// Parse arguments.
		return bbp_parse_args( $args, array(), $context_key );
	}
}
