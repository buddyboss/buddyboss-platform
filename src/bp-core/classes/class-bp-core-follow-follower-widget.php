<?php
/**
 * BuddyBoss Follow Follower Widget.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.1.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Follow_Follower widget for the logged-in user
 *
 * @subpackage Widgets
 */
class BBoss_Core_Follow_Follower_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	function __construct() {
		// Set up optional widget args
		$widget_ops = array(
			'classname'   => 'widget_bp_follow_follower_widget widget buddypress',
			'description' => __( 'A list of member avatars that are following the logged-in user.', 'buddyboss' ),
		);

		// Set up the widget
		parent::__construct(
			false,
			__( '(BB) Members Following Me', 'buddyboss' ),
			$widget_ops
		);
	}

	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {

		// do not do anything if user isn't logged in
		if ( ! is_user_logged_in() || ! bboss_is_activity_follow_active() ) {
			return;
		}

		$id = bboss_displayed_user_id();
		$filter = $show_more = false;

		if ( ! $id ) {
			// If member widget is putted on other pages then will not get the bp_displayed_user_id so set the bp_loggedin_user_id to bp_displayed_user_id.
			add_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
			$id     = bboss_displayed_user_id();
			$filter = true;

			// If $id still blank then return.
			if ( ! $id ) {
				return;
			}
		}

		if ( empty( $instance['max_users'] ) ) {
			$instance['max_users'] = 15;
		}

		// logged-in user isn't follower anyone, so stop!
		if ( ! $follower = bboss_get_follower_ids( array( 'user_id' => $id ) ) ) {
			return false;
		}

		$follower_ids          = bboss_get_follower_ids( array( 'user_id' => $id ) );
		$follower_array        = explode( ',', $follower_ids );
		$follower_count        = '<span class="widget-num-count">' . count( $follower_array ) . '</span>';
		$follower_count_number = count( $follower_array );

		$instance['title'] = (
			bboss_loggedin_user_id() === bboss_displayed_user_id()
			? __( "My Followers", 'buddyboss' )
			/* translators: %s: member display name. */
			: sprintf( __( "%s's Followers", 'buddyboss' ), $this->get_user_display_name( $id ) )
		);

		if ( bboss_loggedin_user_id() === bboss_displayed_user_id() ) {
			$show_more = true;
		}

		// Remove the filter.
		if ( $filter ) {
			remove_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
		}

		/**
		 * Filters the Connections widget title.
		 *
		 * @since BuddyBoss 1.2.5 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		// show the users the logged-in user is follower.
		if ( bboss_has_members(
			array(
				'include'             => $follower,
				'per_page'            => $instance['max_users'],
				'populate_extras'     => false,
				'member_type__not_in' => false
			)
		) ) {
			do_action( 'bp_before_follower_widget' );

			echo wp_kses_post( $args['before_widget'] );
			echo wp_kses_post(
				$args['before_title']
				. $title
				. $follower_count
				. $args['after_title']
			);
			?>

			<div class="avatar-block">
				<?php
				while ( bboss_members() ) :
					bboss_the_member();
					?>
					<div class="item-avatar">
						<a href="<?php bboss_member_permalink() ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bboss_core_get_user_displayname( bboss_get_member_user_id() ) ); ?>"<?php echo bboss_get_hover_card_profile_attr( bboss_get_member_user_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>><?php bboss_member_avatar() ?></a>
					</div>
				<?php endwhile; ?>
			</div>
			<?php if ( $follower_count_number > $instance['max_users'] && $show_more ) { ?>
				<div class="more-block more-followers"><a href="<?php bboss_members_directory_permalink(); ?>#followers" class="count-more"><?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a></div>
			<?php } ?>

			<?php echo wp_kses_post( $args['after_widget'] ); ?>

			<?php do_action( 'bp_after_follower_widget' ); ?>

			<?php
		}
	}

	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['max_users'] = (int) $new_instance['max_users'];

		return $instance;
	}

	/**
	 * Widget settings form.
	 */
	function form( $instance ) {
		$instance = bboss_parse_args(
			(array) $instance,
			array(
				'max_users' => 16,
			)
		);
		?>

		<p><label for="bp-follow-widget-users-max"><?php esc_html_e( 'Max members to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_users' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_users' ) ); ?>" type="number" value="<?php echo esc_attr( (int) $instance['max_users'] ); ?>" style="width: 30%" /></label></p>
		<p><small><?php esc_html_e( 'Note: This widget is only displayed if a member is followed by other members.', 'buddyboss' ); ?></small></p>

		<?php
	}

	/**
	 * Set Display user_id to loggedin_user_id if someone added the widget on outside bp pages.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	public function set_display_user( $id ) {
		if ( ! $id ) {
			$id = bboss_loggedin_user_id();
		}
		return $id;
	}

	/**
	 * Display user name to 'First Name' when they have selected 'First Name & Last Name' in display format.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	public function get_user_display_name( $user_id ) {

		if ( ! $user_id ) {
			return;
		}

		$format = bboss_core_display_name_format();

		if (
			'first_name' === $format
			|| 'first_last_name' === $format
		) {
			$first_name_id = (int) bboss_get_option( 'bp-xprofile-firstname-field-id' );
			$display_name = bboss_xprofile_get_field_data( $first_name_id, $user_id );
		} else {
			$display_name = bboss_core_get_user_displayname( $user_id );
		}

		return apply_filters( 'bp_core_widget_user_display_name', $display_name, $user_id );
	}
}
