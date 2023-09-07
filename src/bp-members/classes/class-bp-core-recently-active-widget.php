<?php
/**
 * BuddyPress Members Recently Active widget.
 *
 * @package BuddyBoss\Members\Widgets
 * @since BuddyPress 1.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Recently Active Members Widget.
 *
 * @since BuddyPress 1.0.3
 */
class BP_Core_Recently_Active_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		$name        = __( '(BB) Recently Active Members', 'buddyboss' );
		$description = __( 'Profile photos of recently active members', 'buddyboss' );
		parent::__construct(
			false,
			$name,
			array(
				'description'                 => $description,
				'classname'                   => 'widget_bp_core_recently_active_widget buddypress widget',
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Display the Recently Active widget.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		global $members_template;

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		/**
		 * Filters the title of the Recently Active widget.
		 *
		 * @since BuddyPress 1.8.0
		 * @since BuddyPress 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );

		$refresh_recent_users = '<a href="" class="bs-widget-reload bs-heartbeat-reload hide"><i class="bb-icon-spin6"></i></a>';

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $refresh_recent_users . $args['after_title'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => 'active',
			'per_page'        => $settings['max_members'],
			'max'             => false,
			'populate_extras' => true,
			'search_terms'    => false,
			'exclude'         => ( function_exists( 'bp_get_users_of_removed_member_types' ) && ! empty( bp_get_users_of_removed_member_types() ) ) ? bp_get_users_of_removed_member_types() : '',
		);

		// Back up global.
		$old_members_template = $members_template;

		?>
		<div id="boss_recently_active_widget_heartbeat" data-max="<?php echo $settings['max_members']; ?>">
			<?php if ( bp_has_members( $members_args ) ) : ?>

				<div class="avatar-block">

					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>

						<div class="item-avatar">
							<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>"><?php bp_member_avatar(); ?></a>
						</div>

					<?php endwhile; ?>

				</div>
				<div class="more-block <?php echo ( $members_template->total_member_count > $settings['max_members'] ) ? '' : esc_attr( 'bp-hide' ); ?>">
					<a href="<?php esc_url( bp_members_directory_permalink() ); ?>" class="count-more">
						<?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i>
					</a>
				</div>

			<?php else : ?>

				<div class="widget-error">
					<?php esc_html_e( 'There are no recently active members', 'buddyboss' ); ?>
				</div>

			<?php endif; ?>
		</div>
		<?php
		echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Recently Active widget options.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Recently Active widget options form.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings    = $this->parse_settings( $instance );
		$title       = strip_tags( $settings['title'] );
		$max_members = strip_tags( $settings['max_members'] );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_members' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_members' ) ); ?>" type="number" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args(
			$instance,
			array(
				'title'       => __( 'Recently Active Members', 'buddyboss' ),
				'max_members' => 15,
			),
			'recently_active_members_widget_settings'
		);
	}
}

/**
 * Periodically update members list for recently active members widget.
 *
 * @since BuddyBoss 1.0.0
 */
function buddyboss_theme_recently_active_widget_heartbeat( $response = array(), $data = array() ) {
	global $members_template;

	if ( empty( $data['boss_recently_active_widget'] ) ) {
		return $response;
	}

	$number = (int) $data['boss_recently_active_widget'];

	ob_start();

	// Setup args for querying members.
	$members_args = array(
		'user_id'         => 0,
		'type'            => 'active',
		'per_page'        => $number,
		'max'             => false,
		'populate_extras' => true,
		'search_terms'    => false,
		'exclude'         => ( function_exists( 'bp_get_users_of_removed_member_types' ) && ! empty( bp_get_users_of_removed_member_types() ) ) ? bp_get_users_of_removed_member_types() : '',
	);

	// Back up global.
	$old_members_template = $members_template;

	?>

	<?php if ( bp_has_members( $members_args ) ) : ?>

		<div class="avatar-block">

			<?php
			while ( bp_members() ) :
				bp_the_member();
				?>

				<div class="item-avatar">
					<a href="<?php bp_member_permalink(); ?>" title="<?php echo esc_attr( bp_get_member_name() ); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>"><?php bp_member_avatar(); ?></a>
				</div>

			<?php endwhile; ?>

		</div>
		<div class="more-block <?php echo ( $members_template->total_member_count > $number ) ? '' : esc_attr( 'bp-hide' ); ?>"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>

	<?php else : ?>

		<div class="widget-error">
			<?php esc_html_e( 'There are no recently active members', 'buddyboss' ); ?>
		</div>

	<?php endif; ?>

	<?php

	// Restore the global.
	$members_template = $old_members_template;

	$response['boss_recently_active_widget'] = ob_get_clean();
	return $response;
}

add_filter( 'heartbeat_received', 'buddyboss_theme_recently_active_widget_heartbeat', 10, 2 );
add_filter( 'heartbeat_nopriv_received', 'buddyboss_theme_recently_active_widget_heartbeat', 10, 2 );
