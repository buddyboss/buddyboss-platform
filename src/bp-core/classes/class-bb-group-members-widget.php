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
		<div class="bb-rl-group-members-widget">
			<ul class="group-members-tabs">
				<li class="active" data-tab="active"><?php esc_html_e( 'Active', 'buddyboss' ); ?></li>
				<li data-tab="new"><?php esc_html_e( 'New', 'buddyboss' ); ?></li>
				<li data-tab="popular"><?php esc_html_e( 'Popular', 'buddyboss' ); ?></li>
			</ul>
			<div class="group-members-list" data-tab-content="active">
				<?php $this->render_members_list( 'active' ); ?>
			</div>
			<div class="group-members-list" data-tab-content="new" style="display:none;">
				<?php $this->render_members_list( 'newest' ); ?>
			</div>
			<div class="group-members-list" data-tab-content="popular" style="display:none;">
				<?php $this->render_members_list( 'popular' ); ?>
			</div>
			<a href="<?php echo esc_url( bp_get_group_permalink() . 'members/' ); ?>" class="see-all-link"><?php esc_html_e( 'See all', 'buddyboss' ); ?></a>
		</div>
		<?php
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Render the members list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type The type of members to display.
	 */
	private function render_members_list( $type ) {
		$args = array(
			'per_page' => 5,
			'group_id' => bp_get_current_group_id(),
			'type'     => $type,
		);
		if ( bp_group_has_members( $args ) ) {
			echo '<ul class="members-list">';
			while ( bp_group_members() ) {
				bp_group_the_member();
				echo '<li>';
				echo bp_get_group_member_avatar();
				echo '<span class="member-name">' . esc_html( bp_get_group_member_name() ) . '</span>';
				echo '<span class="member-last-active"></span>';
				echo '</li>';
			}
			echo '</ul>';
		}
	}
}
