<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Components.
 *
 * ## EXAMPLES
 *
 *     # Activate a component.
 *     $ wp bp component activate groups
 *     Success: The Groups component has been activated.
 *
 *     # Deactive a component.
 *     $ wp bp component deactivate groups
 *     Success: The Groups component has been deactivated.
 *
 *     # List components.
 *     $ wp bp component list --type=required
 *     +--------+---------+--------+------------------------+--------------------------------------------+
 *     | number | id      | status | title                  | description                                |
 *     +--------+---------+--------+------------------------------------------+--------------------------+
 *     | 1      | core    | Active | Núcleo do BuddyPress   | É o que torna <del>viajar no tempo</del> o |
 *     |        |         |        |                        | BuddyPress possível!                       |
 *     | 2      | members | Active | Membros da Comunidade  | Tudo em uma comunidade BuddyPress gira em  |
 *     |        |         |        |                        | torno de seus membros.                     |
 *     +--------+---------+--------+------------------------------------------+--------------------------+
 *
 * @since 1.6.0
 */
class Components extends BuddypressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'number',
		'id',
		'status',
		'title',
		'description',
	);

	/**
	 * Activate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to activate.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp component activate groups
	 *     Success: The Groups component has been activated.
	 */
	public function activate( $args, $assoc_args ) {
		$component = $args[0];

		if ( ! $this->component_exists( $component ) ) {
			WP_CLI::error( sprintf( '%s is not a valid component.', ucfirst( $component ) ) );
		}

		if ( bp_is_active( $component ) ) {
			WP_CLI::error( sprintf( 'The %s component is already active.', ucfirst( $component ) ) );
		}

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		$active_components[ $component ] = 1;

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		// Ensure that dbDelta() is defined.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		// Run the setup, in case tables have to be created.
		require_once( \BP_PLUGIN_DIR . 'bp-core/admin/bp-core-admin-schema.php' );
		bp_core_install( $active_components );
		bp_core_add_page_mappings( $active_components );

		WP_CLI::success( sprintf( 'The %s component has been activated.', ucfirst( $component ) ) );
	}

	/**
	 * Deactivate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to deactivate.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp component deactivate groups
	 *     Success: The Groups component has been deactivated.
	 */
	public function deactivate( $args, $assoc_args ) {
		$component = $args[0];

		if ( ! $this->component_exists( $component ) ) {
			WP_CLI::error( sprintf( '%s is not a valid component.', ucfirst( $component ) ) );
		}

		if ( ! bp_is_active( $component ) ) {
			WP_CLI::error( sprintf( 'The %s component is not active.', ucfirst( $component ) ) );
		}

		if ( array_key_exists( $component, bp_core_get_components( 'required' ) ) ) {
			WP_CLI::error( 'You cannot deactivate a required component.' );
		}

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		unset( $active_components[ $component ] );

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		WP_CLI::success( sprintf( 'The %s component has been deactivated.', ucfirst( $component ) ) );
	}

	/**
	 * Get a list of components.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Type of the component (all, optional, retired, required).
	 * ---
	 * default: all
	 * ---
	 *
	 * [--status=<status>]
	 * : Status of the component (all, active, inactive).
	 * ---
	 * default: all
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Fields to display (id, title, description).
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - count
	 *   - csv
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp component list --format=count
	 *     10
	 *
	 *     $ wp bp component list --status=inactive --format=count
	 *     4
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Sanitize type.
		$type = $assoc_args['type'];
		if ( empty( $type ) || ! in_array( $type, $this->component_types(), true ) ) {
			$type = 'all';
		}

		// Sanitize status.
		$status = $assoc_args['status'];
		if ( empty( $status ) || ! in_array( $status, $this->component_status(), true ) ) {
			$status = 'all';
		}

		$components = bp_core_get_components( $type );

		// Active components.
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

		// Core component is always active.
		if ( 'optional' !== $type ) {
			$active_components['core'] = $components['core'];
		}

		// Inactive components.
		$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );

		$current_components  = array();
		switch ( $status ) {
			case 'all':
				$index = 0;
				foreach ( $components as $name => $labels ) {
					$index++;
					$current_components[] = array(
						'number'      => $index,
						'id'          => $name,
						'status'      => $this->verify_component_status( $name ),
						'title'       => $labels['title'],
						'description' => $labels['description'],
					);
				}
				break;

			case 'active':
				$index = 0;
				foreach ( array_keys( $active_components ) as $component ) {
					$index++;

					$info = $components[ $component ];
					$current_components[] = array(
						'number'      => $index,
						'id'          => $component,
						'status'      => 'Active',
						'title'       => $info['title'],
						'description' => $info['description'],
					);
				}
				break;

			case 'inactive':
				$index = 0;
				foreach ( $inactive_components as $component ) {
					$index++;

					$info = $components[ $component ];
					$current_components[] = array(
						'number'      => $index,
						'id'          => $component,
						'status'      => 'Inactive',
						'title'       => $info['title'],
						'description' => $info['description'],
					);
				}
				break;
		}

		// Bail early.
		if ( empty( $current_components ) ) {
			WP_CLI::error( 'There is no component available.' );
		}

		if ( 'count' === $formatter->format ) {
			$formatter->display_items( $current_components );
		} else {
			$formatter->display_items( $current_components );
		}
	}

	/**
	 * Does the component exist?
	 *
	 * @param  string $component Component.
	 *
	 * @return bool
	 */
	protected function component_exists( $component ) {
		$keys = array_keys( bp_core_get_components() );

		return in_array( $component, $keys, true );
	}

	/**
	 * Verify Component Status.
	 *
	 * @since 1.7.0
	 *
	 * @param string $id Component id.
	 *
	 * @return string
	 */
	protected function verify_component_status( $id ) {
		$active = 'Active';

		if ( 'core' === $id ) {
			return $active;
		}

		return ( bp_is_active( $id ) ) ? $active : 'Inactive';
	}

	/**
	 * Component Types.
	 *
	 * @since 1.6.0
	 *
	 * @return array An array of valid component types.
	 */
	protected function component_types() {
		return array( 'all', 'optional', 'retired', 'required' );
	}

	/**
	 * Component Status.
	 *
	 * @since 1.6.0
	 *
	 * @return array An array of valid component status.
	 */
	protected function component_status() {
		return array( 'all', 'active', 'inactive' );
	}
}
