<?php
/**
 * BuddyBoss Moderation Functions.
 *
 * Functions for the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Moderation Core functions
 */

/**
 * Retrieve an Moderation reports.
 *
 * The bp_moderation_get() function shares all arguments with
 * BP_Moderation::get().
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array|string $args See BP_Moderation::get() for description.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 * @see   BP_Moderation::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bp_moderation_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'               => false,
			// Maximum number of results to return.
			'user_id'           => false,
			// Filter moderation reported by particular user.
			'fields'            => 'all',
			'page'              => 1,
			// Page 1 without a per_page will result in no pagination.
			'per_page'          => false,
			// results per page.
			'sort'              => 'DESC',
			'order_by'          => 'date_updated',
			// sort ASC or DESC.
			// Phpcs:ignore
			'meta_query'        => false,
			// Filter by moderation meta. See WP_Meta_Query for format.
			'date_query'        => false,
			// Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,
			'exclude'           => false,
			// Comma-separated list of moderation IDs to exclude.
			'in'                => false,
			// Comma-separated list or array of moderation IDs to which you
			// want to limit the query.
			'exclude_types'     => false,
			// Comma-separated list of moderation item types to exclude.
			'in_types'          => false,
			// Comma-separated list or array of moderation item types to which you
			// want to limit the query.
			'update_meta_cache' => true,
			'display_reporters' => false,
			'count_total'       => false,

			/**
			 * Pass filters as an array -- all filter items can be multiple values comma separated:
			 * array(
			 *     'item_id'       => false, // Item ID to filter on eg. Activity ID, Groups ID, User ID etc.
			 *     'hide_sitewide' => false, // filter by hidden items e.g. 0, 1.
			 *     'blog_id'       => false, // Blog ID to filter on.
			 * );
			 */
			'filter'            => array(),
		),
		'moderation_get'
	);

	$moderation = BP_Moderation::get(
		array(
			'page'              => $r['page'],
			'per_page'          => $r['per_page'],
			'user_id'           => $r['user_id'],
			'max'               => $r['max'],
			'sort'              => $r['sort'],
			'order_by'          => $r['order_by'],
			'meta_query'        => $r['meta_query'], // Phpcs:ignore
			'date_query'        => $r['date_query'],
			'filter_query'      => $r['filter_query'],
			'filter'            => $r['filter'],
			'exclude_types'     => $r['exclude_types'],
			'in_types'          => $r['in_types'],
			'exclude'           => $r['exclude'],
			'in'                => $r['in'],
			'update_meta_cache' => $r['update_meta_cache'],
			'display_reporters' => $r['display_reporters'],
			'count_total'       => $r['count_total'],
			'fields'            => $r['fields'],
		)
	);

	/**
	 * Filters the requested moderation item(s).
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array         $r          Arguments used for the moderation query.
	 *
	 * @param BP_Moderation $moderation Requested moderation object.
	 */
	return apply_filters_ref_array(
		'bp_moderation_get',
		array(
			&$moderation,
			&$r,
		)
	);
}

/**
 * Retrieve sitewide hidden items ids of particular item type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $type Moderation items type.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 */
function bp_moderation_get_sitewide_hidden_item_ids( $type ) {
	return bp_moderation_get(
		array(
			'in_types'          => $type,
			'update_meta_cache' => false,
			'filter'            => array(
				'hide_sitewide' => 1,
			),
		)
	);
}

/**
 * Function to get the moderation content types.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return mixed|void
 */
function bp_moderation_content_types() {

	$content_types = array(
		'activity'    => esc_html__( 'Activity', 'buddyboss' ),
		'document'    => esc_html__( 'Document', 'buddyboss' ),
		'forum_reply' => esc_html__( 'Forum Reply', 'buddyboss' ),
		'forum_topic' => esc_html__( 'Forum Topic', 'buddyboss' ),
		'forum'       => esc_html__( 'Forum', 'buddyboss' ),
		'groups'      => esc_html__( 'Groups', 'buddyboss' ),
		'media'       => esc_html__( 'Media', 'buddyboss' ),
		'user'        => esc_html__( 'User', 'buddyboss' ),
		'message'     => esc_html__( 'Message', 'buddyboss' ),
	);

	return apply_filters( 'bp_moderation_content_types', $content_types );
}

/**
 * Function get content owner id.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param int    $moderation_item_id   content id.
 * @param string $moderation_item_type content type.
 *
 * @return array|int|string
 */
function bp_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type ) {

	switch ( $moderation_item_type ) {
		case 'activity':
			$activity = new BP_Activity_Activity( $moderation_item_id );
			$user_id  = ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
			break;
		case 'document':
			$document = new BP_Document( $moderation_item_id );
			$user_id  = ( ! empty( $document->user_id ) ) ? $document->user_id : 0;
			break;
		case 'forum_reply':
		case 'forum_topic':
		case 'forum':
			$user_id = get_post_field( 'post_author', $moderation_item_id );
			break;
		case 'media':
			$media   = new BP_Media( $moderation_item_id );
			$user_id = ( ! empty( $media->user_id ) ) ? $media->user_id : 0;
			break;
		case 'groups':
			$group   = new BP_Groups_Group( $moderation_item_id );
			$user_id = ( ! empty( $group->creator_id ) ) ? $group->creator_id : 0;
			break;
		case 'user':
			$user_id = $moderation_item_id;
			break;
		case 'message':
			$message = new BP_Messages_Message( $moderation_item_id );
			$user_id = ( ! empty( $message->sender_id ) ) ? $message->sender_id : 0;
			break;
		default:
			$user_id = 0;
	}

	return $user_id;
}

/**
 * Function to get specific moderation content type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $key content type key.
 *
 * @return mixed|void
 */
function bp_get_moderation_content_type( $key ) {

	$content_types = bp_moderation_content_types();

	return apply_filters( 'bp_get_moderation_content_type', key_exists( $key, $content_types ) ? $content_types[ $key ] : '' );
}
