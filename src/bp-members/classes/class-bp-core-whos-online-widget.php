<?php
/**
 * BuddyPress Members Who's Online Widget.
 *
 * @package BuddyBoss
 * @subpackage MembersWidgets
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
		$name        = _x( "(BuddyBoss) Who's Online", 'widget name', 'buddyboss' );
		$description = __( 'Profile photos of online users', 'buddyboss' );
		parent::__construct( false, $name, array(
			'description'                 => $description,
			'classname'                   => 'widget_bp_core_whos_online_widget buddypress widget',
			'customize_selective_refresh' => true,
		) );
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

		//echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => 'online',
			'per_page'        => $settings['max_members'],
			'max'             => $settings['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);
        
        $total_online = 0;
		if ( function_exists( 'bp_get_total_online_member_count' ) ){
			$total_online  = bp_get_total_online_member_count();
		}
        $count_online_users =  '<span class="widget-num-count" id="boss_whos_online_widget_total_heartbeat">' . $total_online . '</span>';

        $refresh_online_users =  '<a href="" class="bs-widget-reload bs-heartbeat-reload hide" title="reload"><i class="bb-icon-spin6"></i></a>';

        echo $args['before_widget'] . $args['before_title'] . $title . $count_online_users . $refresh_online_users . $args['after_title'];

		// Back up global.
		$old_members_template = $members_template;

		?>
        <div id="boss_whos_online_widget_heartbeat" data-max="<?php echo $settings['max_members']; ?>">
    		<?php if ( bp_has_members( $members_args ) ) : ?>
    
    			<div class="avatar-block">
    
    				<?php while ( bp_members() ) : bp_the_member(); ?>
    
    					<div class="item-avatar">
    						<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip="<?php bp_member_name(); ?>"><?php bp_member_avatar(); ?></a>
    					</div>
    
    				<?php endwhile; ?>
    
    			</div>
                
                <?php if (  $members_template->total_member_count < $total_online ){ ?>
                    <div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more">More<i class="bb-icon-angle-right"></i></a></div>
                <?php } ?>
    
    		<?php else: ?>
    
    			<div class="widget-error">
    				<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
    			</div>
    
    		<?php endif; ?>
        </div>
		<?php echo $args['after_widget'];

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
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

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
		$title       = strip_tags( $settings['title'] );
		$max_members = strip_tags( $settings['max_members'] ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

	<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args( $instance, array(
			'title' 	     => __( "Who's Online", 'buddyboss' ),
			'max_members' 	 => 15,
		), 'members_widget_settings' );
	}
}

if ( ! function_exists( 'bp_get_total_online_member_count' ) ) {
	function bp_get_total_online_member_count() {

		global $members_template;

		$total = 0;

		$members_args = array(
			'user_id'         => 0,
			'type'            => 'online',
			'max'             => 999,
			'populate_extras' => false,
			'search_terms'    => false,
		);

		$old_members_template = $members_template;
		if ( bp_has_members( $members_args ) ){
			$total = $members_template->total_member_count;
		}
		$members_template = $old_members_template;
		return $total;
	}
}

function buddyboss_theme_whos_online_widget_heartbeat( $response = array(), $data = array()  ){
	global $members_template;

	if ( empty( $data['boss_whos_online_widget'] ) ) {
		return $response;
	}

	$number = (int) $data['boss_whos_online_widget'];

	ob_start();

	// Setup args for querying members.
	$members_args = array(
		'user_id'         => 0,
		'type'            => 'online',
		'per_page'        => $number,
		'max'             => $number,
		'populate_extras' => true,
		'search_terms'    => false,
	);

	$total_online = 0;
	if ( function_exists( 'bp_get_total_online_member_count' ) ){
		$total_online  = bp_get_total_online_member_count();
	}

	// Back up global.
	$old_members_template = $members_template;

	?>

	<?php if ( bp_has_members( $members_args ) ) : ?>

        <div class="avatar-block">

			<?php while ( bp_members() ) : bp_the_member(); ?>

                <div class="item-avatar">
                    <a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" title="<?php bp_member_name(); ?>" data-bp-tooltip="<?php bp_member_name(); ?>"><?php bp_member_avatar(); ?></a>
                </div>

			<?php endwhile; ?>

        </div>

		<?php if (  $members_template->total_member_count < $total_online ){ ?>
            <div class="more-block"><a href="<?php bp_members_directory_permalink(); ?>" class="count-more">More<i class="bb-icon-angle-right"></i></a></div>
		<?php } ?>

	<?php else: ?>

        <div class="widget-error">
			<?php esc_html_e( 'There are no users currently online', 'buddyboss' ); ?>
        </div>

	<?php endif; ?>

	<?php

	// Restore the global.
	$members_template = $old_members_template;

	$response['boss_whos_online_widget'] = ob_get_clean();
	$response['boss_whos_online_widget_total'] = $total_online;
	return $response;
}

add_filter( 'heartbeat_received', 'buddyboss_theme_whos_online_widget_heartbeat', 10, 2 );
add_filter( 'heartbeat_nopriv_received', 'buddyboss_theme_whos_online_widget_heartbeat', 10, 2 );