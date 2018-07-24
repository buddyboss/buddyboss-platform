<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage XProfile Groups.
 *
 * @since 1.5.0
 */
class XProfile_Group extends BuddypressCommand {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'name',
		'description',
		'group_order',
		'can_delete',
	);

	/**
	 * Object ID key.
	 *
	 * @var int
	 */
	protected $obj_id_key = 'id';

	/**
	 * Create an XProfile group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : The name for this field group.
	 *
	 * [--description=<description>]
	 * : The description for this field group.
	 *
	 * [--can-delete=<can-delete>]
	 * : Whether the group can be deleted.
	 * ---
	 * Default: true.
	 * ---
	 *
	 * [--porcelain]
	 * : Output just the new group id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
	 *     Success: Created XProfile field group "Group Name" (ID 123).
	 *
	 *     $ wp bp xprofile group add --name="Another Group" --can-delete=false
	 *     Success: Created XProfile field group "Another Group" (ID 21212).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'name'        => '',
			'description' => '',
			'can_delete'  => true,
		) );

		$group_id = xprofile_insert_field_group( $r );

		if ( ! $group_id ) {
			WP_CLI::error( 'Could not create field group.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $group_id );
		} else {
			$group   = new \BP_XProfile_Group( $group_id );
			$success = sprintf(
				'Created XProfile field group "%s" (ID %d).',
				$group->name,
				$group->id
			);
			WP_CLI::success( $success );
		}
	}

	/**
	 * Fetch specific XProfile field group.
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>
	 * : Identifier for the field group.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 * ---
	 * Default: All fields.
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile group get 500
	 *     $ wp bp xprofile group see 56 --format=json
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$field_group_id = $args[0];

		if ( ! is_numeric( $field_group_id ) ) {
			WP_CLI::error( 'Please provide a numeric field group ID.' );
		}

		$object = xprofile_get_field_group( $field_group_id );
		if ( ! is_object( $object ) && empty( $object->id ) ) {
			WP_CLI::error( 'No XProfile field group found.' );
		}

		$object_arr = get_object_vars( $object );
		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $object_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $object_arr );
	}

	/**
	 * Delete a specific XProfile field group.
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>...
	 * : ID or IDs for the field group.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile group delete 500 --yes
	 *
	 * @alias remove
	 */
	public function delete( $args, $assoc_args ) {
		$field_group_id = $args[0];
		WP_CLI::confirm( 'Are you sure you want to delete this field group?', $assoc_args );

		parent::_delete( array( $field_group_id ), $assoc_args, function( $field_group_id ) {
			if ( ! is_numeric( $field_group_id ) ) {
				WP_CLI::error( 'This is not a valid field group ID.' );
			}

			if ( xprofile_delete_field_group( $field_group_id ) ) {
				return array( 'success', 'Field group deleted.' );
			} else {
				return array( 'error', 'Could not delete the field group.' );
			}
		} );
	}
}
