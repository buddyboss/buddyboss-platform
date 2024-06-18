<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Signups.
 *
 * @since BuddyPress 1.5.0
 */
class Signup extends BuddypressCommand {

	/**
	 * Signup object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'signup_id',
		'user_login',
		'user_name',
		'meta',
		'activation_key',
		'registered',
	);

	/**
	 * Add a signup.
	 *
	 * ## OPTIONS
	 *
	 * [--user-login=<user-login>]
	 * : User login for the signup.
	 *
	 * [--user-email=<user-email>]
	 * : User email for the signup.
	 *
	 * [--activation-key=<activation-key>]
	 * : Activation key for the signup. If none is provided, a random one will be used.
	 *
	 * [--silent]
	 * : Whether to silent the signup creation.
	 *
	 * [--porcelain]
	 * : Output only the new signup id.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup create --user-login=test_user --user-email=teste@site.com
	 *     Success: Successfully added new user signup (ID #345).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = bp_parse_args(
			$assoc_args,
			array(
				'user-login'     => '',
				'user-email'     => '',
				'activation-key' => wp_generate_password( 32, false ),
			)
		);

		$signup_args = array(
			'meta' => '',
		);

		$user_login = $r['user-login'];
		if ( ! empty( $user_login ) ) {
			$user_login = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
		}

		$user_email = $r['user-email'];
		if ( ! empty( $user_email ) ) {
			$user_email = sanitize_email( $user_email );
		}

		$signup_args['user_login']     = $user_login;
		$signup_args['user_email']     = $user_email;
		$signup_args['activation_key'] = $r['activation-key'];

		$id = \BP_Signup::add( $signup_args );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $id ) {
			WP_CLI::error( 'Could not add user signup.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( sprintf( 'Successfully added new user signup (ID #%d).', $id ) );
		}
	}

	/**
	 * Get a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * [--match-field=<match-field>]
	 * : Field to match the signup-id to. Use if there is ambiguity between, eg, signup ID and user_login.
	 * ---
	 * options:
	 *   - signup_id
	 *   - user_email
	 *   - user_login
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific signup fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup get 123
	 *     $ wp bp signup get foo@example.com
	 *     $ wp bp signup get 123 --match-field=id
	 */
	public function get( $args, $assoc_args ) {
		$id          = $args[0];
		$signup_args = array(
			'number' => 1,
		);

		$signup = $this->get_signup_by_identifier( $id, $assoc_args );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $signup );
	}

	/**
	 * Delete a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>...
	 * : ID or IDs of signup.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup delete 520
	 *     Success: Signup deleted.
	 *
	 *     $ wp bp signup delete 55654 54564 --yes
	 *     Success: Signup deleted.
	 */
	public function delete( $args, $assoc_args ) {
		$signup_id = $args[0];

		WP_CLI::confirm( 'Are you sure you want to delete this signup?', $assoc_args );

		parent::_delete(
			array( $signup_id ),
			$assoc_args,
			function( $signup_id ) {
				if ( \BP_Signup::delete( array( $signup_id ) ) ) {
					return array( 'success', 'Signup deleted.' );
				} else {
					return array( 'error', 'Could not delete signup.' );
				}
			}
		);
	}

	/**
	 * Activate a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup activate ee48ec319fef3nn4
	 *     Success: Signup activated, new user (ID #545).
	 */
	public function activate( $args, $assoc_args ) {
		$signup  = $this->get_signup_by_identifier( $args[0], $assoc_args );
		$user_id = bp_core_activate_signup( $signup->activation_key );

		if ( $user_id ) {
			WP_CLI::success( sprintf( 'Signup activated, new user (ID #%d).', $user_id ) );
		} else {
			WP_CLI::error( 'Signup not activated.' );
		}
	}

	/**
	 * Generate random signups.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many signups to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating signups', $assoc_args['count'] );

		// Use the email API to get a valid "from" domain.
		$email_domain = new \BP_Email( '' );
		$email_domain = $email_domain->get_from()->get_address();
		$random_login = wp_generate_password( 12, false ); // Generate random user login.

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->create(
				array(),
				array(
					'user-login' => $random_login,
					'user-email' => $random_login . substr( $email_domain, strpos( $email_domain, '@' ) ),
					'silent',
				)
			);

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Resend activation e-mail to a newly registered user.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup resend test@example.com
	 *     Success: Email sent successfully.
	 *
	 * @alias send
	 */
	public function resend( $args, $assoc_args ) {
		$signup = $this->get_signup_by_identifier( $args[0], $assoc_args );
		$send   = \BP_Signup::resend( array( $signup->signup_id ) );

		// Add feedback message.
		if ( empty( $send['errors'] ) ) {
			WP_CLI::success( 'Email sent successfully.' );
		} else {
			WP_CLI::error( 'This account is already activated.' );
		}
	}

	/**
	 * Get a list of signups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See \BP_Signup::get()
	 *
	 * [--<number>=<number>]
	 * : How many signups to list.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - count
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup list --format=ids
	 *     $ wp bp signup list --number=100 --format=count
	 *     $ wp bp signup list --number=5 --activation_key=ee48ec319fef3nn4
	 *
	 * @subcommand list
	 */
	public function _list( $_, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$assoc_args = bp_parse_args(
			$assoc_args,
			array(
				'number' => 20,
				'fields' => 'all',
			)
		);

		if ( 'ids' === $formatter->format ) {
			$assoc_args['fields'] = 'ids';
		}

		$signups = \BP_Signup::get( $assoc_args );

		if ( empty( $signups['signups'] ) ) {
			WP_CLI::error( 'No signups found.' );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', $signups['signups'] ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			WP_CLI::line( $signups['total'] );
		} else {
			$formatter->display_items( $signups['signups'] );
		}
	}

	/**
	 * Look up a signup by the provided identifier.
	 *
	 * @since BuddyPress 1.5.0
	 */
	protected function get_signup_by_identifier( $identifier, $assoc_args ) {
		if ( isset( $assoc_args['match-field'] ) ) {
			switch ( $assoc_args['match-field'] ) {
				case 'signup_id':
					$signup_args['include'] = array( $identifier );
					break;

				case 'user_login':
					$signup_args['user_login'] = $identifier;
					break;

				case 'user_email':
				default:
					$signup_args['usersearch'] = $identifier;
					break;
			}
		} else {
			if ( is_numeric( $identifier ) ) {
				$signup_args['include'] = array( intval( $identifier ) );
			} elseif ( is_email( $identifier ) ) {
				$signup_args['usersearch'] = $identifier;
			} else {
				$signup_args['user_login'] = $identifier;
			}
		}

		$signups = \BP_Signup::get( $signup_args );
		$signup  = null;

		if ( ! empty( $signups['signups'] ) ) {
			$signup = reset( $signups['signups'] );
		}

		if ( ! $signup ) {
			WP_CLI::error( 'No signup found by that identifier.' );
		}

		return $signup;
	}
}
