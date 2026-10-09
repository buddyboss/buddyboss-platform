<?php
/**
 * BuddyBoss Platform — moved media features admin notice.
 *
 * Videos, Documents and Animated GIFs (GIPHY) moved out of Platform into the
 * BuddyBoss Add-ons plugin. Sites that already have that content keep it in
 * the database, but it stays hidden from members until BuddyBoss Add-ons is
 * installed, active and licensed for the feature.
 *
 * Conditions for showing the notice (ALL must be true):
 *   1. The current user can manage the community (`bp_moderate`).
 *   2. The feature's code is NOT loaded — videos/documents: nothing claims the
 *      component through the `bb_component_directory_available` filter;
 *      GIFs: {@see bb_giphy_provider_available()} is false.
 *   3. The site has stored content of that type (videos in `bp_media` with type
 *      `video`, rows in `bp_document`, or `_gif_data` meta on activity, messages
 *      or forum posts).
 *
 * The call to action adapts to the Add-ons state: get/install, activate, or
 * check the license. It disappears as soon as the feature is available again.
 * Admins may snooze it for 30 days.
 *
 * @package BuddyBoss\Core\Administration
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin file of the BuddyBoss Add-ons plugin that provides the moved features.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_MOVED_MEDIA_FEATURES_ADDONS_PLUGIN = 'buddyboss-addons/buddyboss-addons.php';

/**
 * User meta key holding the time the notice was last dismissed.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_MOVED_MEDIA_FEATURES_NOTICE_DISMISSED_META = 'bb_moved_media_features_notice_dismissed';

/**
 * Count the stored videos, documents and GIFs on this site.
 *
 * The feature code is not loaded when this matters, so the counts are read
 * straight from the tables. They only change while the features are active
 * (which hides the notice), so the result is cached for 12 hours.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array{video: int, document: int, gif: int} Number of stored items per feature.
 */
function bb_moved_media_features_get_stored_counts() {
	$counts = get_transient( 'bb_moved_media_features_stored_counts' );

	if ( is_array( $counts ) && isset( $counts['video'], $counts['document'], $counts['gif'] ) ) {
		return $counts;
	}

	global $wpdb;

	$prefix = bp_core_get_table_prefix();
	$counts = array(
		'video'    => 0,
		'document' => 0,
		'gif'      => 0,
	);

	if ( bb_moved_media_features_table_exists( $prefix . 'bp_media' ) ) {
		$counts['video'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$prefix}bp_media WHERE type = %s", 'video' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted BP table name; counts are cached in a transient.
	}

	if ( bb_moved_media_features_table_exists( $prefix . 'bp_document' ) ) {
		$counts['document'] = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$prefix}bp_document" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted BP table name; counts are cached in a transient.
	}

	// GIFs live as `_gif_data` meta on activity, messages and forum posts.
	foreach ( array( $prefix . 'bp_activity_meta', $prefix . 'bp_messages_meta', $wpdb->postmeta ) as $meta_table ) {
		if ( bb_moved_media_features_table_exists( $meta_table ) ) {
			$counts['gif'] += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$meta_table} WHERE meta_key = %s", '_gif_data' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name; counts are cached in a transient.
		}
	}

	set_transient( 'bb_moved_media_features_stored_counts', $counts, 12 * HOUR_IN_SECONDS );

	return $counts;
}

/**
 * Whether a database table exists.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $table Full table name.
 *
 * @return bool
 */
function bb_moved_media_features_table_exists( $table ) {
	global $wpdb;

	return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

/**
 * Get the features whose stored content is currently hidden.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array<string, int> Feature key ('video', 'document', 'gif') => number of
 *                            stored items, for every unavailable feature that has content.
 */
function bb_moved_media_features_get_hidden_content() {
	$unavailable = array(
		'video'    => ! bb_is_component_directory_available( 'video' ),
		'document' => ! bb_is_component_directory_available( 'document' ),
		'gif'      => function_exists( 'bb_giphy_provider_available' ) && ! bb_giphy_provider_available(),
	);

	if ( ! in_array( true, $unavailable, true ) ) {
		return array();
	}

	$counts = bb_moved_media_features_get_stored_counts();
	$hidden = array();

	foreach ( $unavailable as $feature => $is_unavailable ) {
		if ( $is_unavailable && ! empty( $counts[ $feature ] ) ) {
			$hidden[ $feature ] = (int) $counts[ $feature ];
		}
	}

	return $hidden;
}

/**
 * Get the install/activation state of the BuddyBoss Add-ons plugin.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string One of 'not_installed', 'inactive' or 'active'.
 */
function bb_moved_media_features_get_addons_state() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( BB_MOVED_MEDIA_FEATURES_ADDONS_PLUGIN ) ) {
		return 'active';
	}

	if ( file_exists( WP_PLUGIN_DIR . '/' . BB_MOVED_MEDIA_FEATURES_ADDONS_PLUGIN ) ) {
		return 'inactive';
	}

	return 'not_installed';
}

/**
 * Get the pricing URL used by the moved media features upsells.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $content Optional. `utm_content` value identifying the placement.
 *
 * @return string
 */
function bb_moved_media_features_get_upgrade_url( $content = 'admin-notice' ) {
	$url = add_query_arg(
		array(
			'utm_source'   => 'product',
			'utm_medium'   => 'platform-plugin',
			'utm_campaign' => 'moved-media-features',
			'utm_content'  => sanitize_key( $content ),
		),
		'https://www.buddyboss.com/pricing/'
	);

	/**
	 * Filters the pricing URL of the moved media features upsells.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $url     Upgrade URL.
	 * @param string $content Placement identifier.
	 */
	return (string) apply_filters( 'bb_moved_media_features_upgrade_url', $url, $content );
}

/**
 * Whether the current user snoozed the notice within the last 30 days.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_moved_media_features_notice_is_snoozed() {
	$dismissed = (int) get_user_meta( get_current_user_id(), BB_MOVED_MEDIA_FEATURES_NOTICE_DISMISSED_META, true );

	return $dismissed > 0 && ( time() - $dismissed ) < 30 * DAY_IN_SECONDS;
}

/**
 * Render the moved media features notice.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_moved_media_features_render_notice() {
	if ( ! bp_current_user_can( 'bp_moderate' ) || bb_moved_media_features_notice_is_snoozed() ) {
		return;
	}

	$hidden = bb_moved_media_features_get_hidden_content();
	if ( empty( $hidden ) ) {
		return;
	}

	$features = array();
	$items    = array();
	if ( isset( $hidden['video'] ) ) {
		$features[] = __( 'Videos', 'buddyboss' );
		/* translators: %s: Number of videos. */
		$items[] = sprintf( _n( '%s video', '%s videos', $hidden['video'], 'buddyboss' ), number_format_i18n( $hidden['video'] ) );
	}
	if ( isset( $hidden['document'] ) ) {
		$features[] = __( 'Documents', 'buddyboss' );
		/* translators: %s: Number of documents. */
		$items[] = sprintf( _n( '%s document', '%s documents', $hidden['document'], 'buddyboss' ), number_format_i18n( $hidden['document'] ) );
	}
	if ( isset( $hidden['gif'] ) ) {
		$features[] = __( 'Animated GIFs', 'buddyboss' );
		/* translators: %s: Number of posts, comments and messages with a GIF. */
		$items[] = sprintf( _n( '%s GIF', '%s GIFs', $hidden['gif'], 'buddyboss' ), number_format_i18n( $hidden['gif'] ) );
	}

	$state       = bb_moved_media_features_get_addons_state();
	$upgrade_url = bb_moved_media_features_get_upgrade_url();
	$addons_url  = bp_get_admin_url( 'admin.php?page=buddyboss-addons' );

	if ( 'inactive' === $state ) {
		$message   = __( 'Activate the BuddyBoss Add-ons plugin to show them to your members again.', 'buddyboss' );
		$primary   = array(
			'label' => __( 'Activate BuddyBoss Add-ons', 'buddyboss' ),
			'url'   => wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( BB_MOVED_MEDIA_FEATURES_ADDONS_PLUGIN ) ), 'activate-plugin_' . BB_MOVED_MEDIA_FEATURES_ADDONS_PLUGIN ),
		);
		$secondary = array(
			'label'    => __( 'View plans', 'buddyboss' ),
			'url'      => $upgrade_url,
			'external' => true,
		);
	} elseif ( 'active' === $state ) {
		$message   = __( 'Your current BuddyBoss license does not include them. Check your license or upgrade your plan to show them to your members again.', 'buddyboss' );
		$primary   = array(
			'label'    => __( 'Upgrade plan', 'buddyboss' ),
			'url'      => $upgrade_url,
			'external' => true,
		);
		$secondary = array(
			'label' => __( 'Manage license', 'buddyboss' ),
			'url'   => $addons_url,
		);
	} else {
		$message   = __( 'Install the BuddyBoss Add-ons plugin, included with paid BuddyBoss plans, to show them to your members again.', 'buddyboss' );
		$primary   = array(
			'label'    => __( 'View plans', 'buddyboss' ),
			'url'      => $upgrade_url,
			'external' => true,
		);
		$secondary = array(
			'label' => __( 'Already have a plan? Install BuddyBoss Add-ons', 'buddyboss' ),
			'url'   => $addons_url,
		);
	}
	?>
	<div class="notice notice-warning is-dismissible bb-moved-media-features-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bb_dismiss_moved_media_features_notice' ) ); ?>">
		<p>
			<strong>
				<?php
				printf(
					/* translators: %s: Feature names, e.g. "Videos, Documents and Animated GIFs". */
					esc_html__( '%s now come from the BuddyBoss Add-ons plugin', 'buddyboss' ),
					esc_html( wp_sprintf( '%l', $features ) )
				);
				?>
			</strong>
		</p>
		<p>
			<?php
			printf(
				/* translators: 1: Item counts, e.g. "12 videos, 3 documents and 5 GIFs". 2: Call to action sentence. */
				esc_html__( 'Your community has %1$s that are currently hidden from members. %2$s', 'buddyboss' ),
				'<strong>' . esc_html( wp_sprintf( '%l', $items ) ) . '</strong>',
				esc_html( $message )
			);
			?>
		</p>
		<p>
			<a href="<?php echo esc_url( $primary['url'] ); ?>" class="button button-primary" <?php echo ! empty( $primary['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $primary['label'] ); ?></a>
			<a href="<?php echo esc_url( $secondary['url'] ); ?>" class="button" <?php echo ! empty( $secondary['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $secondary['label'] ); ?></a>
		</p>
	</div>
	<script>
		( function () {
			var notice = document.querySelector( '.bb-moved-media-features-notice' );
			if ( ! notice ) {
				return;
			}
			notice.addEventListener( 'click', function ( event ) {
				if ( ! event.target.classList.contains( 'notice-dismiss' ) ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', 'bb_dismiss_moved_media_features_notice' );
				data.append( 'nonce', notice.getAttribute( 'data-nonce' ) );
				window.fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', credentials: 'same-origin', body: data } );
			} );
		} )();
	</script>
	<?php
}
add_action( 'admin_notices', 'bb_moved_media_features_render_notice' );

/**
 * Snooze the moved media features notice for the current user.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_moved_media_features_dismiss_notice() {
	check_ajax_referer( 'bb_dismiss_moved_media_features_notice', 'nonce' );

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error();
	}

	update_user_meta( get_current_user_id(), BB_MOVED_MEDIA_FEATURES_NOTICE_DISMISSED_META, time() );

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_dismiss_moved_media_features_notice', 'bb_moved_media_features_dismiss_notice' );
