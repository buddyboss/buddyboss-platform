<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyBoss Groups.
 *
 * @since BuddyPress 1.5.0
 */
class Group extends BuddypressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'name',
		'slug',
		'status',
		'date_created',
	);

	/**
	 * Group ID Object Key
	 *
	 * @var string
	 */
	protected $obj_id_key = 'group_id';

	/**
	 * Group Object Type
	 *
	 * @var string
	 */
	protected $obj_type = 'group';

	/**
	 * Create a group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : Name of the group.
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group. If not provided, one will be generated automatically.
	 *
	 * [--description=<description>]
	 * : Group description.
	 * ---
	 * Default: 'Description for group "[name]"'
	 * ---
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
	 * Default: 1
	 * ---
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group.
	 *
	 * [--status=<status>]
	 * : Group status (public, private, hidden).
	 * ---
	 * Default: public
	 * ---
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums.
	 * ---
	 * Default: 0
	 * ---
	 *
	 * [--date-created=<date-created>]
	 * : MySQL-formatted date.
	 * ---
	 * Default: current date.
	 * ---
	 *
	 * [--silent]
	 * : Whether to silent the group creation.
	 *
	 * [--porcelain]
	 * : Return only the new group id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group create --name="Totally Cool Group"
	 *     Success: Group (ID 5465) created: http://example.com/groups/totally-cool-group/
	 *
	 *     $ wp bp group create --name="Another Cool Group" --description="Cool Group" --creator-id=54 --status=private
	 *     Success: Group (ID 6454)6 created: http://example.com/groups/another-cool-group/
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = bp_parse_args(
			$assoc_args,
			array(
				'name'         => '',
				'slug'         => '',
				'description'  => '',
				'creator-id'   => 1,
				'status'       => 'public',
				'enable-forum' => 0,
				'date-created' => bp_core_current_time(),
			)
		);

		// Auto-generate some stuff.
		if ( empty( $r['slug'] ) ) {
			$r['slug'] = groups_check_slug( sanitize_title( $r['name'] ) );
		}

		if ( empty( $r['description'] ) ) {
			$r['description'] = sprintf( 'Description for group "%s"', $r['name'] );
		}

		// Fallback for group status.
		if ( ! in_array( $r['status'], $this->group_status(), true ) ) {
			$r['status'] = 'public';
		}

		$group_id = groups_create_group(
			array(
				'name'         => $r['name'],
				'slug'         => $r['slug'],
				'description'  => $r['description'],
				'creator_id'   => $r['creator-id'],
				'status'       => $r['status'],
				'enable_forum' => $r['enable-forum'],
				'date_created' => $r['date-created'],
			)
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $group_id ) ) {
			WP_CLI::error( 'Could not create group.' );
		}

		groups_update_groupmeta( $group_id, 'total_member_count', 1 );

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $group_id );
		} else {
			$group     = groups_get_group(
				array(
					'group_id' => $group_id,
				)
			);
			$permalink = bp_get_group_permalink( $group );
			WP_CLI::success( sprintf( 'Group (ID %d) created: %s', $group_id, $permalink ) );
		}
	}

	/**
	 * Generate random groups.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many groups to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--status=<status>]
	 * : The status of the generated groups. Specify public, private, hidden, or mixed.
	 * ---
	 * default: public
	 * ---
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums.
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group generate --count=50
	 *     $ wp bp group generate --count=5 --status=mixed
	 *     $ wp bp group generate --count=10 --status=hidden --creator-id=30
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating groups', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->create(
				array(),
				array(
					'name'         => sprintf( 'Group - #%d', $i ),
					'creator-id'   => $assoc_args['creator-id'],
					'status'       => $this->random_group_status( $assoc_args['status'] ),
					'enable-forum' => $assoc_args['enable-forum'],
					'silent',
				)
			);

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Get a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
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
	 *     $ wp bp group get 500
	 *     $ wp bp group get group-slug
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$group_id         = $this->get_group_id_from_identifier( $args[0] );
		$group            = groups_get_group( $group_id );
		$group_arr        = get_object_vars( $group );
		$group_arr['url'] = bp_get_group_permalink( $group );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $group_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $group_arr );
	}

	/**
	 * Delete a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>...
	 * : Identifier(s) for the group(s). Can be a numeric ID or the group slug.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group delete 500
	 *     Success: Group successfully deleted.
	 *
	 *     $ wp bp group delete group-slug --yes
	 *     Success: Group successfully deleted.
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $args[0] );

		WP_CLI::confirm( 'Are you sure you want to delete this group and its metadata?', $assoc_args );

		parent::_delete(
			array( $group_id ),
			$assoc_args,
			function( $group_id ) {
				if ( groups_delete_group( $group_id ) ) {
					return array( 'success', 'Group successfully deleted.' );
				} else {
					return array( 'error', 'Could not delete the group.' );
				}
			}
		);
	}

	/**
	 * Update a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>...
	 * : Identifier(s) for the group(s). Can be a numeric ID or the group slug.
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update. See groups_create_group()
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
	 */
	public function update( $args, $assoc_args ) {
		$clean_group_ids = array();

		foreach ( $args as $group_id ) {
			$clean_group_ids[] = $this->get_group_id_from_identifier( $group_id );
		}

		parent::_update(
			$clean_group_ids,
			$assoc_args,
			function( $params ) {
				return groups_create_group( $params );
			}
		);
	}

	/**
	 * Get a list of groups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_groups()
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--user-id=<user>]
	 * : Limit results to groups of which a specific user is a member. Accepts either a user_login or a numeric ID.
	 *
	 * [--orderby=<orderby>]
	 * : Sort order for results.
	 * ---
	 * default: name
	 * options:
	 *   - date_created
	 *   - last_activity
	 *   - total_member_count
	 *   - name
	 *
	 * [--order=<order>]
	 * : Whether to sort results ascending or descending.
	 * ---
	 * default: ASC
	 * options:
	 *   - ASC
	 *   - DESC
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - csv
	 *   - count
	 *   - haml
	 * ---
	 *
	 * [--count=<number>]
	 * : How many group items to list.
	 * ---
	 * default: 50
	 * ---

	 * ## EXAMPLES
	 *
	 *     $ wp bp group list --format=ids
	 *     $ wp bp group list --format=count
	 *     $ wp bp group list --user-id=123
	 *     $ wp bp group list --user-id=user_login --format=ids
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$query_args = bp_parse_args(
			$assoc_args,
			array(
				'count'       => 50,
				'show_hidden' => true,
				'orderby'     => $assoc_args['orderby'],
				'order'       => $assoc_args['order'],
				'per_page'    => $assoc_args['count'],
			)
		);

		if ( isset( $assoc_args['user-id'] ) ) {
			$user                  = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$query_args['user_id'] = $user->ID;
		}

		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		// If count or ids, no need for group objects.
		if ( in_array( $formatter->format, array( 'ids', 'count' ), true ) ) {
			$query_args['fields'] = 'ids';
		}

		$groups = groups_get_groups( $query_args );
		if ( empty( $groups['groups'] ) ) {
			WP_CLI::error( 'No groups found.' );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', $groups['groups'] ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $groups['total'] );
		} else {
			$formatter->display_items( $groups['groups'] );
		}
	}

	/**
	 * Group Status
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return array An array of gruop status.
	 */
	protected function group_status() {
		return array( 'public', 'private', 'hidden' );
	}

	/**
	 * Gets a randon group status.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param  string $status Group status.
	 * @return string Group Status.
	 */
	protected function random_group_status( $status ) {
		$core_status = $this->group_status();

		if ( 'mixed' === $status ) {
			$status = $core_status[ array_rand( $core_status ) ];
		}

		return $status;
	}
}
