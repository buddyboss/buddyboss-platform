<?php
/**
 * BuddyBoss Profile Completion Widget.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.2.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Profile Completion widget for the logged-in user
 *
 * @subpackage Widgets
 */
class BP_Xprofile_Profile_Completion_Widget extends WP_Widget {

	public $widget_id     = false;
	public $transient_key = 'bbprofilecompletion';

	/**
	 * Constructor.
	 */
	function __construct() {

		// Set up optional widget args.
		$widget_ops = array(
			'classname'   => 'widget_bp_profile_completion_widget widget buddypress',
			'description' => __( 'Show Logged in user Profile Completion Progress.', 'buddyboss' ),
		);

		// Set up the widget.
		parent::__construct(
			false,
			__( '(BB) Profile Completion', 'buddyboss' ),
			$widget_ops
		);

		// Delete Transient hooks.
		$this->delete_transient_hooks();
	}

	/**
	 * Function add hook to delete transient on various wp-admin and profile settings change.
	 * IF transient not deleted then it will show outdated content.
	 */
	function delete_transient_hooks() {

		// Delete loggedin user transient only..
		add_action( 'xprofile_avatar_uploaded', array( $this, 'delete_pc_loggedin_transient' ) ); // When profile photo uploaded from profile in Frontend.
		add_action( 'xprofile_cover_image_uploaded', array( $this, 'delete_pc_loggedin_transient' ) ); // When cover photo uploaded from profile in Frontend.
		add_action( 'bp_core_delete_existing_avatar', array( $this, 'delete_pc_loggedin_transient' ) ); // When profile photo deleted from profile in Frontend.
		add_action( 'xprofile_cover_image_deleted', array( $this, 'delete_pc_loggedin_transient' ) ); // When cover photo deleted from profile in Frontend.

		// Delete Profile Completion Transient when Profile updated, New Field added/update, field deleted etc..
		add_action( 'xprofile_updated_profile', array( $this, 'delete_pc_transient' ) ); // On Profile updated from frontend.
		add_action( 'xprofile_fields_saved_field', array( $this, 'delete_pc_transient' ) ); // On field added/updated in wp-admin > Profile
		add_action( 'xprofile_fields_deleted_field', array( $this, 'delete_pc_transient' ) ); // On field deleted in wp-admin > profile.
		add_action( 'xprofile_groups_deleted_group', array( $this, 'delete_pc_transient' ) ); // On profile group deleted in wp-admin.
		add_action( 'update_option_bp-disable-avatar-uploads', array( $this, 'delete_pc_transient' ) ); // When avatar photo setting updated in wp-admin > Settings > profile.
		add_action( 'update_option_bp-disable-cover-image-uploads', array( $this, 'delete_pc_transient' ) ); // When cover photo setting updated in wp-admin > Settings > profile.
		add_action( 'wp_ajax_xprofile_reorder_fields', array( $this, 'delete_pc_transient' ) ); // When fields inside fieldset are dragged and dropped in wp-admin > buddybpss > profile.
	}


	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {

		// do not do anything if user isn't logged in OR IF user is viewing other members profile.
		if ( ! is_user_logged_in() || ( bp_is_user() && ! bp_is_my_profile() ) ) {
			return;
		}

		/* Widget VARS */

		$profile_groups_selected      = $instance['profile_groups_enabled'];
		$this->widget_id              = $args['widget_id'];
		$profile_phototype_selected   = ! empty( $instance['profile_photos_enabled'] ) ? $instance['profile_photos_enabled'] : array();
		$profile_hide_widget_selected = ! empty( $instance['profile_hide_widget'] ) ? $instance['profile_hide_widget'] : array();
		$user_progress                = $this->get_progress_data( $profile_groups_selected, $profile_phototype_selected );

		// IF nothing selected then return and nothing to display.
		if ( empty( $profile_groups_selected ) && empty( $profile_phototype_selected ) ) {
		    return;
        }

		// Hide the widget if "Hide widget once progress hits 100%" selected and progress is 100%
		if ( 100 === (int) $user_progress['completion_percentage'] && ! empty( $instance['profile_hide_widget'] ) ) {
		    return;
        }

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

		/**
		 * Fires after showing widget content.
		 *
		 * @since BuddyBoss 1.2.5
		 */
		do_action( 'xprofile_profile_completion_widget' );

		echo $args['after_widget'];

	}

	/**
	 * Function returns user progress data by checking if data already exists in transient first. IF NO then follow checking the progress logic.
	 *
	 * Clear transient when 1) Widget form settings update. 2) When Logged user profile updated. 3) When new profile fields added/updated/deleted.
	 *
	 * @param type $profile_groups
	 * @param type $profile_phototype
	 *
	 * @return type
	 */
	function get_progress_data( $profile_groups, $profile_phototype ) {

		$user_progress_formmatted = array();

		// Check if data avail in transient.
		$pc_transient_name = $this->get_pc_transient_name();
		$pc_transient_data = get_transient( $pc_transient_name );

		if ( ! empty( $pc_transient_data ) ) {

			$user_progress_formmatted = $pc_transient_data;

		} else {

			// Get logged in user Progress.
			$user_progress_arr = $this->get_user_progress( $profile_groups, $profile_phototype );

			// Format User Progress array to pass on to the template.
			$user_progress_formmatted = $this->get_user_progress_formatted( $user_progress_arr );

			// set Transient here.
			set_transient( $pc_transient_name, $user_progress_formmatted );
		}

		return $user_progress_formmatted;
	}

	/**
	 * Function trigger when profile updated. Profile field added/updated/deleted.
	 * Deletes Profile Completion Transient here.
	 */
	function delete_pc_loggedin_transient() {

		// Delete logged in user all widgets transients from options table.
		$user_id               = get_current_user_id();
		$transient_name_prefix = '%_transient_' . $this->transient_key . $user_id . '%';
		$this->delete_transient_query( $transient_name_prefix );
	}

	/**
	 * Function trigger when profile updated. Profile field added/updated/deleted.
	 * Deletes Profile Completion Transient here.
	 */
	function delete_pc_transient() {

		// Delete all users all widhets transients from options table.
		$transient_name_prefix = '%_transient_' . $this->transient_key . '%';
		$this->delete_transient_query( $transient_name_prefix );
	}


	/**
	 * Function deletes Transient based on the transient name specified.
	 *
	 * @param type $transient_name_prefix
	 *
	 * @global type $wpdb
	 */
	function delete_transient_query( $transient_name_prefix ) {
		global $wpdb;
		$delete_transient_query = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%s' ",
			$transient_name_prefix
		);
		$wpdb->query( $delete_transient_query );
	}


	/**
	 * Return Transient name using logged in User ID.
	 *
	 * @return string
	 */
	function get_pc_transient_name() {

		$user_id           = get_current_user_id();
		$pc_transient_name = $this->transient_key . $user_id . $this->widget_id;

		return $pc_transient_name;

	}

	/**
	 * Function returns logged in user progress based on options selected in the widget form.
	 *
	 * @param type $group_ids
	 * @param type $photo_types
	 *
	 * @return int
	 */
	function get_user_progress( $group_ids, $photo_types ) {

		/* User Progress specific VARS. */
		$user_id                = get_current_user_id();
		$progress_details       = array();
		$grand_total_fields     = 0;
		$grand_completed_fields = 0;

		/* Profile Photo */

		// check if profile photo option still enabled.
		$is_profile_photo_disabled = bp_disable_avatar_uploads();
		if ( ! $is_profile_photo_disabled && in_array( 'profile_photo', $photo_types ) ) {

			++ $grand_total_fields;

			$is_profile_photo_uploaded = ( bp_get_user_has_avatar( $user_id ) ) ? 1 : 0;

			if ( $is_profile_photo_uploaded ) {
				++ $grand_completed_fields;
			}

			$progress_details['photo_type']['profile_photo'] = array(
				'is_uploaded' => $is_profile_photo_uploaded,
				'name'        => __( 'Profile Photo', 'buddyboss' ),
			);

		}

		/* Cover Photo */

		// check if cover photo option still enabled.
		$is_cover_photo_disabled = bp_disable_cover_image_uploads();
		if ( ! $is_cover_photo_disabled && in_array( 'cover_photo', $photo_types ) ) {

			++ $grand_total_fields;

			$is_cover_photo_uploaded = ( bp_attachments_get_user_has_cover_image( $user_id ) ) ? 1 : 0;

			if ( $is_cover_photo_uploaded ) {
				++ $grand_completed_fields;
			}

			$progress_details['photo_type']['cover_photo'] = array(
				'is_uploaded' => $is_cover_photo_uploaded,
				'name'        => __( 'Cover Photo', 'buddyboss' ),
			);

		}

		/* Groups Fields */

		// Get Groups and Group fields with Loggedin user data.
		$profile_groups = bp_xprofile_get_groups(
			array(
				'fetch_fields'     => true,
				'fetch_field_data' => true,
				'user_id'          => $user_id,
			)
		);

		foreach ( $profile_groups as $single_group_details ) {

			if ( empty( $single_group_details->fields ) ) {
				continue;
			}

			/* Single Group Specific VARS */
			$group_id              = $single_group_details->id;
			$single_group_progress = array();

			// Consider only selected Groups ids from the widget form settings, skip all others.
			if ( ! in_array( $group_id, $group_ids ) ) {
				continue;
			}

			// Check if Current Group is repeater if YES then get number of fields inside current group.
			$is_group_repeater_str = bp_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
			$is_group_repeater     = ( 'on' === $is_group_repeater_str ) ? true : false;

			/* Loop through all the fields and check if fields completed or not. */
			$group_total_fields     = 0;
			$group_completed_fields = 0;
			foreach ( $single_group_details->fields as $group_single_field ) {

				// If current group is repeater then only consider first set of fields.
				if ( $is_group_repeater ) {

					// If field not a "clone number 1" then stop. That means proceed with the first set of fields and restrict others.
					$field_id     = $group_single_field->id;
					$clone_number = bp_xprofile_get_meta( $field_id, 'field', '_clone_number', true );
					if ( $clone_number > 1 ) {
						continue;
					}
				}

				$field_data_value = maybe_unserialize( $group_single_field->data->value );

				if ( ! empty( $field_data_value ) ) {
					++ $group_completed_fields;
				}

				++ $group_total_fields;
			}

			/* Prepare array to return group specific progress details */
			$single_group_progress['group_name']             = $single_group_details->name;
			$single_group_progress['group_total_fields']     = $group_total_fields;
			$single_group_progress['group_completed_fields'] = $group_completed_fields;

			$grand_total_fields     += $group_total_fields;
			$grand_completed_fields += $group_completed_fields;

			$progress_details['groups'][ $group_id ] = $single_group_progress;

		}

		/* Total Fields vs completed fields to calculate progress percentage. */
		$progress_details['total_fields']     = $grand_total_fields;
		$progress_details['completed_fields'] = $grand_completed_fields;

		/**
		 * Filter returns User Progress array.
		 *
		 * @since BuddyBoss 1.2.5
		 */
		return apply_filters( 'xprofile_pc_user_progress', $progress_details );
	}


	/**
	 * Function formats user progress to pass on to templates.
	 *
	 * @param type $user_progress_arr
	 *
	 * @return int
	 */
	function get_user_progress_formatted( $user_progress_arr ) {

		/* Groups */

		$loggedin_user_domain = bp_loggedin_user_domain();
		$profile_slug         = bp_get_profile_slug();

		// Calculate Total Progress percentage.
		$profile_completion_percentage = round( ( $user_progress_arr['completed_fields'] * 100 ) / $user_progress_arr['total_fields'] );
		$user_prgress_formatted        = array(
			'completion_percentage' => $profile_completion_percentage,
		);

		// Group specific details
		$listing_number = 1;
		foreach ( $user_progress_arr['groups'] as $group_id => $group_details ) {

			$group_link = trailingslashit( $loggedin_user_domain . $profile_slug . '/edit/group/' . $group_id );

			$user_prgress_formatted['groups'][] = array(
				'number'             => $listing_number,
				'label'              => $group_details['group_name'],
				'link'               => $group_link,
				'is_group_completed' => ( $group_details['group_total_fields'] === $group_details['group_completed_fields'] ) ? true : false,
				'total'              => $group_details['group_total_fields'],
				'completed'          => $group_details['group_completed_fields'],
			);

			$listing_number ++;
		}

		/* Profile Photo */

		if ( isset( $user_progress_arr['photo_type']['profile_photo'] ) ) {

			$change_avatar_link  = trailingslashit( $loggedin_user_domain . $profile_slug . '/change-avatar' );
			$is_profile_uploaded = ( 1 === $user_progress_arr['photo_type']['profile_photo']['is_uploaded'] );

			$user_prgress_formatted['groups'][] = array(
				'number'             => $listing_number,
				'label'              => $user_progress_arr['photo_type']['profile_photo']['name'],
				'link'               => $change_avatar_link,
				'is_group_completed' => ( $is_profile_uploaded ) ? true : false,
				'total'              => 1,
				'completed'          => ( $is_profile_uploaded ) ? 1 : 0,
			);

			$listing_number ++;
		}

		/* Cover Photo */

		if ( isset( $user_progress_arr['photo_type']['cover_photo'] ) ) {

			$change_cover_link = trailingslashit( $loggedin_user_domain . $profile_slug . '/change-cover-image' );
			$is_cover_uploaded = ( 1 === $user_progress_arr['photo_type']['cover_photo']['is_uploaded'] );

			$user_prgress_formatted['groups'][] = array(
				'number'             => $listing_number,
				'label'              => $user_progress_arr['photo_type']['cover_photo']['name'],
				'link'               => $change_cover_link,
				'is_group_completed' => ( $is_cover_uploaded ) ? true : false,
				'total'              => 1,
				'completed'          => ( $is_cover_uploaded ) ? 1 : 0,
			);

			$listing_number ++;
		}

		/**
		 * Filter returns User Progress array in the template friendly format.
		 *
		 * @since BuddyBoss 1.2.5
		 */
		return apply_filters( 'xprofile_pc_user_progress_formatted', $user_prgress_formatted );
	}


	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance                           = $old_instance;
		$instance['title']                  = wp_strip_all_tags( $new_instance['title'] );
		$instance['profile_groups_enabled'] = $new_instance['profile_groups_enabled'];
		$instance['profile_photos_enabled'] = $new_instance['profile_photos_enabled'];
		$instance['profile_hide_widget']    = $new_instance['profile_hide_widget'];

		// Delete Transient.
		$this->delete_pc_transient();

		/**
		 * Fires when updating widget form settings.
		 *
		 * @since BuddyBoss 1.2.5
		 */
		return apply_filters( 'xprofile_profile_completion_form_update', $instance );
	}

	/**
	 * Widget settings form.
	 */
	function form( $instance ) {

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => __( 'Complete Your Profile', 'buddyboss' ),
			)
		);

		/* Profile Groups and Profile Cover Photo VARS. */
		$profile_groups = bp_xprofile_get_groups();

		$photos_enabled_arr        = array();
		$widget_enabled_arr        = array();
		$is_profile_photo_disabled = bp_disable_avatar_uploads();
		$is_cover_photo_disabled   = bp_disable_cover_image_uploads();

		// Show Options only when Profile Photo and Cover option enabled in the Profile Settings.
		if ( ! $is_profile_photo_disabled ) {
			$photos_enabled_arr['profile_photo'] = __( 'Profile Photo', 'buddyboss' );
		}
		if ( ! $is_cover_photo_disabled ) {
			$photos_enabled_arr['cover_photo'] = __( 'Cover Photo', 'buddyboss' );
		}

		$widget_enabled_arr['hide_widget'] = __( 'Hide widget once progress hits 100%', 'buddyboss' );

		/* Widget Form HTML */ ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'buddyboss' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>        <p>
			<label><?php esc_html_e( 'Profile field sets:', 'buddyboss' ); ?></label>

		<ul>
			<?php
			foreach ( $profile_groups as $single_group_details ) :
					$is_checked = ( ! empty( $instance['profile_groups_enabled'] ) && in_array( $single_group_details->id, $instance['profile_groups_enabled'] ) );
				?>
				<li>
					<label>
						<input class="widefat" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'profile_groups_enabled' ) ); ?>[]" value="<?php echo esc_attr( $single_group_details->id ); ?>"
							<?php checked( $is_checked ); ?>
						/>
						<?php echo esc_html( $single_group_details->name ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>

		</p>

		<?php if ( ! empty( $photos_enabled_arr ) ) : ?>
			<p>
				<label><?php esc_html_e( 'Profile photos:', 'buddyboss' ); ?></label>

			<ul>
				<?php foreach ( $photos_enabled_arr as $photos_value => $photos_label ) : ?>

					<li>
						<label>
							<input class="widefat" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'profile_photos_enabled' ) ); ?>[]" value="<?php echo esc_attr( $photos_value ); ?>" <?php checked( ( ! empty( $instance['profile_photos_enabled'] ) && in_array( $photos_value, $instance['profile_photos_enabled'] ) ) ); ?>/>
							<?php echo esc_html( $photos_label ); ?>
						</label>
					</li>

				<?php endforeach; ?>
			</ul>

			</p>
		<?php endif; ?>

		<?php if ( ! empty( $widget_enabled_arr ) ) : ?>
            <p>
                <label><?php esc_html_e( 'Options:', 'buddyboss' ); ?></label>

            <ul>
				<?php foreach ( $widget_enabled_arr as $option_value => $option_label ) : ?>

                    <li>
                        <label>
                            <input class="widefat" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'profile_hide_widget' ) ); ?>[]" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( ( ! empty( $instance['profile_hide_widget'] ) && in_array( $option_value, $instance['profile_hide_widget'] ) ) ); ?>/>
							<?php echo esc_html( $option_label ); ?>
                        </label>
                    </li>

				<?php endforeach; ?>
            </ul>

            </p>
		<?php endif; ?>

		<?php
		/**
		 * Fires after showing last field in the Widget form.
		 *
		 * @since BuddyBoss 1.2.5
		 */
		do_action( 'xprofile_profile_completion_form' );
		?>

		<p><small>
		<?php
		esc_html_e(
			'Note: This widget is only displayed if a member is logged in.',
			'buddyboss'
		);
		?>
					</small>
		</p>

		<?php
	}
}
