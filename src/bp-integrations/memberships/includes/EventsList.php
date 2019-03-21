<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\WpListTable as WpListTable;

class EventsList extends WpListTable {

	/** Class constructor */
	public function __construct() {

		parent::__construct([
			'singular' => __('Event', 'buddyboss'), //singular name of the listed records
			'plural' => __('Events', 'buddyboss'), //plural name of the listed records
			'ajax' => false, //does this table support ajax?
		]);

	}

	/**
	 * Retrieve events data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_events($per_page = 5, $page_number = 1) {

		$results = BpMemberships::getProductEvents();

		error_log(print_r($results, true));
		return $results;
	}

	/**
	 * Delete a event record.
	 *
	 * @param int $id event ID
	 */
	public static function delete_event($id) {

		// Not applicable
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		$results = BpMemberships::getProductEvents();

		return count($results);
	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		_e('No events avaliable.', 'buddyboss');
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default($item, $column_name) {
		switch ($column_name) {
		case 'product_id':
			$html = "";
			$productId = $item[$column_name];
			$product = get_post($productId, OBJECT);
			$html .= "<a target=\"_blank\" href=\"post.php?post=$productId&action=edit\">&nbsp;&nbsp;$product->post_title</a></br>";
			return $html;
		case 'event_identifier':
			return $item[$column_name];
		case 'user_id':
			$html = "";
			$userId = $item[$column_name];
			$user = get_userdata($userId);
			$html .= "<a target=\"_blank\" href=\"user-edit.php?user_id=$userId\">&nbsp;&nbsp;$user->user_login</a></br>";

			return $html;
		case 'course_attached':
			$html = "";
			foreach (unserialize($item[$column_name]) as $key => $courseId) {
				$course = get_post($courseId, OBJECT);
				$html .= "<a target=\"_blank\" href=\"post.php?post=$courseId&action=edit\">&nbsp;&nbsp;$course->post_title</a></br>";
			}

			return $html;
		case 'grant_access':
			return $item[$column_name] ? "Access Granted" : "Access Revoked";
		case 'created_at':
			return $item[$column_name];
		case 'updated_at':
			return $item[$column_name];
		default:
			return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name($item) {

		$delete_nonce = wp_create_nonce('sp_delete_event');

		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
			'delete' => sprintf('<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['ID']), $delete_nonce),
		];

		return $title . $this->row_actions($actions);
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		error_log("get_columns()");

		$columns = [
			'cb' => '<input type="checkbox" />',
			'product_id' => __('Product', 'buddyboss'),
			'user_id' => __('User', 'buddyboss'),
			'course_attached' => __('Course attached', 'buddyboss'),
			'grant_access' => __('Action', 'buddyboss'),
			'event_identifier' => __('Identifier', 'buddyboss'),
			'created_at' => __('Creation Date', 'buddyboss'),
			'updated_at' => __('Last Modified', 'buddyboss'),
		];

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'product_id' => array('product_id', true),
			'user_id' => array('user_id', false),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [];

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page = $this->get_items_per_page('events_per_page', 1);
		$current_page = $this->get_pagenum();
		$total_items = self::record_count();

		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page, //WE have to determine how many items to show on a page
		]);

		$this->items = self::get_events($per_page, $current_page);
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ('delete' === $this->current_action()) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr($_REQUEST['_wpnonce']);

			if (!wp_verify_nonce($nonce, 'sp_delete_event')) {
				die('Go get a life script kiddies');
			} else {
				self::delete_event(absint($_GET['events']));

				// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
				// add_query_arg() return the current url
				wp_redirect(esc_url_raw(add_query_arg()));
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
			|| (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
		) {

			$delete_ids = esc_sql($_POST['bulk-delete']);

			// loop over the array of record IDs and delete them
			foreach ($delete_ids as $id) {
				self::delete_event($id);

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
			// add_query_arg() return the current url
			wp_redirect(esc_url_raw(add_query_arg()));
			exit;
		}
	}

}
