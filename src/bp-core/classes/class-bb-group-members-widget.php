<?php
/**
 * BuddyBoss Group Members Widget for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Group Members Widget.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Group_Members_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 2.9.00
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
	 * Maximum number of members a single widget request may return.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @var int
	 */
	const MAX_MEMBERS = 20;

	/**
	 * Map a widget tab / filter value to the member query `type`.
	 *
	 * The "Active" tab lists recently active members (BP_User_Query `active`, the
	 * same sort the members directory uses for "Recently Active"). It used to query
	 * `online`, whose SQL window (presence interval + time span, ~80s) is shorter
	 * than the interval at which presence is written to the DB (300s), so it was
	 * empty most of the time. The legacy `online` value is still accepted from
	 * cached pages and mapped to `active`.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @param string $filter Requested filter.
	 *
	 * @return string One of `active`, `last_joined` or `popular`.
	 */
	public static function bb_get_query_type( $filter ) {
		$filter = is_string( $filter ) ? sanitize_key( $filter ) : '';

		if ( in_array( $filter, array( 'active', 'last_joined', 'popular' ), true ) ) {
			return $filter;
		}

		// 'online' (pre-3.7.0 markup) and anything unknown fall back to the default tab.
		return 'active';
	}

	/**
	 * Build the group member query args used by both the first render and the tab AJAX.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @param int    $group_id Group ID.
	 * @param string $filter   Requested tab filter.
	 * @param int    $max      Number of members to return.
	 *
	 * @return array
	 */
	public static function bb_get_query_args( $group_id, $filter, $max ) {
		$max = absint( $max );
		if ( $max < 1 ) {
			$max = 5;
		}

		return array(
			'group_id'            => absint( $group_id ),
			'type'                => self::bb_get_query_type( $filter ),
			'max'                 => min( $max, self::MAX_MEMBERS ),
			'page'                => 1,
			'exclude_admins_mods' => 0,
			'search_terms'        => '', // Never pick up a members search from the request.
		);
	}

	/**
	 * Whether the current user may see the members of a group.
	 *
	 * Uses the same predicate as the group screens (`bp_group_is_visible()`):
	 * public groups are open to everyone, private/hidden groups only to members
	 * and community moderators.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return bool
	 */
	public static function bb_can_view_group_members( $group_id ) {
		$group_id = absint( $group_id );
		if ( empty( $group_id ) || ! function_exists( 'groups_get_group' ) ) {
			return false;
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			return false;
		}

		return (bool) bp_group_is_visible( $group );
	}

	/**
	 * Render the member list items (or the empty state) for the given query args.
	 *
	 * @since BuddyBoss 3.7.0
	 *
	 * @param array $query_args Args for bp_group_has_members().
	 *
	 * @return string List item HTML. Never empty.
	 */
	public static function bb_get_members_list_html( $query_args ) {
		ob_start();

		if ( bp_group_has_members( $query_args ) ) {
			while ( bp_group_members() ) :
				bp_group_the_member();
				$member_id = bp_get_group_member_id();
				?>
				<li class="bb-rl-group-member-item">
					<div class="item-avatar">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>"<?php echo bb_get_hover_card_profile_attr( $member_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>>
							<?php echo wp_kses_post( bp_get_group_member_avatar() ); ?>
							<?php bb_user_presence_html( $member_id ); ?>
						</a>
					</div>
					<div class="item-content">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>"<?php echo bb_get_hover_card_profile_attr( $member_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>>
							<span class="member-name"><?php echo esc_html( bp_get_group_member_name() ); ?></span>
						</a>
						<span class="member-active"><?php echo esc_html( bp_get_last_activity( $member_id ) ); ?></span>
					</div>
				</li>
				<?php
			endwhile;
		} else {
			?>
			<li class="bb-rl-group-members-empty widget-error">
				<?php esc_html_e( 'There are no members to display.', 'buddyboss' ); ?>
			</li>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Display the widget.
	 *
	 * @since BuddyBoss 2.9.00
	 * @since BuddyBoss 3.7.0 Always renders the tabs, lists recently active members and hides the widget when the group is not visible.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_current_group_id();

		// Do not list the members of a private/hidden group to non-members.
		if ( ! self::bb_can_view_group_members( $group_id ) ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] );

		$default_tab = 'active';
		$max         = 5;
		$nonce       = wp_create_nonce( 'groups_widget_groups_members_list' );
		$tabs        = array(
			'active'      => array(
				'id'    => 'bb-rl-active-groups-members',
				'label' => __( 'Active', 'buddyboss' ),
			),
			'last_joined' => array(
				'id'    => 'bb-rl-newest-groups-members',
				'label' => __( 'New', 'buddyboss' ),
			),
			'popular'     => array(
				'id'    => 'bb-rl-popular-groups-members',
				'label' => __( 'Popular', 'buddyboss' ),
			),
		);
		?>
		<div class="widget-header">
			<h2 class="widget-title"><?php esc_html_e( 'Group members', 'buddyboss' ); ?></h2>
			<a href="<?php echo esc_url( bp_get_group_permalink( groups_get_group( $group_id ) ) ); ?>" class="widget-link">
				<?php esc_html_e( 'See all', 'buddyboss' ); ?>
			</a>
		</div>
		<div class="bb-rl-group-members-list-options item-options" id="bb-rl-groups-members-list-options">
			<?php
			foreach ( $tabs as $filter => $tab ) {
				?>
				<a href="#" id="<?php echo esc_attr( $tab['id'] ); ?>"<?php echo ( $default_tab === $filter ) ? ' class="selected"' : ''; ?>
					data-group-attr="
					<?php
					echo esc_attr(
						wp_json_encode(
							array(
								'filter'   => $filter,
								'group_id' => $group_id,
								'max'      => $max,
								'nonce'    => $nonce,
							)
						)
					);
					?>
					"><?php echo esc_html( $tab['label'] ); ?>
				</a>
				<?php
			}
			?>
		</div>
		<ul id="bb-rl-group-members-list" class="item-list bb-rl-group-members-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
			<?php echo self::bb_get_members_list_html( self::bb_get_query_args( $group_id, $default_tab, $max ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while rendering. ?>
		</ul>
		<?php
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * AJAX callback for group members list.
	 *
	 * @since BuddyBoss 2.9.00
	 * @since BuddyBoss 3.7.0 Checks group visibility and uses the same query args as the first render.
	 */
	public function groups_ajax_widget_groups_members_list() {
		check_ajax_referer( 'groups_widget_groups_members_list', '_wpnonce' );

		$filter   = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'active';
		$max      = isset( $_POST['max'] ) ? absint( wp_unslash( $_POST['max'] ) ) : 5;
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : bp_get_current_group_id();

		// The nonce is shared by every guest; the group must be checked on its own.
		if ( ! self::bb_can_view_group_members( $group_id ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'html'    => '',
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
				'html'    => self::bb_get_members_list_html( self::bb_get_query_args( $group_id, $filter, $max ) ),
			)
		);
	}
}
