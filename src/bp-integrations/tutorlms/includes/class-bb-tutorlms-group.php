<?php
/**
 * BuddyBoss Groups TutorLMS.
 *
 * @package BuddyBoss\Groups\TutorLMS
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_TutorLMS_Group
 */
class BB_TutorLMS_Group {
	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		if ( ! bp_is_active( 'groups' ) ) {
			return false;
		}

		$this->includes();
		$this->setup_filters();
		$this->setup_actions();
	}

	/**
	 * Includes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function includes() {

	}

	/**
	 * Setup the group TutorLMS class filters.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_filters() {

	}

	/**
	 * Setup actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setup_actions() {
		// Adds a TutorLMS metabox to the new BuddyBoss Group Admin UI.
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'group_admin_ui_edit_screen' ) );

		// Saves the TutorLMS options if they come from the BuddyBoss Group Admin UI.
		add_action( 'bp_group_admin_edit_after', array( $this, 'admin_tutorlms_settings_screen_save' ) );
	}

	/**
	 * Adds a TutorLMS metabox to BuddyBoss Group Admin UI.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @uses add_meta_box
	 */
	public function group_admin_ui_edit_screen() {
		add_meta_box(
			'bb_tutorlms_group_admin_ui_meta_box',
			__( 'TutorLMS', 'buddyboss' ),
			array( $this, 'group_admin_ui_display_metabox' ),
			get_current_screen()->id,
			'advanced',
			'low'
		);
	}

	/**
	 * Displays the TutorLMS metabox in BuddyBoss Group Admin UI.
	 *
	 * @param object $group (group object).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function group_admin_ui_display_metabox( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		$bb_tutorlms_groups = groups_get_groupmeta( $group_id, 'bb-tutorlms-group' );

		$tutorlms_global_course_activities = bb_get_enabled_tutorlms_course_activities();
		$tutorlms_course_activities        = bb_tutrolms_course_activities( $tutorlms_global_course_activities );

		$courses = \Tutor\Models\CourseModel::get_courses();
		?>

        <div class="bb-group-tutorlms-settings-container">
			<?php
			if ( ! empty( $tutorlms_course_activities ) ) {
				?>
                <fieldset>
                    <h3><?php echo __( 'Select Course Activities', 'buddyboss' ); ?></h3>
                    <p class="bb-section-info">
						<?php esc_html_e( 'Which TutorLMS activities should be displayed in this group?', 'buddyboss' ); ?>
                    </p>
					<?php
					foreach ( $tutorlms_course_activities as $key => $value ) {
						$checked = isset( $bb_tutorlms_groups['course-activity'][ $key ] ) ? $bb_tutorlms_groups['course-activity'][ $key ] : '0';
						?>
                        <div class="field-group bp-checkbox-wrap">
                            <p class="checkbox bp-checkbox-wrap bp-group-option-enable">
                                <input type="checkbox" name="bb-tutorlms-group[course-activity][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" class="bs-styled-checkbox" value="1" <?php checked( $checked, '1' ); ?>/>
                                <label for="bp-edit-group-tutorlms">
                                    <span><?php echo esc_html( $value ); ?></span>
                                </label>
                            </p>
                        </div>
						<?php
					}
					?>
                </fieldset>
				<?php
			}
			if ( ! empty( $courses ) ) {
				?>
                <fieldset>
                    <h3><?php echo __( 'Select Courses', 'buddyboss' ); ?></h3>
                    <p class="bb-section-info">
						<?php esc_html_e( 'Choose your TutorLMS courses you would like to associate with this group.', 'buddyboss' ); ?>
                    </p>
					<?php
					foreach ( $courses as $course ) {
						$checked = isset( $bb_tutorlms_groups['courses'][ $course->ID ] ) ? $bb_tutorlms_groups['courses'][ $course->ID ] : '';
						?>
                        <div class="field-group bp-checkbox-wrap">
                            <p class="checkbox bp-checkbox-wrap bp-group-option-enable">
                                <input type="checkbox" name="bb-tutorlms-group[courses][<?php echo esc_attr( $course->ID ); ?>]" id="bb-tutorlms-group-course-<?php echo esc_attr( $course->ID ); ?>" class="bs-styled-checkbox" value="<?php echo esc_attr( $course->ID ); ?>" <?php checked( $checked, $course->ID ); ?>/>
                                <label for="bp-edit-group-tutorlms"><span><?php echo esc_attr( $course->post_title ); ?></span></label>
                            </p>
                        </div>
						<?php
					}
					?>
                </fieldset>
				<?php
			}
			?>

            <input type="hidden" id="bp-tutorlms-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
			<?php wp_nonce_field( 'groups_edit_save_tutorlms', 'tutorlms_group_admin_ui' ); ?>
        </div>
		<?php
	}

	/**
	 * Save the admin Group TutorLMS settings on edit group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $group_id Group ID.
	 */
	public function admin_tutorlms_settings_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		// Admin Nonce check.
		check_admin_referer( 'groups_edit_save_tutorlms', 'tutorlms_group_admin_ui' );

        $bb_tutorlms_groups = $_POST[ 'bb-tutorlms-group' ];

		$group_id = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		groups_update_groupmeta( $group_id, 'bb-tutorlms-group', $bb_tutorlms_groups );

		/**
		 * Add action that fire before user redirect.
		 *
		 * @Since BuddyBoss [BBVERSION]
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );
	}
}
