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
		$profile_groups_selected        = $instance['profile_groups_enabled'];
		$this->widget_id                = $args['widget_id'];
		$profile_phototype_selected     = ! empty( $instance['profile_photos_enabled'] ) ? $instance['profile_photos_enabled'] : array();
		$profile_hide_widget_selected   = ! empty( $instance['profile_hide_widget'] ) ? $instance['profile_hide_widget'] : array();
		$settings                       = array();
		$settings['profile_groups']     = $profile_groups_selected;
		$settings['profile_photo_type'] = $profile_phototype_selected;
		$user_progress                  = bp_xprofile_get_user_profile_progress_data( $settings );

		// IF nothing selected then return and nothing to display.
		if ( empty( $profile_groups_selected ) && empty( $profile_phototype_selected ) ) {
			return;
		}

		// Hide the widget if "Hide widget once progress hits 100%" selected and progress is 100%
		if ( 100 === (int) $user_progress['completion_percentage'] && ! empty( $instance['profile_hide_widget'] ) ) {
			return;
		}

		/** This filter is documented in https://developer.wordpress.org/reference/hooks/widget_title/ */
		$instance['title'] = apply_filters( 'widget_title', ! empty( $instance['title'] ) ? $instance['title'] : '', $instance );

		/* Widget Template */

		echo $args['before_widget'];

		// Widget Title
		echo $args['before_title'];
		echo $instance['title'];
		echo $args['after_title'];

		// Widget Content

		// Globalize the Profile Completion widget arguments. Used in the template called below.
		$bp_nouveau                                           = bp_nouveau();
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
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance                           = $old_instance;
		$instance['title']                  = wp_strip_all_tags( $new_instance['title'] );
		$instance['profile_groups_enabled'] = ( isset( $new_instance['profile_groups_enabled'] ) ) ? $new_instance['profile_groups_enabled'] : '';
		$instance['profile_photos_enabled'] = ( isset( $new_instance['profile_photos_enabled'] ) ) ? $new_instance['profile_photos_enabled'] : '';
		$instance['profile_hide_widget']    = ( isset( $new_instance['profile_hide_widget'] ) ) ? $new_instance['profile_hide_widget'] : '';

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

		$instance = bp_parse_args(
				(array) $instance,
				array(
						'title' => __( 'Complete Your Profile', 'buddyboss' ),
				)
		);

		$steps_options            = bp_core_profile_completion_steps_options();
		$profile_groups            = $steps_options['profile_groups'];
		$photos_enabled_arr        = array();
		$widget_enabled_arr        = array();
		$is_profile_photo_disabled = $steps_options['is_profile_photo_disabled'];
		$is_cover_photo_disabled   = $steps_options['is_cover_photo_disabled'];

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

		<p>
			<small><?php esc_html_e( 'Note: This widget is only displayed if a member is logged in.', 'buddyboss' ); ?></small>
		</p>

		<?php
	}
}
