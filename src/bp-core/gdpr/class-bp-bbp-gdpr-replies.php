<?php
/**
 * BP_Bbp_Gdpr_Replies base class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Bbp_Gdpr_Replies
 */
class BP_Bbp_Gdpr_Replies {

	public $post_type = 'reply';

	/**
	 * BP_Bbp_Gdpr_Replies constructor.
	 */
	public function __construct() {

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erase_exporter' ), 10 );
	}

	/**
	 * Register forum replies exporter.
	 *
	 * @param $exporters
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function register_exporter( $exporters ) {
		$exporters['bbp-reply'] = array(
			'exporter_friendly_name' => __( 'Discussion Replies', 'buddyboss' ),
			'callback'               => array( $this, 'replies_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register forum replies deletion.
	 *
	 * @param $erasers
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function erase_exporter( $erasers ) {
		$erasers['bbp-reply'] = array(
			'eraser_friendly_name' => __( 'Forum Replies', 'buddyboss' ),
			'callback'             => array( $this, 'replies_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Export member created forum replies.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function replies_exporter( $email_address, $page = 1 ) {
		$per_page = 500; // Limit us to avoid timing out
		$page     = (int) $page;

		$export_items = array();

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array(
				'data' => $export_items,
				'done' => true,
			);
		}

		$replies_details = $this->get_replies( $user, $page, $per_page );
		$total           = isset( $replies_details['total'] ) ? $replies_details['total'] : 0;
		$replies         = isset( $replies_details['replies'] ) ? $replies_details['replies'] : array();

		if ( $total > 0 ) {
			foreach ( $replies as $reply ) {
				$item_id = "bbp-reply-{$reply->ID}";

				$group_id = 'bbp-replies';

				$group_label = __( 'Discussion Replies', 'buddyboss' );

				$permalink = get_permalink( $reply->ID );

				$parent_title = get_the_title( $reply->post_parent );

				// Plugins can add as many items in the item data array as they want
				$data = array(
					array(
						'name'  => __( 'Reply Author', 'buddyboss' ),
						'value' => $user->display_name,
					),
					array(
						'name'  => __( 'Reply Author Email', 'buddyboss' ),
						'value' => $user->user_email,
					),
					array(
						'name'  => __( 'Reply Title', 'buddyboss' ),
						'value' => ! empty( $parent_title ) ? __(
							'Reply To: ',
							'buddyboss'
						) . html_entity_decode( $parent_title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) : '',
					),
					array(
						'name'  => __( 'Reply Content', 'buddyboss' ),
						'value' => $reply->post_content,
					),
					array(
						'name'  => __( 'Reply Date', 'buddyboss' ),
						'value' => $reply->post_date,
					),
					array(
						'name'  => __( 'Reply URL', 'buddyboss' ),
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
	 * Get member created forum replies.
	 *
	 * @param $user
	 * @param $page
	 * @param $per_page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array|bool
	 */
	function get_replies( $user, $page, $per_page ) {
		$pp_args = array(
			'post_type'      => $this->post_type,
			'author'         => $user->ID,
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		$the_query = new \WP_Query( $pp_args );

		if ( $the_query->have_posts() ) {
			return array(
				'replies' => $the_query->posts,
				'total'   => $the_query->post_count,
			);
		}

		return false;
	}

	/**
	 * Delete member created forum replies.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function replies_eraser( $email_address, $page = 1 ) {
		$per_page = 500; // Limit us to avoid timing out
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

		$items = $this->get_replies( $user, 1, $per_page );

		if ( ! $items ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$total   = isset( $items['total'] ) ? $items['total'] : 0;
		$replies = ! empty( $items['replies'] ) ? $items['replies'] : array();

		if ( $total ) {
			foreach ( (array) $replies as $reply ) {
				$attachments = get_posts(
					array(
						'post_type'              => 'attachment',
						'posts_per_page'         => - 1,
						'post_parent'            => $reply->ID,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);

				if ( $attachments ) {
					foreach ( $attachments as $attachment ) {
						wp_delete_post( $attachment->ID, true );
					}
				}
				wp_delete_post( $reply->ID, true );
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
