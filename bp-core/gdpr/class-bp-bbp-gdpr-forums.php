<?php
/**
 * BP_Bbp_Gdpr_Forums base class
 *
 * This class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Bbp_Gdpr_Forums
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Bbp_Gdpr_Forums {

	/**
	 * @var string
	 */
	public $post_type = 'forum';

	/**
	 * BBP_GDPR_Forum constructor.
	 */
	public function __construct() {

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erase_exporter' ), 10 );
	}

	/**
	 * Register forum exporter.
	 *
	 * @param $exporters
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function register_exporter( $exporters ) {
		$exporters['bbp-forum'] = array(
			'exporter_friendly_name' => __( 'Forums', 'buddyboss' ),
			'callback'               => array( $this, 'forums_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register Forum data deletion.
	 *
	 * @param $erasers
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function erase_exporter( $erasers ) {
		$erasers['bbp-forum'] = array(
			'eraser_friendly_name' => __( 'Forums', 'buddyboss' ),
			'callback'             => array( $this, 'forums_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Export member created forum data.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function forums_exporter( $email_address, $page = 1 ) {
		$per_page = 500; // Limit to avoid timeout
		$page     = (int) $page;

		$export_items = array();

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array(
				'data' => $export_items,
				'done' => true,
			);
		}

		$forums_details = $this->get_forums( $user, $page, $per_page );
		$total          = isset( $forums_details['total'] ) ? $forums_details['total'] : 0;
		$forums         = isset( $forums_details['forums'] ) ? $forums_details['forums'] : array();

		if ( $total > 0 ) {
			foreach ( $forums as $forum ) {
				$item_id = "bbp-forum-{$forum->ID}";

				$group_id = 'bbp-forums';

				$group_label = __( 'Forums', 'buddyboss' );

				$permalink = get_permalink( $forum->ID );

				// Plugins can add as many items in the item data array as they want
				$data = array(
					array(
						'name'  => __( 'Forum Author', 'buddyboss' ),
						'value' => $user->display_name,
					),
					array(
						'name'  => __( 'Forum Author Email', 'buddyboss' ),
						'value' => $user->user_email,
					),
					array(
						'name'  => __( 'Forum Title', 'buddyboss' ),
						'value' => $forum->post_title,
					),
					array(
						'name'  => __( 'Forum Content', 'buddyboss' ),
						'value' => $forum->post_content,
					),
					array(
						'name'  => __( 'Forum Date', 'buddyboss' ),
						'value' => $forum->post_date,
					),
					array(
						'name'  => __( 'Forum URL', 'buddyboss' ),
						'value' => $permalink,
					),
				);

				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);
			}
		}

		$offset = ( $page - 1 ) * $per_page;

		// Tell core if we have more comments to work on still
		$done = $total < $offset;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Fetch all the user forums.
	 *
	 * @param $user
	 * @param $page
	 * @param $per_page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array|bool
	 */
	function get_forums( $user, $page, $per_page ) {
		$pp_args = array(
			'post_type'      => $this->post_type,
			'author'         => $user->ID,
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		$the_query = new \WP_Query( $pp_args );

		if ( $the_query->have_posts() ) {
			return array(
				'forums' => $the_query->posts,
				'total'  => $the_query->post_count,
			);
		}

		return false;
	}

	/**
	 * Delete all forums created by member.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function forums_eraser( $email_address, $page = 1 ) {
		$per_page = 500; // Limit to avoid timeout
		$page     = (int) $page;

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$items_removed  = false;
		$items_retained = false;
		$messages       = array();

		$items = $this->get_forums( $user, 1, $per_page );

		if ( ! $items ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$total  = isset( $items['total'] ) ? $items['total'] : 0;
		$forums = ! empty( $items['forums'] ) ? $items['forums'] : array();

		if ( $total ) {
			foreach ( (array) $forums as $forum ) {
				$attachments = get_posts(
					array(
						'post_type'              => 'attachment',
						'posts_per_page'         => - 1,
						'post_parent'            => $forum->ID,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);

				if ( $attachments ) {
					foreach ( $attachments as $attachment ) {
						wp_delete_post( $attachment->ID, true );
					}
				}
				wp_delete_post( $forum->ID, true );
				$items_removed = true;
			}
		}

		$offset = ( $page - 1 ) * $per_page;

		// Tell core if we have more comments to work on still
		$done = $total < $offset;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}
}
