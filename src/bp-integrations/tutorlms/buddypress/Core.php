<?php
/**
 * BuddyBoss TutorLMS integration core class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\TutorLMSIntegration\Buddypress;

use Buddyboss\TutorLMSIntegration\Buddypress\Admin;
use Buddyboss\TutorLMSIntegration\Buddypress\Ajax;
use Buddyboss\TutorLMSIntegration\Buddypress\Components\BpGroupCourses;
use Buddyboss\TutorLMSIntegration\Buddypress\Courses;
use Buddyboss\TutorLMSIntegration\Buddypress\Hooks;


// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Core class for the buddypress settings
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class Core {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		$this->courses  = new Courses();
		$this->ajax     = new Ajax();
		$this->hooks    = new Hooks();
		$this->admin    = new Admin();

		add_action( 'bb_tutorlms/init', array( $this, 'init' ) );
	}

	/**
	 * Register actions on init
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		$this->registerTemplateStack();
		$this->registerGroupComponent();
	}

	/**
	 * Add bp template stack so child theme can overwrite template
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function registerTemplateStack() {
		bp_register_template_stack( array( $this, 'registerPluginTemplate' ) );
	}

	/**
	 * Register BB group extension components based on settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function registerGroupComponent() {
		if ( ! bp_is_group() && ! bp_is_group_create() ) {
			return;
		}

		if ( bb_tutorlms( 'settings' )->get( 'tutorlms.enabled' ) ) {
			require_once bb_tutorlms()->path( '/buddypress/components/BpGroupCourses.php' );
		}

	}

	/**
	 * Register the path the bp template stack
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerPluginTemplate() {
		return bb_tutorlms_path( '/templates' );
	}

	/**
	 * Get the courses tab's sub menu items in group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function coursesSubMenus() {
		return wp_list_sort(
			apply_filters(
				'bb_tutorlms/courses_group_tab_subnavs',
				array(
					'courses' => array(
						'name'     => __( 'Courses', 'buddyboss' ),
						'slug'     => '',
						'position' => 10,
					),
				)
			),
			'position',
			'ASC',
			true
		);
	}

	/**
	 * Returns the link to the selected sub menu
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function subMenuLink( $slug ) {
		$groupUrl = untrailingslashit( bp_get_group_permalink( groups_get_current_group() ) );
		$action   = bp_current_action();
		return untrailingslashit( "{$groupUrl}/{$action}/{$slug}" );
	}
}
