<?php
/**
 * BuddyPress Members Who's Online Widget.
 *
 * @package BuddyBoss\Members\Widgets
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Who's Online Widget.
 *
 * @since BuddyPress 1.0.3
 */
class BP_Core_Whos_Online_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		$name        = __( '(BB) Who\'s Online', 'buddyboss' );
		$description = __( 'Profile photos of online users', 'buddyboss' );
		parent::__construct(
			false,
			$name,
			array(
				'description'                 => $description,
				'classname'                   => 'widget_bp_core_whos_online_widget buddypress widget',
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Display the Who's Online widget.
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
		 * Filters the title of the Who's Online widget.
		 *
		 * @since BuddyPress 1.8.0
		 * @since BuddyPress 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );

		// Back up global.
		$old_members_template = $members_template;
		$online_count         = 0;
		$connection_count     = 0;
		$online_html          = '';
		$connection_html      = '';

		/**
		 * Filters the check if fetch widget data.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param bool $value Fetch the data if it's true.
		 */
		$is_fetch_data = apply_filters( 'bb_is_fetch_widget_data', true );

		if ( $is_fetch_data ) {
			// Setup args for querying members.
			$online_args = array(
				'user_id'         => 0,
				'type'            => 'online',
				'per_page'        => $settings['max_members'],
				'max'             => $settings['max_members'],
				'populate_extras' => true,
				'search_terms'    => false,
				'exclude'         => bp_loggedin_user_id(),
			);

			ob_start();
			if ( bp_has_members( $online_args ) && apply_filters( 'bb_show_online_users', true ) ) {
				?>

				<div class="avatar-block who-is-online-widget-parent-users">

					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>

						<div class="item-avatar item-avatar-<?php echo esc_attr( bp_get_member_user_id() ); ?>">
							<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>">
								<?php bp_member_avatar(); ?>
								<?php bb_user_presence_html( bp_get_member_user_id() ); ?>
							</a>
						</div>

					<?php endwhile; ?>

				</div>

				<?php
				$online_count = $members_template->total_member_count;
				if ( $online_count > (int) $settings['max_members'] ) {
					?>
					<div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>
					<?php
				}
			} else {
				?>

				<div class="widget-error widget-error-users">
					<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
				</div>

				<?php
			}
			$online_html = ob_get_clean();
		}

		if ( $is_fetch_data ) {
			$connection_args = array(
				'user_id'         => bp_loggedin_user_id(),
				'scope'           => 'personal',
				'type'            => 'online',
				'per_page'        => $settings['max_members'],
				'max'             => $settings['max_members'],
				'populate_extras' => true,
				'search_terms'    => false,
				'exclude'         => bp_loggedin_user_id(),
			);

			ob_start();
			if ( bp_has_members( $connection_args ) && apply_filters( 'bb_show_online_users', true ) ) {

				?>

				<div class="avatar-block who-is-online-widget-parent-connection">

					<?php
					while ( bp_members() ) :
						bp_the_member();

						$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( bp_get_member_user_id() ) ? 'bp-user-suspended' : '';
						$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( bp_get_member_user_id() ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
						?>

						<div class="item-avatar item-avatar-<?php echo esc_attr( bp_get_member_user_id() ); ?>">
							<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip <?php echo esc_attr( $moderation_class ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>">
								<?php bp_member_avatar(); ?>
								<?php bb_user_presence_html( bp_get_member_user_id() ); ?>
							</a>
						</div>

					<?php endwhile; ?>

				</div>

				<?php
				$connection_count = $members_template->total_member_count;
				if ( $connection_count > (int) $settings['max_members'] ) {
					?>
					<div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>
				<?php } ?>

			<?php } else { ?>

				<div class="widget-error widget-error-connections">
					<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
				</div>

				<?php
			}

			$connection_html = ob_get_clean();
		}

		$refresh_online_users = '<a href="" class="bs-widget-reload bs-heartbeat-reload hide" title="reload"><i class="bb-icon-spin6"></i></a>';

		echo $args['before_widget'] . $args['before_title'] . $title . $refresh_online_users . $args['after_title'];

		$separator = apply_filters( 'bp_members_online_widget_separator', '|' );

		?>
		<div class="item-options bb-online-members-tabs" id="who-online-members-list-options">
			<a href="javascript:void(0);" id="online-members" data-content="boss_whos_online_widget_heartbeat" class="online-members-count">
				<?php esc_html_e( 'Online', 'buddyboss' ); ?>
				<span class="widget-num-count"><?php echo esc_html( $online_count ); ?></span>
			</a>
			<?php
			if ( is_user_logged_in() && bp_is_active( 'friends' ) ) :
				?>
				<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
				<a href="javascript:void(0);" id="connection-members" data-content="boss_whos_online_widget_connections" class="online-friends-count">
					<?php esc_html_e( 'Connections', 'buddyboss' ); ?>
					<span class="widget-num-count"><?php echo esc_html( $connection_count ); ?></span>
				</a>
				<?php
			endif;
			?>
		</div>
		<div class="widget-content bb-online-status-who-is-online-members-tab" id="boss_whos_online_widget_heartbeat" data-max="<?php echo esc_attr( $settings['max_members'] ); ?>">
			<?php echo wp_kses_post( $online_html ); ?>
		</div>

		<?php
		if ( is_user_logged_in() && bp_is_active( 'friends' ) ) {
			?>
			<div class="widget-content bb-online-status-who-is-online-connection-tab" id="boss_whos_online_widget_connections" data-max="<?php echo esc_attr( $settings['max_members'] ); ?>">
				<?php echo wp_kses_post( $connection_html ); ?>
			</div>
			<?php
		}

		echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Who's Online widget options.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = wp_strip_all_tags( $new_instance['title'] );
		$instance['max_members'] = wp_strip_all_tags( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Who's Online widget options form.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings    = $this->parse_settings( $instance );
		$title       = wp_strip_all_tags( $settings['title'] );
		$max_members = wp_strip_all_tags( $settings['max_members'] );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'max_members' ) ); ?>">
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
				'title'       => __( "Who's Online", 'buddyboss' ),
				'max_members' => 15,
			),
			'members_widget_settings'
		);
	}
}

if ( ! function_exists( 'bp_get_total_online_member_count' ) ) {
	/**
	 * Get total number of members currently online.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_get_total_online_member_count() {

		global $members_template;

		$total = 0;

		$members_args = array(
			'user_id'         => 0,
			'type'            => 'online',
			'max'             => 9999999,
			'populate_extras' => false,
			'search_terms'    => false,
			'exclude'         => bp_loggedin_user_id(),
		);

		$old_members_template = $members_template;
		if ( bp_has_members( $members_args ) ) {
			$total = $members_template->total_member_count;
		}
		$members_template = $old_members_template;
		return $total;
	}
}

/**
 * Periodically update total number of members online for who's online widget.
 *
 * @since BuddyBoss 1.0.0
 */
function buddyboss_theme_whos_online_widget_heartbeat( $response = array(), $data = array() ) {
	global $members_template;

	if ( empty( $data['boss_whos_online_widget'] ) ) {
		return $response;
	}

	/**
	 * Filters the check if fetch widget data.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param bool $value Fetch the data if it's true.
	 */
	$is_fetch_data = apply_filters( 'bb_is_fetch_widget_data', true );

	if ( ! $is_fetch_data ) {
		return $response;
	}

	$number = (int) $data['boss_whos_online_widget'];

	// Back up global.
	$old_members_template = $members_template;

	// Setup args for querying members.
	$online_args = array(
		'user_id'         => 0,
		'type'            => 'online',
		'per_page'        => $number,
		'max'             => $number,
		'populate_extras' => true,
		'search_terms'    => false,
		'exclude'         => bp_loggedin_user_id(),
	);

	$online_count     = 0;
	$connection_count = 0;

	ob_start();
	if ( bp_has_members( $online_args ) ) {
		?>
		<div class="avatar-block who-is-online-widget-parent-users">

			<?php
			while ( bp_members() ) :
				bp_the_member();

				$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( bp_get_member_user_id() ) ? 'bp-user-suspended' : '';
				$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( bp_get_member_user_id() ) ? 'bp-user-blocked' : $moderation_class;
				?>

				<div class="item-avatar item-avatar-<?php echo esc_attr( bp_get_member_user_id() ); ?>">
					<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip <?php echo esc_attr( $moderation_class ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>">
						<?php bp_member_avatar(); ?>
						<?php bb_user_presence_html( bp_get_member_user_id() ); ?>
					</a>
				</div>
			<?php endwhile; ?>

		</div>

		<?php
		$online_count = $members_template->total_member_count;
		if ( $online_count > $number ) {
			?>
			<div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>
			<?php
		}
	} else {
		?>
			<div class="widget-error widget-error-users">
				<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
			</div>
		<?php
	}
	$online_html = ob_get_clean();

	$connection_args = array(
		'user_id'         => bp_loggedin_user_id(),
		'scope'           => 'personal',
		'type'            => 'online',
		'per_page'        => $number,
		'max'             => $number,
		'populate_extras' => true,
		'search_terms'    => false,
		'exclude'         => bp_loggedin_user_id(),
	);

	ob_start();
	if ( bp_has_members( $connection_args ) && apply_filters( 'bb_show_online_users', true ) ) {
		?>

		<div class="avatar-block who-is-online-widget-parent-connection">

			<?php
			while ( bp_members() ) :
				bp_the_member();

				$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( bp_get_member_user_id() ) ? 'bp-user-suspended' : '';
				$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( bp_get_member_user_id() ) ? 'bp-user-blocked' : $moderation_class;
				?>

				<div class="item-avatar item-avatar-<?php echo esc_attr( bp_get_member_user_id() ); ?>">
					<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip <?php echo esc_attr( $moderation_class ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_member_name() ); ?>">
						<?php bp_member_avatar(); ?>
						<?php bb_user_presence_html( bp_get_member_user_id() ); ?>
					</a>
				</div>

			<?php endwhile; ?>

		</div>

		<?php
		$connection_count = $members_template->total_member_count;
		if ( $connection_count > $number ) {
			?>
			<div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>
			<?php
		}
	} else {
		?>
		<div class="widget-error widget-error-connections">
			<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
		</div>
		<?php
	}

	$connection_html = ob_get_clean();

	// Restore the global.
	$members_template = $old_members_template;

	$response['boss_whos_online_widget']                  = $online_html;
	$response['boss_whos_online_widget_total']            = $online_count;
	$response['boss_whos_online_widget_connection']       = $connection_html;
	$response['boss_whos_online_widget_total_connection'] = $connection_count;
	return $response;
}

add_filter( 'heartbeat_received', 'buddyboss_theme_whos_online_widget_heartbeat', 10, 2 );
add_filter( 'heartbeat_nopriv_received', 'buddyboss_theme_whos_online_widget_heartbeat', 10, 2 );
