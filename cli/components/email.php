<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Email Post Types.
 *
 * @since BuddyPress 1.6.0
 */
class Email extends BuddypressCommand {

	/**
	 * Create a new email post connected to an email type.
	 *
	 * ## OPTIONS
	 *
	 * --type=<type>
	 * : Email type for the email (should be unique identifier, sanitized like a post slug).
	 *
	 * --type-description=<type-description>
	 * : Email type description.
	 *
	 * --subject=<subject>
	 * : Email subject line. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.
	 *
	 * [--content=<content>]
	 * : Email content. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.
	 *
	 * [--plain-text-content=<plain-text-content>]
	 * : Plain-text email content. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.
	 *
	 * [<file>]
	 * : Read content from <file>. If this value is present, the
	 *     `--content` argument will be ignored.
	 *
	 *   Passing `-` as the filename will cause post content to
	 *   be read from STDIN.
	 *
	 * [--edit]
	 * : Immediately open system's editor to write or edit email content.
	 *
	 *   If content is read from a file, from STDIN, or from the `--content`
	 *   argument, that text will be loaded into the editor.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create email post
	 *     $ wp bp email create --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --content="<a href='{{{some.custom-token-url}}}'></a>A new event</a> was created" --plain-text-content="A new event was created"
	 *     Success: Email post created for type "new-event".
	 *
	 *     # Create email post with content from given file
	 *     $ wp bp email create ./email-content.txt --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --plain-text-content="A new event was created"
	 *     Success: Email post created for type "new-event".
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$switched = false;

		if ( false === bp_is_root_blog() ) {
			$switched = true;
			switch_to_blog( bp_get_root_blog_id() );
		}

		$term = term_exists( $assoc_args['type'], bp_get_email_tax_type() );

		// Term already exists so don't do anything.
		if ( 0 !== $term && null !== $term ) {
			if ( true === $switched ) {
				restore_current_blog();
			}

			WP_CLI::error( sprintf( 'Email type %s already exists.', $assoc_args['type'] ) );
		}

		if ( ! empty( $args[0] ) ) {
			$assoc_args['content'] = $this->read_from_file_or_stdin( $args[0] );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'edit' ) ) {
			$input  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'content', '' );
			$output = $this->_edit( $input, 'WP-CLI: New BP Email Content' );

			if ( $output ) {
				$assoc_args['content'] = $output;
			} else {
				$assoc_args['content'] = $input;
			}
		}

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => bp_get_email_post_type(),
		);

		$email = array(
			'post_title'   => $assoc_args['subject'],
			'post_content' => $assoc_args['content'],
			'post_excerpt' => ! empty( $assoc_args['plain-text-content'] ) ? $assoc_args['plain-text-content'] : '',
		);

		$id = $assoc_args['type'];

		// Email post content.
		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ), true );

		// Save the situation.
		if ( ! is_wp_error( $post_id ) ) {
			$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );

			// Situation description.
			if ( ! is_wp_error( $tt_ids ) && ! empty( $assoc_args['type-description'] ) ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $tt_ids[0], bp_get_email_tax_type() );
				wp_update_term(
					(int) $term->term_id,
					bp_get_email_tax_type(),
					array(
						'description' => $assoc_args['type-description'],
					)
				);
			}

			if ( true === $switched ) {
				restore_current_blog();
			}

			WP_CLI::success( sprintf( 'Email post created for type %s.', $assoc_args['type'] ) );
		} else {
			if ( true === $switched ) {
				restore_current_blog();
			}

			WP_CLI::error( "There was a problem creating the email post for type '{$assoc_args['type']}' - " . $post_id->get_error_message() );
		}
	}

	/**
	 * Get details for a post connected to an email type.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : The email type to fetch the post details for.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole post, returns the value of a single field.
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
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     # Output the post ID for the 'activity-at-message' email type
	 *     $ wp bp email get-post activity-at-message --fields=ID
	 *
	 * @alias get-post
	 * @alias see
	 */
	public function get_post( $args, $assoc_args ) {
		$email = bp_get_email( $args[0] );

		if ( is_wp_error( $email ) ) {
			WP_CLI::error( sprintf( 'Email post for type %s does not exist.', $args[0] ) );
		}

		$post_arr = get_object_vars( $email->get_post_object() );
		unset( $post_arr['filter'] );
		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $post_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $email->get_post_object() );
	}

	/**
	 * Reinstall BuddyPress default emails.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp email reinstall --yes
	 *     Success: Emails have been successfully reinstalled.
	 */
	public function reinstall( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to reinstall BuddyPress emails?', $assoc_args );

		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php';

		$result = bp_admin_reinstall_emails();

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( $result[1] );
		}
	}

	/**
	 * Helper method to use the '--edit' flag.
	 *
	 * Copied from Post_Command::_edit().
	 *
	 * @param  string $content Post content.
	 * @param  string $title   Post title.
	 * @return mixed
	 */
	protected function _edit( $content, $title ) {
		$content = apply_filters( 'the_editor_content', $content );
		$output  = \WP_CLI\Utils\launch_editor_for_input( $content, $title );

		return ( is_string( $output ) ) ?
			apply_filters( 'content_save_pre', $output )
			: $output;
	}
}
