<?php
namespace BuddyBoss\Memberships\Classes;

class BpProductEvents {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $events_obj;

	// class constructor
	public function __construct() {
		add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
		add_action('admin_menu', [$this, 'plugin_menu']);
	}

	public static function set_screen($status, $option, $value) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_submenu_page(
			'',
			'BuddyBoss Memberships',
			'Memberships Settings',
			'manage_options',
			'bpms-product-events',
			[$this, 'bbmsProductEvents']
		);

		add_action("load-$hook", [$this, 'screen_option']);

	}

	/**
	 * Plugin settings page
	 */
	public function bbmsProductEvents() {
		?>
		<div class="wrap">
			<h2>BuddyBoss Memberships - Product Events</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
$this->events_obj->prepare_items();
		$this->events_obj->display();?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args = [
			'label' => 'Events',
			'default' => 5,
			'option' => 'events_per_page',
		];

		add_screen_option($option, $args);

		$this->events_obj = new Events_List();
	}

	/** Singleton instance */
	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}