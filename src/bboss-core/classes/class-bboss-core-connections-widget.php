<?php
/**
 * BuddyBoss Core Connections Widget.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BBoss_Core_Connections_Widget
 *
 * @since BuddyBoss 2.9.00
 */
class BBoss_Core_Connections_Widget extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		parent::__construct(
			'bb_connections_widget',
			esc_html__( 'Connections', 'buddyboss-platform' ),
			array( 'description' => esc_html__( 'Displays user connections (friends) in a grid.', 'buddyboss-platform' ) )
		);
	}

	/**
	 * Widget.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Use displayed user if on profile, otherwise logged-in user.
		$user_id = bboss_displayed_user_id() ? bboss_displayed_user_id() : bboss_loggedin_user_id();
		$max     = 10;

		$friends = function_exists( 'bboss_friends_get_friend_user_ids' )
			? bboss_friends_get_friend_user_ids( $user_id )
			: array();

		// Current: "No connections found, don't display the widget."
		// Suggested: "Early return if user has no connections to prevent empty widget display"
		if ( empty( $friends ) ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] );

		$friends = array_slice( $friends, 0, $max );

		// Build "See all" link for the displayed user.
		$see_all_url = bboss_core_get_user_domain( $user_id ) . bboss_get_friends_slug() . '/';
		?>
		<div class="widget-header">
			<h2 class="widget-title"><?php esc_html_e( 'Connections', 'buddyboss-platform' ); ?></h2>
			<a href="<?php echo esc_url( $see_all_url ); ?>" class="widget-link">
				<?php esc_html_e( 'See all', 'buddyboss-platform' ); ?>
			</a>
		</div>
		<ul class="bb-connections-grid">
			<?php
			foreach ( $friends as $friend_id ) {
				?>
				<li>
					<a href="<?php echo esc_url( bboss_core_get_user_domain( $friend_id ) ); ?>" class="item-avatar bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bboss_core_get_user_displayname( $friend_id ) ); ?>"<?php echo bboss_get_hover_card_profile_attr( $friend_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>>
						<?php
						echo wp_kses_post(
							bboss_core_fetch_avatar(
								array(
									'item_id' => $friend_id,
									'type'    => 'full',
									'width'   => 56,
									'height'  => 56,
									'html'    => true,
								)
							)
						);
						?>
						<?php
						if ( function_exists( 'bboss_user_presence_html' ) ) {
							bboss_user_presence_html( $friend_id );
						}
						?>
					</a>
				</li>
				<?php
			}
			?>
		</ul>
		<?php

		echo wp_kses_post( $args['after_widget'] );
	}
}
