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
	 * Display the widget.
	 *
	 * @since BuddyBoss 2.9.00
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

		$group_args = array(
			'user_id'             => bp_loggedin_user_id(),
			'type'                => 'online',
			'max'                 => 5,
			'exclude_admins_mods' => 0,
		);
		if ( bp_group_has_members( $group_args ) ) {
			?>
			<div class="widget-header">
				<h2 class="widget-title"><?php esc_html_e( 'Group members', 'buddyboss' ); ?></h2>
				<a href="<?php echo esc_url( bp_get_group_permalink() ); ?>" class="widget-link">
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
					data-group-attr="
					<?php
					echo esc_attr(
						wp_json_encode(
							array(
								'filter'   => 'online',
								'group_id' => $group_id,
								'max'      => $max,
								'nonce'    => wp_create_nonce( 'groups_widget_groups_members_list' ),
							)
						)
					);
					?>
					"><?php esc_html_e( 'Active', 'buddyboss' ); ?>
				</a>
				<a href="#" id="bb-rl-newest-groups-members"
					<?php
					if ( 'newest' === $default_tab ) {
						?>
						class="selected"
						<?php
					}
					?>
					data-group-attr="
					<?php
					echo esc_attr(
						wp_json_encode(
							array(
								'filter'   => 'last_joined',
								'group_id' => $group_id,
								'max'      => $max,
								'nonce'    => wp_create_nonce( 'groups_widget_groups_members_list' ),
							)
						)
					);
					?>
					"><?php esc_html_e( 'New', 'buddyboss' ); ?>
				</a>
				<a href="#" id="bb-rl-popular-groups-members"
					<?php
					if ( 'popular' === $default_tab ) {
						?>
						class="selected"
						<?php
					}
					?>
					data-group-attr="
					<?php
					echo esc_attr(
						wp_json_encode(
							array(
								'filter'   => 'popular',
								'group_id' => $group_id,
								'max'      => $max,
								'nonce'    => wp_create_nonce( 'groups_widget_groups_members_list' ),
							)
						)
					);
					?>
					"><?php esc_html_e( 'Popular', 'buddyboss' ); ?>
				</a>	
			</div>
			<ul id="bb-rl-group-members-list" class="item-list bb-rl-group-members-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
				<?php
				while ( bp_group_members() ) :
					bp_group_the_member();
					$member_id = bp_get_group_member_id();
					?>
					<li class="bb-rl-group-member-item">
						<div class="item-avatar">
							<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>">
								<?php echo wp_kses_post( bp_get_group_member_avatar() ); ?>
								<?php bb_user_presence_html( $member_id ); ?>
							</a>
						</div>
						<div class="item-content">
							<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>">
								<span class="member-name"><?php echo esc_html( bp_get_group_member_name() ); ?></span>
							</a>
							<span class="member-active"><?php echo esc_html( bp_get_last_activity( $member_id ) ); ?></span>
						</div>
					</li>
					<?php
				endwhile;
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
	 * AJAX callback for group members list.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function groups_ajax_widget_groups_members_list() {
		check_ajax_referer( 'groups_widget_groups_members_list', '_wpnonce' );

		$tab      = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'active';
		$max      = isset( $_POST['max'] ) ? absint( wp_unslash( $_POST['max'] ) ) : 5;
		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : bp_get_current_group_id();

		// Construct query args array.
		$query_args = array(
			'type'     => $tab,
			'group_id' => $group_id,
			'max'      => $max,
		);

		// Convert to query string.
		$query_string = http_build_query( $query_args );

		if ( bp_group_has_members( $query_string ) ) {
			ob_start();
			while ( bp_group_members() ) :
				bp_group_the_member();
				$member_id = bp_get_group_member_id();
				?>
				<li class="bb-rl-group-member-item">
					<div class="item-avatar">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>">
							<?php echo wp_kses_post( bp_get_group_member_avatar() ); ?>
							<?php bb_user_presence_html( $member_id ); ?>
						</a>
					</div>
					<div class="item-content">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>">
							<span class="member-name"><?php echo wp_kses_post( bp_get_group_member_name() ); ?></span>
						</a>
						<span class="member-active"><?php echo wp_kses_post( bp_get_last_activity( $member_id ) ); ?></span>
					</div>
				</li>
				<?php
			endwhile;
			$html = ob_get_clean();
		}
		wp_send_json_success(
			array(
				'success' => true,
				'html'    => $html,
			)
		);
	}
}
