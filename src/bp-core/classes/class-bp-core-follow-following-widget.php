<?php
/**
 * BuddyBoss Follow Following Widget.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Follow_Following widget for the logged-in user
 *
 * @subpackage Widgets
 */
class BP_Core_Follow_Following_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	function __construct() {
		// Set up optional widget args
		$widget_ops = array(
			'classname'   => 'widget_bp_follow_following_widget widget buddypress',
			'description' => __( 'A list of member avatars that the logged-in user is following.', 'buddyboss' ),
		);

		// Set up the widget
		parent::__construct(
			false,
			__( "(BB) Members I'm Following", 'buddyboss' ),
			$widget_ops
		);
	}

	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {

		$id = bp_displayed_user_id();
		$filter = false;

		if ( ! $id ) {
			// If member widget is putted on other pages then will not get the bp_displayed_user_id so set the bp_loggedin_user_id to bp_displayed_user_id.
			add_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
			$id     = bp_displayed_user_id();
			$filter = true;

			// If $id still blank then return.
			if ( ! $id ) {
				return;
			}

			// Set the global $bp->displayed_user variables.
			$bp->displayed_user->id       = $id;
			$bp->displayed_user->userdata = bp_core_get_core_userdata( $id );
			$bp->displayed_user->domain   = bp_core_get_user_domain( $id );
		}

		// do not do anything if user isn't logged in
		if ( ! bp_displayed_user_id() ) {
			return;
		}

		if ( empty( $instance['max_users'] ) ) {
			$instance['max_users'] = 15;
		}

		// logged-in user isn't following anyone, so stop!
		if ( ! $following = bp_get_following_ids( array( 'user_id' => bp_displayed_user_id() ) ) ) {
			return false;
		}

		$following_ids          = bp_get_following_ids( array( 'user_id' => bp_displayed_user_id() ) );
		$following_array        = explode( ',', $following_ids );
		$following_count        = '<span class="widget-num-count">' . count( $following_array ) . '</span>';
		$following_count_number = count( $following_array );

		// Remove the filter.
		if ( $filter ) {
			remove_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
		}

		$instance['title'] = (
			bp_loggedin_user_id() === bp_displayed_user_id()
			? __( "I'm Following", 'buddyboss' )
			: sprintf( __( "%s Following", 'buddyboss' ), bp_core_get_user_displayname( bp_displayed_user_id() ) )
		);

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

		// show the users the logged-in user is following
		if ( bp_has_members(
			array(
				'include'         => $following,
				'max'             => $instance['max_users'],
				'populate_extras' => false,
			)
		) ) {
			do_action( 'bp_before_following_widget' );

			echo $args['before_widget'];
			echo $args['before_title']
			   . $title
			   . $following_count
			   . $args['after_title'];
			?>

			<div class="avatar-block">
				<?php
				while ( bp_members() ) :
					bp_the_member();
					?>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink() ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo bp_core_get_user_displayname( bp_get_member_user_id() ); ?>"><?php bp_member_avatar() ?></a>
					</div>
				<?php endwhile; ?>
			</div>
			<?php if ( $following_count_number > $instance['max_users'] ) { ?>
				<div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>#following" class="count-more"><?php _e( 'More', 'buddyboss' ); ?><i class="bb-icon-angle-right"></i></a></div>
			<?php } ?>

			<?php echo $args['after_widget']; ?>

			<?php do_action( 'bp_after_following_widget' ); ?>

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
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'max_users' => 16,
			)
		);
		?>

		<p><label for="bp-follow-widget-users-max"><?php _e( 'Max members to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_users' ); ?>" name="<?php echo $this->get_field_name( 'max_users' ); ?>" type="text" value="<?php echo esc_attr( (int) $instance['max_users'] ); ?>" style="width: 30%" /></label></p>
		<p><small><?php _e( 'Note: This widget is only displayed if a member is logged in and if the logged-in user is following some users.', 'buddyboss' ); ?></small></p>

		<?php
	}

	/**
	 * Set Display user_id to loggedin_user_id if someone added the widget on outside bp pages.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	public function set_display_user( $id ) {
		if ( ! $id ) {
			$id = bp_loggedin_user_id();
		}
		return $id;
	}
}
