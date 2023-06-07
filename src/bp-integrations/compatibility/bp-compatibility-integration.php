<?php
/**
 * BuddyBoss Compatibility Integration Class.
 *
 * @since BuddyBoss 1.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp compatibility class.
 *
 * @since BuddyBoss 1.1.5
 */
class BP_Compatibility_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'compatibility',
			__( 'BuddyPress', 'buddyboss' ),
			'compatibility',
			array(
				'required_plugin' => array(),
			)
		);

		// Add link to settings page.
		add_filter( 'plugin_action_links',               array( $this, 'action_links' ), 11000, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'action_links' ), 11000, 2 );
	}

	/**
	 * Change the Third party plugin setting link.
	 *
	 * @param $link
	 * @param $file
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @return array
	 */
	public function action_links( $link, $file ) {

		// Return normal links if not BuddyBoss Platform plugin or it's does not have a setting links
		if (
			plugin_basename( 'buddyboss-platform/bp-loader.php' ) == $file
			|| empty( $link['settings'] )
		) {
			return $link;
		}

		$extractedLinks = array();

		if ( class_exists( 'DOMDocument' ) ) {
			$htmlDom = new DOMDocument;

			// Parse the HTML of the page using DOMDocument::loadHTML
			$htmlDom->loadHTML( htmlentities( $link['settings'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );

			// Extract the links from the HTML.
			$links = $htmlDom->getElementsByTagName( 'a' );

			if ( ! empty( $links ) ) {
				foreach ( $links as $link_obj ) {
					$extractedLinks[] = $link_obj->getAttribute( 'href' );
				}
			}
		}

		if (
			! empty( $extractedLinks )
			&& in_array( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings' ), 'admin.php' ) ), $extractedLinks )
		) {
			// Add a few links to the existing links array.
			return array_merge( $link, array(
				'settings' => '<a href="' . esc_url( bp_get_admin_url( add_query_arg( array(
						'page' => 'bp-integrations',
						'tab'  => 'bp-compatibility'
					), 'admin.php' ) ) ) . '">' . esc_html__( 'Settings', 'buddyboss' ) . '</a>',
			) );
		}

		return $link;
	}

	/**
	 * Register admin setting tab, only if Compatibility plugin is disabled
	 *
	 * @since BuddyBoss 1.1.5
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( $this->path ) . 'bp-admin-compatibility-tab.php';

		new BP_Compatibility_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
