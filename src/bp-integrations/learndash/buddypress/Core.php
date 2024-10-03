<?php
/**
 * BuddyBoss LearnDash integration core class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Admin;
use Buddyboss\LearndashIntegration\Buddypress\Ajax;
use Buddyboss\LearndashIntegration\Buddypress\Components\BpGroupCourses;
use Buddyboss\LearndashIntegration\Buddypress\Components\BpGroupReports;
use Buddyboss\LearndashIntegration\Buddypress\Courses;
use Buddyboss\LearndashIntegration\Buddypress\Forum;
use Buddyboss\LearndashIntegration\Buddypress\Group;
use Buddyboss\LearndashIntegration\Buddypress\GroupBpComponent;
use Buddyboss\LearndashIntegration\Buddypress\Helpers;
use Buddyboss\LearndashIntegration\Buddypress\Hooks;
use Buddyboss\LearndashIntegration\Buddypress\Reports;
use Buddyboss\LearndashIntegration\Buddypress\Sync;

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
		 $this->helpers = new Helpers();
		$this->courses  = new Courses();
		$this->reports  = new Reports();
		$this->ajax     = new Ajax();
		$this->sync     = new Sync();
		$this->hooks    = new Hooks();
		$this->admin    = new Admin();
		$this->group    = new Group();

		add_action( 'bp_ld_sync/init', array( $this, 'init' ) );
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
	 * Register BP group extension components based on settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function registerGroupComponent() {
		if ( ! bp_is_group() && ! bp_is_group_create() ) {
			return;
		}

		if ( bp_ld_sync( 'settings' )->get( 'learndash.enabled' ) ) {
			require_once bp_ld_sync()->path( '/buddypress/components/BpGroupCourses.php' );
			$extension = new BpGroupCourses();
			add_action( 'bp_actions', array( $extension, '_register' ), 8 );
			add_action( 'admin_init', array( $extension, '_register' ) );
		}

		if ( bp_ld_sync( 'settings' )->get( 'reports.enabled' ) ) {
			require_once bp_ld_sync()->path( '/buddypress/components/BpGroupReports.php' );
			$extension = new BpGroupReports();
			add_action( 'bp_actions', array( $extension, '_register' ), 8 );
			add_action( 'admin_init', array( $extension, '_register' ) );
		}
	}

	/**
	 * Register the path the bp template stack
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerPluginTemplate() {
		return bp_learndash_path( '/templates' );
	}

	/**
	 * Get the courses tab's sub menu items in group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function coursesSubMenus() {
		return wp_list_sort(
			apply_filters(
				'bp_ld_sync/courses_group_tab_subnavs',
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
	 * Get the reports tab's sub menu items in group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function reportsSubMenus() {
		return wp_list_sort(
			apply_filters(
				'bp_ld_sync/reports_group_tab_subnavs',
				array(
					'reports' => array(
						'name'     => __( 'Reports', 'buddyboss' ),
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
