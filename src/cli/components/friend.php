<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyBoss Connections.
 *
 * @since BuddyPress 1.6.0
 */
class Friend extends BuddypressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'initiator_user_id',
		'friend_user_id',
		'is_confirmed',
		'is_limited',
	);

	/**
	 * Create a new friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the user who is sending the friendship request. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the user whose friendship is being requested. Accepts either a user_login or a numeric ID.
	 *
	 * [--force-accept]
	 * : Whether to force acceptance.
	 *
	 * [--silent]
	 * : Whether to silent the message creation.
	 *
	 * [--porcelain]
	 * : Return only the friendship id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend create user1 another_use
	 *     Success: Connection successfully created.
	 *
	 *     $ wp bp friend create user1 another_use --force-accept
	 *     Success: Connection successfully created.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		// Members.
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend    = $this->get_user_id_from_identifier( $args[1] );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		// Check if users are already friends, and bail if they do.
		if ( friends_check_friendship( $initiator->ID, $friend->ID ) ) {
			WP_CLI::error( 'These users are already friends.' );
		}

		$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force-accept' );

		if ( ! friends_add_friend( $initiator->ID, $friend->ID, $force ) ) {
			WP_CLI::error( 'There was a problem while creating the friendship.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( \BP_Friends_Friendship::get_friendship_id( $initiator->ID, $friend->ID ) );
		} else {
			if ( $force ) {
				WP_CLI::success( 'Connection successfully created.' );
			} else {
				WP_CLI::success( 'Connection successfully created but not accepted.' );
			}
		}
	}

	/**
	 * Remove a friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the friendship initiator. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the friend user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp friend remove user1 another_user
	 *     Success: Connection successfully removed.
	 *
	 * @alias delete
	 */
	public function remove( $args, $assoc_args ) {
		// Members.
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend    = $this->get_user_id_from_identifier( $args[1] );

		// Check if users are already friends, if not, bail.
		if ( ! friends_check_friendship( $initiator->ID, $friend->ID ) ) {
			WP_CLI::error( 'These users are not friends.' );
		}

		if ( friends_remove_friend( $initiator->ID, $friend->ID ) ) {
			WP_CLI::success( 'Connection successfully removed.' );
		} else {
			WP_CLI::error( 'There was a problem while removing the friendship.' );
		}
	}

	/**
	 * Mark a connection request as accepted.
	 *
	 * ## OPTIONS
	 *
	 * <friendship>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend accept_invitation 2161
	 *     Success: Connection successfully accepted.
	 *
	 *     $ wp bp friend accept 2161
	 *     Success: Connection successfully accepted.
	 *
	 * @alias accept_invitation
	 */
	public function accept( $args, $assoc_args ) {
		foreach ( $args as $friendship_id ) {
			if ( friends_accept_friendship( (int) $friendship_id ) ) {
				WP_CLI::success( 'Connection successfully accepted.' );
			} else {
				WP_CLI::error( 'There was a problem accepting the friendship.' );
			}
		}
	}

	/**
	 * Mark a connection request as rejected.
	 *
	 * ## OPTIONS
	 *
	 * <friendship>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend reject_invitation 2161
	 *     Success: Connection successfully accepted.
	 *
	 *     $ wp bp friend reject 2161 151 2121
	 *     Success: Connection successfully accepted.
	 *
	 * @alias reject_invitation
	 */
	public function reject( $args, $assoc_args ) {
		foreach ( $args as $friendship_id ) {
			if ( friends_reject_friendship( (int) $friendship_id ) ) {
				WP_CLI::success( 'Connection successfully rejected.' );
			} else {
				WP_CLI::error( 'There was a problem rejecting the friendship.' );
			}
		}
	}

	/**
	 * Check whether two users are friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the other user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend check 2161 65465
	 *     Success: Yes, they are friends.
	 *
	 *     $ wp bp friend see 2121 65456
	 *     Success: Yes, they are friends.
	 *
	 * @alias see
	 */
	public function check( $args, $assoc_args ) {
		// Members.
		$user   = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( friends_check_friendship( $user->ID, $friend->ID ) ) {
			WP_CLI::success( 'Yes, they are friends.' );
		} else {
			WP_CLI::error( 'No, they are not friends.' );
		}
	}

	/**
	 * Get a list of user's friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
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
	 *     $ wp bp friend list 65465 --format=ids
	 *     $ wp bp friend list 2422 --format=count
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );
		$user      = $this->get_user_id_from_identifier( $args[0] );
		$friends   = \BP_Friends_Friendship::get_friendships( $user->ID );

		if ( empty( $friends ) ) {
			WP_CLI::error( 'This member has no friends.' );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $friends, 'friend_user_id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $friends );
		} else {
			$formatter->display_items( $friends );
		}
	}

	/**
	 * Generate random friendships.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many friendships to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--initiator=<user>]
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * [--friend=<user>]
	 * : ID of the second user. Accepts either a user_login or a numeric ID.
	 *
	 * [--force-accept]
	 * : Whether to force acceptance.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend generate --count=50
	 *     $ wp bp friend generate --initiator=121 --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating friendships', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {

			if ( isset( $assoc_args['initiator'] ) ) {
				$user   = $this->get_user_id_from_identifier( $assoc_args['initiator'] );
				$member = $user->ID;
			} else {
				$member = $this->get_random_user_id();
			}

			if ( isset( $assoc_args['friend'] ) ) {
				$user_2 = $this->get_user_id_from_identifier( $assoc_args['friend'] );
				$friend = $user_2->ID;
			} else {
				$friend = $this->get_random_user_id();
			}

			$this->create(
				array( $member, $friend ),
				array(
					'silent',
					'force-accept',
				)
			);

			$notify->tick();
		}

		$notify->finish();
	}
}
