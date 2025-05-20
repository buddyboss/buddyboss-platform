<?php
/**
 * BuddyBoss Group Members Widget for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Group Members Widget.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Group_Members_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		parent::__construct(
			'bb_group_members_widget',
			esc_html__( 'Group Members', 'buddyboss' ),
			array( 'description' => esc_html__( 'Displays group members with tabs.', 'buddyboss' ) )
		);

		add_action( 'wp_ajax_widget_groups_members_list', array( $this, 'groups_ajax_widget_groups_members_list' ) );
		add_action( 'wp_ajax_nopriv_widget_groups_members_list', array( $this, 'groups_ajax_widget_groups_members_list' ) );
	}

	/**
	 * Display the widget.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
			return;
		}
		echo wp_kses_post( $args['before_widget'] );

		$group_id    = bp_get_current_group_id();
		$default_tab = 'active';
		$max         = 5;

		if ( 'active' === $default_tab ) {
			$members = $this->bb_get_active_group_members( $group_id, $max );
		} elseif ( 'newest' === $default_tab ) {
			$members = $this->bb_get_new_group_members( $group_id, $max );
		} elseif ( 'popular' === $default_tab ) {
			$members = $this->bb_get_popular_group_members( $group_id, $max );
		}

		$data_attr = array(
			'nonce'    => wp_create_nonce( 'groups_widget_groups_members_list' ),
			'group_id' => $group_id,
			'max'      => $max,
		);
		if ( ! empty( $members ) ) {
			?>
			<div class="widget-header">
				<h2 class="widget-title"><?php esc_html_e( 'Group Members', 'buddyboss' ); ?></h2>
				<a href="<?php echo bp_get_group_permalink(); ?>" class="widget-link">
					<?php esc_html_e( 'See all', 'buddyboss' ); ?>
				</a>
			</div>
			<div class="bb-rl-group-members-list-options item-options" id="bb-rl-groups-members-list-options">
				<a href="#" id="bb-rl-active-groups-members"
					<?php
					if ( 'active' === $default_tab ) {
						?>
						class="selected"
						<?php
					}
					?>
					data-group-attr="<?php echo esc_attr( wp_json_encode( array_merge( $data_attr, array( 'filter' => 'active' ) ) ) ); ?>"
					><?php esc_html_e( 'Active', 'buddyboss' ); ?>
				</a>
				<a href="#" id="bb-rl-newest-groups-members"
					<?php
					if ( 'newest' === $default_tab ) {
						?>
						class="selected"
						<?php
					}
					?>
					data-group-attr="<?php echo esc_attr( wp_json_encode( array_merge( $data_attr, array( 'filter' => 'newest' ) ) ) ); ?>"
					><?php esc_html_e( 'New', 'buddyboss' ); ?>
				</a>
				<a href="#" id="bb-rl-popular-groups-members"
					<?php
					if ( 'popular' === $default_tab ) {
						?>
						class="selected"
						<?php
					}
					?>
					data-group-attr="<?php echo esc_attr( wp_json_encode( array_merge( $data_attr, array( 'filter' => 'popular' ) ) ) ); ?>"
					><?php esc_html_e( 'Popular', 'buddyboss' ); ?>
				</a>	
			</div>
			<ul id="bb-rl-group-members-list" class="item-list bb-rl-group-members-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
				<?php
				$this->group_members_list_html( $members );
				?>
			</ul>
			<?php
		} else {
			?>
			<div class="widget-error">
				<?php esc_html_e( 'There are no members to display.', 'buddyboss' ); ?>
			</div>
			<?php
		}
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Get group members list HTML.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $members Array of group members.
	 */
	public function group_members_list_html( $members ) {
		foreach ( $members as $member ) {
			?>
			<li class="bb-rl-group-member-item">
				<div class="item-avatar">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $member['ID'] ) ); ?>">
						<?php
							echo wp_kses_post(
								bp_core_fetch_avatar(
									array(
										'item_id' => $member['ID'],
										'object'  => 'user',
										'type'    => 'thumb',
										'width'   => 30,
										'height'  => 30,
									)
								)
							);
						?>
						<?php bb_user_presence_html( $member['ID'] ); ?>
					</a>
				</div>
				<div class="item-content">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $member['ID'] ) ); ?>">
						<span class="member-name"><?php echo esc_html( bp_core_get_user_displayname( $member['ID'] ) ); ?></span>
					</a>
					<span class="member-active"><?php echo esc_html( bp_get_last_activity( $member['ID'] ) ); ?></span>
				</div>
			</li>
			<?php
		}
	}

	/**
	 * Get group members sorted by last activity (Active tab).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $group_id The ID of the group.
	 * @param int $limit The number of members to return.
	 * @return array Array of group members.
	 */
	private function bb_get_active_group_members( $group_id, $limit = 5 ) {
		global $wpdb, $bp;
		$sql = $wpdb->prepare(
			"SELECT u.ID, MAX(a.date_recorded) as last_activity
			 FROM {$wpdb->users} u
			 INNER JOIN {$bp->activity->table_name} a ON u.ID = a.user_id
			 WHERE a.component = 'groups'
			   AND a.item_id = %d
			   AND a.type IN ( 'activity_update','activity_comment','joined_group','bbp_topic_create','bbp_reply_create' )
			 GROUP BY u.ID
			 ORDER BY last_activity DESC
			 LIMIT %d",
			$group_id,
			$limit
		);
		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get group members sorted by join date (New tab).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $group_id The ID of the group.
	 * @param int $limit The number of members to return.
	 * @return array Array of group members.
	 */
	private function bb_get_new_group_members( $group_id, $limit = 5 ) {
		global $wpdb, $bp;
		$sql = $wpdb->prepare(
			"SELECT u.ID
			 FROM {$wpdb->users} u
			 INNER JOIN {$bp->activity->table_name} a ON u.ID = a.user_id
			 WHERE a.component = 'groups'
			   AND a.item_id = %d
			   AND a.type IN ( 'joined_group' )
			 GROUP BY u.ID
			 ORDER BY u.ID DESC
			 LIMIT %d",
			$group_id,
			$limit
		);
		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get group members sorted by number of group activities (Popular tab).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $group_id The ID of the group.
	 * @param int $limit The number of members to return.
	 * @return array Array of group members.
	 */
	private function bb_get_popular_group_members( $group_id, $limit = 5 ) {
		global $wpdb, $bp;
		$sql = $wpdb->prepare(
			"SELECT u.ID, COUNT(a.id) as activity_count
			 FROM {$wpdb->users} u
			 INNER JOIN {$bp->activity->table_name} a ON u.ID = a.user_id
			 WHERE a.component = 'groups'
			   AND a.item_id = %d
			   AND a.type IN ( 'activity_update','activity_comment','bbp_topic_create','bbp_reply_create' )
			 GROUP BY u.ID
			 ORDER BY activity_count DESC
			 LIMIT %d",
			$group_id,
			$limit
		);
		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * AJAX callback for group members list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function groups_ajax_widget_groups_members_list() {
		check_ajax_referer( 'groups_widget_groups_members_list', '_wpnonce' );

		$tab      = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'active';
		$max      = isset( $_POST['max'] ) ? absint( wp_unslash( $_POST['max'] ) ) : 5;
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : bp_get_current_group_id();

		if ( 'active' === $tab ) {
			$members = $this->bb_get_active_group_members( $group_id, $max );
		} elseif ( 'newest' === $tab ) {
			$members = $this->bb_get_new_group_members( $group_id, $max );
		} elseif ( 'popular' === $tab ) {
			$members = $this->bb_get_popular_group_members( $group_id, $max );
		}

		ob_start();
		$this->group_members_list_html( $members );
		$html = ob_get_clean();
		wp_send_json_success(
			array(
				'success' => true,
				'html'    => $html,
			)
		);
	}
}
