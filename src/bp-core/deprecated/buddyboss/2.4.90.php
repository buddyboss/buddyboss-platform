<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.4.90
 */

/**
 * Background job to repair user profile slug.
 *
 * @since BuddyBoss 2.3.3
 * @deprecated BuddyBoss 2.4.90
 *
 * @param int $paged Number of page.
 *
 * @return void
 */
function bb_repair_member_unique_slug( $paged = 1 ) {
	_deprecated_function( __FUNCTION__, '2.4.90' );

	global $bp_background_updater;

	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page = 50;
	$offset   = ( ( $paged - 1 ) * $per_page );

	$user_ids = get_users(
		array(
			'fields'     => 'ids',
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'     => 'bb_profile_slug',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	$bp_background_updater->data(
		array(
			array(
				'callback' => 'bb_remove_duplicate_member_slug',
				'args'     => array( $user_ids, $paged ),
			),
		)
	);
	$bp_background_updater->save()->schedule_event();
}

/**
 * Delete duplicate bb_profile_slug_ key from the usermeta table.
 *
 * @since BuddyBoss 2.3.3
 * @deprecated BuddyBoss 2.4.90
 *
 * @param array $user_ids Array of user ID.
 * @param int   $paged    Number of page.
 *
 * @return void
 */
function bb_remove_duplicate_member_slug( $user_ids, $paged ) {
	_deprecated_function( __FUNCTION__, '2.4.90' );

	global $wpdb;

	foreach ( $user_ids as $user_id ) {
		$unique_slug = bp_get_user_meta( $user_id, 'bb_profile_slug', true );

		$wpdb->query(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bb_profile_slug_%' AND meta_key != %s AND user_id = %d",
				"bb_profile_slug_{$unique_slug}",
				$user_id
			)
		);
	}

	$paged++;
	bb_repair_member_unique_slug( $paged );
}

/**
 * This function will work as migration process which will repair member profile links.
 *
 * @since BuddyBoss 2.3.41
 * @deprecated BuddyBoss 2.4.90
 *
 * @return array|void
 */
function bb_generate_member_profile_links_on_update() {
	_deprecated_function( __FUNCTION__, '2.4.90' );

	if ( ! bp_is_active( 'members' ) ) {
		return;
	}

	global $wpdb, $bp_background_updater;
	$bp_prefix = bp_core_get_table_prefix();

	// Get all users who have not generate unique slug while it runs from background.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT u.ID FROM `{$wpdb->users}` AS u LEFT JOIN `{$wpdb->usermeta}` AS um ON ( u.ID = um.user_id AND um.meta_key = %s ) WHERE um.user_id IS NULL ORDER BY u.ID ASC",
			'bb_profile_slug'
		)
	);

	if ( ! is_wp_error( $user_ids ) && ! empty( $user_ids ) ) {
		foreach ( array_chunk( $user_ids, 50 ) as $chunked_user_ids ) {
			$bp_background_updater->data(
				array(
					array(
						'callback' => 'bb_set_bulk_user_profile_slug',
						'args'     => array( $chunked_user_ids ),
					),
				)
			);
			$bp_background_updater->save()->dispatch();
		}
	}
}
