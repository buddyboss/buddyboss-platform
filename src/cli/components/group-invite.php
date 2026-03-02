<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress group invites.
 *
 * @since BuddyPress 1.5.0
 */
class Group_Invite extends BuddypressCommand {

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
	 * Invite a member to a group.
	 *
	 * ## OPTIONS
	 *
	 * [--group-id=<group>]
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * [--user-id=<user>]
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--inviter-id=<user>]
	 * : Identifier for the inviter. Accepts either a user_login or a numeric ID.
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_invite_user()
	 *
	 * [--silent]
	 * : Whether to silent the invite creation.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite add --group-id=40 --user-id=10 --inviter-id=1331
	 *     Success: Member invited to the group.
	 *
	 *     $ wp bp group invite create --group-id=40 --user-id=admin --inviter-id=804
	 *     Success: Member invited to the group.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = bp_parse_args(
			$assoc_args,
			array(
				'user-id'       => '',
				'group-id'      => '',
				'inviter-id'    => '',
				'date-modified' => bp_core_current_time(),
				'is-confirmed'  => 0,
			)
		);

		$group_id = $this->get_group_id_from_identifier( $r['group-id'] );
		$user     = $this->get_user_id_from_identifier( $r['user-id'] );
		$inviter  = $this->get_user_id_from_identifier( $r['inviter-id'] );

		$invite = groups_invite_user(
			array(
				'user_id'       => $user->ID,
				'group_id'      => $group_id,
				'inviter_id'    => $inviter->ID,
				'date_modified' => $assoc_args['date-modified'],
				'is_confirmed'  => $assoc_args['is-confirmed'],
			)
		);

		groups_send_invites(
			array(
				'inviter_id' => $inviter->ID,
				'group_id' => $group_id
			)
		);

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( $invite ) {
			WP_CLI::success( 'Member invited to the group.' );
		} else {
			WP_CLI::error( 'Could not invite the member.' );
		}
	}

	/**
	 * Uninvite a user from a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite remove --group-id=3 --user-id=10
	 *     Success: User uninvited from the group.
	 *
	 *     $ wp bp group invite remove --group-id=foo --user-id=admin
	 *     Success: User uninvited from the group.
	 *
	 * @alias uninvite
	 */
	public function remove( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_uninvite_user( $user->ID, $group_id ) ) {
			WP_CLI::success( 'User uninvited from the group.' );
		} else {
			WP_CLI::error( 'Could not remove the user.' );
		}
	}

	/**
	 * Get a list of invitations from a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
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
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite list --user-id=30 --group-id=56
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$user_id  = $user->ID;

		if ( $group_id ) {
			$invite_query = new \BP_Group_Member_Query(
				array(
					'is_confirmed' => false,
					'group_id'     => $group_id,
				)
			);

			$invites = $invite_query->results;

			// Manually filter out user ID - this is not supported by the API.
			if ( $user_id ) {
				$user_invites = array();

				foreach ( $invites as $invite ) {
					if ( $user_id === $invite->user_id ) {
						$user_invites[] = $invite;
					}
				}

				$invites = $user_invites;
			}

			if ( empty( $invites ) ) {
				WP_CLI::error( 'No invitations found.' );
			}

			if ( empty( $assoc_args['fields'] ) ) {
				$fields = array();

				if ( ! $user_id ) {
					$fields[] = 'user_id';
				}

				$fields[] = 'inviter_id';
				$fields[] = 'invite_sent';
				$fields[] = 'date_modified';

				$assoc_args['fields'] = $fields;
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $invites );
		} else {
			$invite_query = groups_get_invites_for_user( $user_id );
			$invites      = $invite_query['groups'];

			if ( empty( $assoc_args['fields'] ) ) {
				$fields = array(
					'id',
					'name',
					'slug',
				);

				$assoc_args['fields'] = $fields;
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $invites );
		}
	}

	/**
	 * Generate random group invitations.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many groups invitations to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp group invite generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating random group invitations', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {

			$random_group = \BP_Groups_Group::get_random( 1, 1 );
			$this->add(
				array(),
				array(
					'user-id'    => $this->get_random_user_id(),
					'group-id'   => $random_group['groups'][0]->slug,
					'inviter-id' => $this->get_random_user_id(),
					'silent',
				)
			);

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Accept a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite accept --group-id=3 --user-id=10
	 *     Success: User is now a "member" of the group.
	 *
	 *     $ wp bp group invite accept --group-id=foo --user-id=admin
	 *     Success: User is now a "member" of the group.
	 */
	public function accept( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_accept_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'User is now a "member" of the group.' );
		} else {
			WP_CLI::error( 'Could not accept user invitation to the group.' );
		}
	}

	/**
	 * Reject a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite reject --group-id=3 --user-id=10
	 *     Success: Member invitation rejected.
	 *
	 *     $ wp bp group invite reject --group-id=foo --user-id=admin
	 *     Success: Member invitation rejected.
	 */
	public function reject( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_reject_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Member invitation rejected.' );
		} else {
			WP_CLI::error( 'Could not reject member invitation.' );
		}
	}

	/**
	 * Delete a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite delete --group-id=3 --user-id=10
	 *     Success: Member invitation deleted from the group.
	 *
	 *     $ wp bp group invite delete --group-id=foo --user-id=admin
	 *     Success: Member invitation deleted from the group.
	 *
	 * @alias remove
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_delete_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Member invitation deleted from the group.' );
		} else {
			WP_CLI::error( 'Could not delete member invitation from the group.' );
		}
	}
}
