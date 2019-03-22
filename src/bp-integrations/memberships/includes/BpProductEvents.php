<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\BpmsView;

class BpProductEvents {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $events_obj;

	// class constructor
	public function __construct() {
		add_filter('set-screen-option', [__CLASS__, 'setScreen'], 10, 3);
		add_action('admin_menu', [$this, 'pluginMenu']);
	}

	public static function setScreen($status, $option, $value) {
		return $value;
	}

	public function pluginMenu() {

		$hook = add_submenu_page(
			'',
			'BuddyBoss Memberships',
			'Memberships Settings',
			'manage_options',
			'bp-memberships-log',
			[$this, 'bpmsProductEvents']
		);

		add_action("load-$hook", [$this, 'screenOption']);

	}

	/**
	 * Plugin settings page
	 */
	public function bpmsProductEvents() {
		$classObj = $this;
		BpmsView::render('admin/membership-logs', get_defined_vars());
	}

	/**
	 * Screen options
	 */
	public function screenOption() {

		$option = 'per_page';
		$args = [
			'label' => 'Events',
			'default' => 5,
			'option' => 'events_per_page',
		];

		add_screen_option($option, $args);

		$this->events_obj = new EventsList();
	}

	/** Singleton instance */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}