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

		$group_args = array(
			'user_id'  => bp_loggedin_user_id(),
			'type'     => 'active',
			'per_page' => 5,
			'max'      => 5,
		);
		if ( bp_group_has_members( $group_args ) ) {
			?>
			<div class="item-options" id="groups-members-list-options">
				<a href="" id="active-groups-members"
					<?php
					if ( 'active' === $instance['group_members_default'] ) {
						?>
						class="selected"
						<?php
					}
					?>
					><?php esc_html_e( 'Active', 'buddyboss' ); ?>
				</a>
				<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
				<a href="" id="newest-groups-members"
					<?php
					if ( 'newest' === $instance['group_members_default'] ) {
						?>
						class="selected"
						<?php
					}
					?>
					><?php esc_html_e( 'New', 'buddyboss' ); ?>
				</a>
				<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
				<a href="" id="popular-groups-members" 
					<?php
					if ( 'popular' === $instance['group_members_default'] ) {
						?>
						class="selected"
						<?php
					}
					?>
					><?php esc_html_e( 'Popular', 'buddyboss' ); ?>
				</a>	
			</div>
			<ul id="groups-members-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
			<?php
			while ( bp_group_members() ) :
				bp_group_the_member();
				?>
				<li>
					<?php echo wp_kses_post( bp_get_group_member_avatar() ); ?>
					<span class="member-name"><?php echo esc_html( bp_get_group_member_name() ); ?></span>
					<?php bb_user_presence_html( bp_get_group_member_id() ); ?>
				</li>
				<?php endwhile; ?>
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
}
