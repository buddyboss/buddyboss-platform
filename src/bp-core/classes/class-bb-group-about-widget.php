<?php
/**
 * BuddyBoss About Group Widget for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Group About Widget.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Group_About_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		parent::__construct(
			'bb_group_about_widget',
			esc_html__( 'About Group', 'buddyboss' ),
			array( 'description' => esc_html__( 'Displays group details.', 'buddyboss' ) )
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

		$group_id = function_exists( 'bp_get_current_group_id' ) ? bp_get_current_group_id() : 0;
		if ( empty( $group_id ) ) {
			return;
		}

		$group = groups_get_group( $group_id );

		$bb_rl_group = new BB_Group_Readylaunch();

		echo wp_kses_post( $args['before_widget'] );
		?>
		<div class="bb-group-about-widget">
			<?php
			$bb_rl_group->bb_rl_get_current_group_info(
				array(
					'group_id' => $group_id,
					'group'    => $group,
					'action'   => 'widget',
				)
			);
			?>
		</div>
		<?php
		echo wp_kses_post( $args['after_widget'] );
	}
}
