<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress XProfile.
 *
 * ## EXAMPLES
 *
 *     # Save a xprofile data to a user with its field and value.
 *     $ wp bp xprofile data set --user-id=45 --field-id=120 --value=teste
 *     Success: Updated XProfile field "Field Name" (ID 120) with value  "teste" for user user_login (ID 45).
 *
 *     # Create a xprofile group.
 *     $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
 *     Success: Created XProfile field group "Group Name" (ID 123).
 *
 *     # List xprofile fields.
 *     $ wp bp xprofile field list
 */
class XProfile extends BuddypressCommand {

	/**
	 * Adds description and subcomands to the DOC.
	 *
	 * @param  string $command Command.
	 * @return string
	 */
	private function command_to_array( $command ) {
		$dump = array(
			'name' => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc' => $command->get_longdesc(),
		);

		foreach ( $command->get_subcommands() as $subcommand ) {
			$dump['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $dump['subcommands'] ) ) {
			$dump['synopsis'] = (string) $command->get_synopsis();
		}

		return $dump;
	}

}
