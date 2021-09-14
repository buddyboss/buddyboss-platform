<?php
/**
 * Create hierarchical list of bbPress replies.
 *
 * @package BuddyBoss
 * @since 0.1.0
 */
class Rest_BBP_Walker_Reply extends Walker {

	/**
	 * Tree type.
	 *
	 * @see Walker::$tree_type
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @var string
	 */
	public $tree_type = 'reply';

	/**
	 * DB column fields.
	 *
	 * @see Walker::$db_fields
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @var array
	 */
	public $db_fields = array(
		'parent' => 'reply_to',
		'id'     => 'ID',
	);

	/**
	 * Confirm the tree_type
	 *
	 * @since 2.6.0 bbPress (r5389)
	 */
	public function __construct() {
		$this->tree_type = bbp_get_reply_post_type();
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * @see   Walker::start_lvl()
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of reply.
	 * @param array  $args   Uses 'style' argument for type of HTML list.
	 */
	public function start_lvl( &$output = '', $depth = 0, $args = array() ) {
		bbpress()->reply_query->reply_depth = (int) $depth + 1;
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @see   Walker::end_lvl()
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of reply.
	 * @param array  $args   Will only append content if style argument value is 'ol' or 'ul'.
	 */
	public function end_lvl( &$output = '', $depth = 0, $args = array() ) {
		bbpress()->reply_query->reply_depth = (int) $depth + 1;
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @param object $element           Data object.
	 * @param array  $children_elements List of elements to continue traversing (passed by reference).
	 * @param int    $max_depth         Max depth to traverse.
	 * @param int    $depth             Depth of current element.
	 * @param array  $args              An array of arguments.
	 * @param string $output            Used to append additional content (passed by reference).
	 */
	public function display_element( $element = false, &$children_elements = array(), $max_depth = 0, $depth = 0, $args = array(), &$output = '' ) {

		if ( empty( $element ) ) {
			return;
		}

		// Get element's id.
		$id_field = $this->db_fields['id'];
		$id       = $element->$id_field;

		// Display element.
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

		// If we're at the max depth and the current element still has children, loop over those
		// and display them at this level to prevent them being orphaned to the end of the list.
		if ( ( $max_depth <= (int) $depth + 1 ) && isset( $children_elements[ $id ] ) ) {
			foreach ( $children_elements[ $id ] as $child ) {
				$this->display_element( $child, $children_elements, $max_depth, $depth, $args, $output );
			}
			unset( $children_elements[ $id ] );
		}
	}

	/**
	 * Start the element output.
	 *
	 * @see Walker:start_el()
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @param string $output            Used to append additional content (passed by reference).
	 * @param object $object            The data object.
	 * @param int    $depth             Depth of the item.
	 * @param array  $args              An array of additional arguments.
	 * @param int    $current_object_id ID of the current item.
	 */
	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		global $buddyboss_thread_reply;

		// Set up reply.
		$depth++;
		$object->depth                         = $depth;
		$buddyboss_thread_reply[ $object->ID ] = $object;
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @since 2.4.0 bbPress (r4944)
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param object $object The data object.
	 * @param int    $depth  Depth of the item.
	 * @param array  $args   An array of additional arguments.
	 */
	public function end_el( &$output = '', $object = false, $depth = 0, $args = array() ) {
	}
}

