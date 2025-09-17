<?php
/**
 * BP_Bbp_Gdpr_Topics base class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Bbp_Gdpr_Topics
 */
class BP_Bbp_Gdpr_Topics {

	public $post_type = 'topic';

	/**
	 * BP_Bbp_Gdpr_Topics constructor.
	 */
	public function __construct() {

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erase_exporter' ), 10 );
	}

	/**
	 * Register forum topic exporter.
	 *
	 * @param $exporters
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function register_exporter( $exporters ) {
		$exporters['bbp-topic'] = array(
			'exporter_friendly_name' => __( 'Forum Discussions', 'buddyboss' ),
			'callback'               => array( $this, 'topics_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register forum topic deletion.
	 *
	 * @param $erasers
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed
	 */
	function erase_exporter( $erasers ) {
		$erasers['bbp-topic'] = array(
			'eraser_friendly_name' => __( 'Forum Discussions', 'buddyboss' ),
			'callback'             => array( $this, 'topics_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Export member created forum topics.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function topics_exporter( $email_address, $page = 1 ) {
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

		$topics_details = $this->get_topics( $user, $page, $per_page );
		$total          = isset( $topics_details['total'] ) ? $topics_details['total'] : 0;
		$topics         = isset( $topics_details['topics'] ) ? $topics_details['topics'] : array();

		if ( $total > 0 ) {
			foreach ( $topics as $topic ) {
				$item_id = "bbp-topic-{$topic->ID}";

				$group_id = 'bbp-topics';

				$group_label = __( 'Forum Discussions', 'buddyboss' );

				$permalink = get_permalink( $topic->ID );

				// Plugins can add as many items in the item data array as they want
				$data = array(
					array(
						'name'  => __( 'Discussion Author', 'buddyboss' ),
						'value' => $user->display_name,
					),
					array(
						'name'  => __( 'Discussion Author Email', 'buddyboss' ),
						'value' => $user->user_email,
					),
					array(
						'name'  => __( 'Discussion Title', 'buddyboss' ),
						'value' => $topic->post_title,
					),
					array(
						'name'  => __( 'Discussion Content', 'buddyboss' ),
						'value' => $topic->post_content,
					),
					array(
						'name'  => __( 'Discussion Date', 'buddyboss' ),
						'value' => $topic->post_date,
					),
					array(
						'name'  => __( 'Discussion URL', 'buddyboss' ),
						'value' => $permalink,
					),
					array(
						'name'  => __( 'Discussion Name', 'buddyboss' ),
						'value' => get_the_title( $topic->post_parent ),
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
	 * Get forum topics created by member.
	 *
	 * @param $user
	 * @param $page
	 * @param $per_page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array|bool
	 */
	function get_topics( $user, $page, $per_page ) {
		$pp_args = array(
			'post_type'      => $this->post_type,
			'author'         => $user->ID,
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		$the_query = new \WP_Query( $pp_args );

		if ( $the_query->have_posts() ) {
			return array(
				'topics' => $the_query->posts,
				'total'  => $the_query->post_count,
			);
		}

		return false;
	}

	/**
	 * Delete forum topics created by member.
	 *
	 * @param $email_address
	 * @param int           $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function topics_eraser( $email_address, $page = 1 ) {
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

		$items = $this->get_topics( $user, 1, $per_page );

		if ( ! $items ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$total  = isset( $items['total'] ) ? $items['total'] : 0;
		$topics = ! empty( $items['topics'] ) ? $items['topics'] : array();

		if ( $total ) {
			foreach ( (array) $topics as $topic ) {
				$attachments = get_posts(
					array(
						'post_type'              => 'attachment',
						'posts_per_page'         => - 1,
						'post_parent'            => $topic->ID,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);

				if ( $attachments ) {
					foreach ( $attachments as $attachment ) {
						wp_delete_post( $attachment->ID, true );
					}
				}
				wp_delete_post( $topic->ID, true );
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
