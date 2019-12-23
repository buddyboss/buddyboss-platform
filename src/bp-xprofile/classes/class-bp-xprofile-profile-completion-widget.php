<?php
/**
 * BuddyBoss Profile Completion Widget.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Profile Completion widget for the logged-in user
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
			'description' => __( 'Show Logged in user Profile Completion Progress.', 'buddyboss' ),
		);

		// Set up the widget
		parent::__construct(
			false,
			__( "(BB) Profile Completion", 'buddyboss' ),
			$widget_ops
		);
		
		// Delete Profile Completion Transient.
		add_action('xprofile_updated_profile', array( $this, 'delete_pc_transient' ) );
		add_action('xprofile_fields_saved_field', array( $this, 'delete_pc_transient' ) );
		add_action('xprofile_fields_deleted_field', array( $this, 'delete_pc_transient' ) );
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
		
		$user_progress = $this->get_progress_data( $profile_groups_selected, $profile_phototype_selected );
		
		
		
		/* Widget Template */
		
		echo $args['before_widget'];
			
			// Widget Title 
			echo $args['before_title'];
			echo $instance['title'];
			echo $args['after_title'];


			// Widget Content

			// Globalize the Profile Completion widget arguments. Used in the template called below.
			$bp_nouveau = bp_nouveau();
			$bp_nouveau->xprofile->profile_completion_widget_para = $user_progress;
			bp_get_template_part( 'members/single/profile/widget' );
			$bp_nouveau->xprofile->profile_completion_widget_para = array();

			
		echo $args['after_widget'];
		
	}

	/**
	 * Function returns user progress data by checking if data already exists in transient first. IF NO then follow checking the progress logic.
	 * 
	 * Clear transient when 1) Widget form settings update. 2) When Logged user profile updated. 3) When new profile fields added/updated/deleted.
	 * 
	 * @param type $profile_groups
	 * @param type $profile_phototype
	 * @return type
	 */
	function get_progress_data( $profile_groups, $profile_phototype ){
		
		$user_progress_formmatted =  array();
		
		// Check if data avail in transient
		$pc_transient_name = $this->get_pc_transient_name();
		$pc_transient_data = get_transient( $this->get_pc_transient_name() );
		
		if( !empty( $pc_transient_data ) ){
			
			$user_progress_formmatted = $pc_transient_data;
			
		}else{
		
			// Get logged in user Progress.
			$user_progress_arr = $this->get_user_progress($profile_groups, $profile_phototype);

			// Format User Progress array to pass on to the template.
			$user_progress_formmatted = $this->get_user_progress_formatted( $user_progress_arr );
			
			// set Transient here
			set_transient($pc_transient_name, $user_progress_formmatted);
		}
		
		return $user_progress_formmatted;
	}
	
	/**
	 * Function trigger when profile updated. Profile field added/updated/deleted.
	 * Deletes Profile Completion Transient here.
	 */
	function delete_pc_transient(){
		
		$pc_transient_name = $this->get_pc_transient_name();
		delete_transient( $pc_transient_name );
		
	}
	
	/**
	 * Return Transient name using logged in User ID.
	 * 
	 * @return string
	 */
	function get_pc_transient_name(){
		
		$user_id = get_current_user_id();
		$pc_transient_name = 'bbprofilecompletion'.$user_id;
		return $pc_transient_name;
		
	}
	
		/**
		 * Function returns logged in user progress based on options selected in the widget form.
		 * 
		 * @param type $group_ids
		 * @param type $photo_types
		 * @return int
		 */
		function get_user_progress( $group_ids, $photo_types ){

			/* User Progress specific VARS. */
			$user_id = get_current_user_id();
			$progress_details = array();
			$grand_total_fields = 0;
			$grand_completed_fields = 0;



			/* Profile Photo */
			if( in_array('profile_photo', $photo_types) ){

				++$grand_total_fields;

				$is_profile_photo_uploaded = ( bp_get_user_has_avatar($user_id) ) ? 1 : 0;

				if( $is_profile_photo_uploaded ){
					++$grand_completed_fields;
				}

				$progress_details['photo_type']['profile_photo'] = array(
					'is_uploaded' => $is_profile_photo_uploaded,
					'name' => __('Profile Photo', 'buddyboss' )
				);

			}



			/* Cover Photo */
			if( in_array('cover_photo', $photo_types) ){

				++$grand_total_fields;

				$is_cover_photo_uploaded = ( bp_attachments_get_user_has_cover_image($user_id) ) ? 1 : 0;

				if( $is_profile_photo_uploaded ){
					++$grand_completed_fields;
				}

				$progress_details['photo_type']['cover_photo'] = array(
					'is_uploaded' => $is_cover_photo_uploaded,
					'name' => __('Cover Photo', 'buddyboss' )
				);

			}



			/* Groups Fields */

			// Get Groups and Group fields with Loggedin user data.
			$profile_groups =  bp_xprofile_get_groups(
				array(
					'fetch_fields'                   => true,
					'fetch_field_data'               => true,
					'user_id'						 => $user_id
				)
			);

			foreach( $profile_groups as $single_group_details ){

				/* Single Group Specific VARS */

				$group_id = $single_group_details->id;
				$single_group_progress = array();

				// Consider only selected Groups ids from the widget form settings, skip all others.
				if( !in_array( $group_id, $group_ids) ){
					continue;
				}

				// Check if Current Group is repeater if YES then get number of fields inside current group.
				$is_group_repeater_str = bp_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
				$is_group_repeater = ( $is_group_repeater_str == 'on' ) ? true : false;
				$repeater_field_count = 0;
				if( $is_group_repeater ){
					$repeater_fields = bp_get_repeater_template_field_ids( $group_id );
					$repeater_field_count = count( $repeater_fields );
				}


				/* Loop through all the fields and check if fields completed or not. */
				$group_total_fields = 0;
				$group_completed_fields = 0;
				foreach( $single_group_details->fields as $array_index => $group_single_field ){

					// If current group is repeater then only check first set of fields.
					if( $is_group_repeater && ($array_index > $repeater_field_count) ){
						continue;
					}

					$field_data_value = maybe_unserialize( $group_single_field->data->value );

					if( !empty( $field_data_value ) ){
						++$group_completed_fields;
					}

					++$group_total_fields;
				}


				/* Prepare array to return group specific progress details */
				$single_group_progress['group_name'] = $single_group_details->name;
				$single_group_progress['group_total_fields'] = $group_total_fields;
				$single_group_progress['group_completed_fields'] = $group_completed_fields;

				$grand_total_fields += $group_total_fields;
				$grand_completed_fields += $group_completed_fields;

				$progress_details['groups'][ $group_id ] = $single_group_progress;

			}



			/* Total Fields vs completed fields to calculate progress percentage. */
			$progress_details['total_fields'] = $grand_total_fields;
			$progress_details['completed_fields'] = $grand_completed_fields;


			return $progress_details;
		}


		/**
		 * Function formats user progress to pass on to templates.
		 * 
		 * @param type $user_progress_arr
		 * @return int
		 */
		function get_user_progress_formatted( $user_progress_arr ){

			/* Groups */

			// Calculate Total Progress percentage.
			$profile_completion_percentage = round( ( $user_progress_arr['completed_fields']*100 ) / $user_progress_arr['total_fields'] );
			$user_prgress_formmatted = array(
				'completion_percentage' => $profile_completion_percentage
			);

			// Group specific details
			$listing_number = 1;
			foreach ( $user_progress_arr['groups'] as $group_id => $group_details ){

				$group_link = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/'.$group_id );

				$user_prgress_formmatted['groups'][] = array(
					'number'	=> $listing_number,
					'label'		=> $group_details['group_name'],
					'link'		=> $group_link,
					'is_group_completed' => ( $group_details['group_total_fields'] == $group_details['group_completed_fields'] ) ? true : false,
					'total'		=> $group_details['group_total_fields'],
					'completed' => $group_details['group_completed_fields'],
				);

				$listing_number++;	
			}


			/* Profile Photo */

			if( isset( $user_progress_arr['photo_type']['profile_photo'] ) ){

				$change_avatar_link = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/change-avatar' );
				$is_profile_uploaded = ($user_progress_arr['photo_type']['profile_photo']['is_uploaded'] == 1 );

				$user_prgress_formmatted['groups'][] = array(
					'number'	=> $listing_number,
					'label'		=> $user_progress_arr['photo_type']['profile_photo']['name'],
					'link'		=> $change_avatar_link,
					'is_group_completed' => ( $is_profile_uploaded ) ? true : false,
					'total'		=> 1,
					'completed' => ( $is_profile_uploaded ) ? 1 : 0,
				);

				$listing_number++;	
			}


			/* Cover Photo */

			if( isset( $user_progress_arr['photo_type']['cover_photo'] ) ){

				$change_cover_link = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/change-cover-image' );
				$is_cover_uploaded = ($user_progress_arr['photo_type']['cover_photo']['is_uploaded'] == 1 );

				$user_prgress_formmatted['groups'][] = array(
					'number'	=> $listing_number,
					'label'		=> $user_progress_arr['photo_type']['cover_photo']['name'],
					'link'		=> $change_cover_link,
					'is_group_completed' => ( $is_cover_uploaded ) ? true : false,
					'total'		=> 1,
					'completed' => ( $is_cover_uploaded ) ? 1 : 0,
				);

				$listing_number++;	
			}

			return $user_prgress_formmatted;
		}



	
	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance							= $old_instance;
		$instance['title']					= strip_tags( $new_instance['title'] );
		$instance['profile_groups_enabled'] = $new_instance['profile_groups_enabled'];
		$instance['profile_photos_enabled'] = $new_instance['profile_photos_enabled'];

		$pc_transient_name = $this->get_pc_transient_name();
		delete_transient( $pc_transient_name );
		
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
		
		
		
		/* Widget Form HTML */
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