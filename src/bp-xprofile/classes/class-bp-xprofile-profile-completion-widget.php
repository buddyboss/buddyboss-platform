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
class BP_Xprofile_Profile_Completion_Widget extends WP_Widget {
	
	/**
	 * Constructor.
	 */
	function __construct() {
		// Set up optional widget args
		$widget_ops = array(
			'classname'   => 'widget_bp_profile_completion_widget widget buddypress',
			'description' => __( 'Show Profile Completion Progress.', 'buddyboss' ),
		);

		// Set up the widget
		parent::__construct(
			false,
			__( "(BB) Profile Completion", 'buddyboss' ),
			$widget_ops
		);
	}

	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {
		
		// do not do anything if user isn't logged in
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		/* Widget VARS */
		
		$profile_groups_selected = $instance['profile_groups_enabled'];
		
		// do not do anything if NO Group selected.
		if( empty( $profile_groups_selected ) ){
			return;
		}
		
		$profile_phototype_selected = !empty( $instance['profile_photos_enabled'] ) ? $instance['profile_photos_enabled'] : array();
		
		//$user_prgress = $this->get_user_progress($profile_groups_selected, $profile_phototype_selected);
		
		
		echo $args['before_widget'];
			
		/* Widget Title */
		echo $args['before_title'];
		echo $instance['title'];
		echo $args['after_title'];

		/* Widget Content */
		
		
		
			
		echo $args['after_widget'];
		
	}

	
	function get_user_progress( $groups_ids, $photo_types ){
		
		$user_id = get_current_user_id();
		
		$progress_details = array();
		
		if( in_array('profile_photo', $photo_types) ){
			
			$is_profile_photo_uploaded = ( bp_get_user_has_avatar($user_id) ) ? 1 : 0;
			
			$progress_details['photo_type']['profile_photo'] = array(
				'is_uploaded' => $is_profile_photo_uploaded,
				'name' => __('Profile Photo', 'buddyboss' )
			);
			
		}
		
		if( in_array('cover_photo', $photo_types) ){
			
			$is_cover_photo_uploaded = ( bp_get_user_has_avatar($user_id) ) ? 1 : 0;
			
			$progress_details['photo_type']['cover_photo'] = array(
				'is_uploaded' => $is_cover_photo_uploaded,
				'name' => __('Cover Photo', 'buddyboss' )
			);
			
		}
		
		echo "<pre>";
		var_dump( $progress_details );
		echo "</pre>";
		
		
		$progress_details_backup = array(
			
			'total_fields' => 10,
			'completed_fields' => 6,
			
			'groups' => array(
				1 => array(
					'group_name' => 'Group 1',
					'group_total_fields' => 5,
					'group_completed_fields' => 3,
				),
				2 => array(
					'group_name' => 'Group 2',
					'group_total_fields' => 3,
					'group_completed_fields' => 2,
				)
			),
			
			'photo_type' => array(
				
				'profile_photo' => array(
					'is_uploaded' => 1,
					'name' => __('Profile Photo', 'buddyboss' )
				),
				'cover_photo' => array(
					'is_uploaded' => 0,
					'name' => __('Cover Photo', 'buddyboss' )
				)
				
			)
		);
		
		
		
		return $progress_details;
	}
	
	
	
	
	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['profile_groups_enabled']     = $new_instance['profile_groups_enabled'];
		$instance['profile_photos_enabled']     = $new_instance['profile_photos_enabled'];

		return $instance;
	}

	
	/**
	 * Widget settings form.
	 */
	function form( $instance ) {
		
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => __( "Complete Your Profile", 'buddyboss' )
			)
		);
		
		
		/* Profile Groups and Profile Cover Photo VARS. */
		$profile_groups =  bp_xprofile_get_groups();

		$photos_enabled_arr = array();
		$is_profile_photo_disabled = bp_disable_avatar_uploads();
		$is_cover_photo_disabled = bp_disable_cover_image_uploads();
		
		// Show Options only when Profile Photo and Cover option enabled in the Profile Settings.
		if( !$is_profile_photo_disabled ){
			$photos_enabled_arr['profile_photo'] = __('Profile Photo', 'buddyboss' );
		}
		if( !$is_cover_photo_disabled ){
			$photos_enabled_arr['cover_photo'] = __('Cover Photo', 'buddyboss' );
		}
		
		
		
		/* Form HTML */
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label><?php _e( 'Profile field groups:', 'buddyboss' ); ?></label>
			
			<ul>
			<?php foreach( $profile_groups as $single_group_details ): ?>
				<?php 
				$is_checked = (!empty( $instance['profile_groups_enabled'] ) && in_array( $single_group_details->id, $instance['profile_groups_enabled'] ) );
				?>
				<li>
					<label>
						<input  class="widefat" type="checkbox" 
							    name="<?php echo $this->get_field_name( 'profile_groups_enabled' ); ?>[]" 
							    value="<?php echo $single_group_details->id; ?>" 
								<?php checked( $is_checked ); ?> 
						/>
						<?php echo $single_group_details->name; ?>
					</label>
				</li>
			<?php endforeach; ?>
			</ul>
		
		</p>
		
		<?php if( !empty( $photos_enabled_arr ) ): ?>
		<p>
			<label><?php _e( 'Profile photos:', 'buddyboss' ); ?></label>
			
			<ul>
				<?php foreach( $photos_enabled_arr as $photos_value => $photos_label ): ?>
				
				<li>
					<label>
						<input  class="widefat" type="checkbox" 
							    name="<?php echo $this->get_field_name( 'profile_photos_enabled' ); ?>[]" 
							    value="<?php echo $photos_value; ?>" 
								<?php checked( (!empty( $instance['profile_groups_enabled'] ) && in_array( $photos_value, $instance['profile_photos_enabled'] ) ) ); ?>  
						/>
						<?php echo $photos_label; ?>
					</label>
				</li>
				
				<?php endforeach; ?>
			</ul>
			
		</p>
		<?php endif; ?>

		<p><small><?php _e( 'Note: This widget is only displayed if a member is logged in.', 'buddyboss' ); ?></small></p>

		<?php
	}
}
