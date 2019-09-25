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
class BP_Core_Follow_Follower_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	function __construct() {
		// Set up optional widget args
		$widget_ops = array(
			'classname'   => 'widget_bp_follow_follower_widget widget buddypress',
			'description' => __( "Show a list of member avatars that are following the logged-in user.", 'buddyboss' )
		);

		// Set up the widget
		parent::__construct(
			false,
			__( "(BB) - Users Following Me", 'buddyboss' ),
			$widget_ops
		);
	}

	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {
		// do not do anything if user isn't logged in
		if ( ! is_user_logged_in() )
			return;

		if ( empty( $instance['max_users'] ) ) {
			$instance['max_users'] = 15;
		}

		// logged-in user isn't follower anyone, so stop!
		if ( ! $follower = bp_get_follower_ids( array( 'user_id' => bp_loggedin_user_id() ) ) ) {
			return false;
		}
        
        $follower_ids = bp_get_follower_ids( array( 'user_id' => bp_loggedin_user_id() ) );
        $follower_array = explode(",", $follower_ids);
        $follower_count = '<span class="widget-num-count">' . count($follower_array) . '</span>';
        $follower_count_number = count($follower_array);

		// show the users the logged-in user is follower
		if ( bp_has_members( array(
			'include'         => $follower,
			'max'             => $instance['max_users'],
			'populate_extras' => false,
		) ) ) {
			do_action( 'bp_before_follower_widget' ); 

			echo $args['before_widget'];
			echo $args['before_title']
			   . $instance['title']
               . $follower_count
			   . $args['after_title'];
	?>

			<div class="avatar-block">
				<?php while ( bp_members() ) : bp_the_member(); ?>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_avatar() ?></a>
					</div>
				<?php endwhile; ?>
			</div>
            <?php if ( $follower_count_number> $instance['max_users'] ) { ?>
                <div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php _e( 'More', 'buddyboss' ); ?><i class="bb-icon-angle-right"></i></a></div>
            <?php } ?>

			<?php echo $args['after_widget']; ?>

			<?php do_action( 'bp_after_follower_widget' ); ?>

	<?php
		}
	}

	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_users'] = (int) $new_instance['max_users'];

		return $instance;
	}

	/**
	 * Widget settings form.
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			'title'     => __( "Users Following Me", 'buddyboss' ),
			'max_users' => 16
		) );
	?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'buddyboss' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></p>

		<p><label for="bp-follow-widget-users-max"><?php _e('Max members to show:', 'buddyboss'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_users' ); ?>" name="<?php echo $this->get_field_name( 'max_users' ); ?>" type="text" value="<?php echo esc_attr( (int) $instance['max_users'] ); ?>" style="width: 30%" /></label></p>
		<p><small><?php _e( 'Note: This widget is only displayed if a member is logged in and if the logged-in user is followed by other users.', 'buddyboss' ); ?></small></p>

	<?php
	}
}