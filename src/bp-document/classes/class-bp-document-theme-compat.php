<?php
/**
 * BuddyBoss Document Theme Compatibility.
 *
 * @package BuddyBoss\Document
 * @since   BuddyBoss 1.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main theme compat class for BuddyBoss Document.
 * This class sets up the necessary theme compatibility actions to safely output
 * document template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyBoss 1.4.0
 */
class BP_Document_Theme_Compat {

	/**
	 * Set up the document component theme compatibility.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_document' ) );
	}

	/**
	 * Set up the theme compatibility hooks, if we're looking at an document page.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function is_document() {

		// Bail if not looking at a group.
		if ( ! bp_is_document_component() ) {
			return;
		}

		// Document Directory.
		if ( ! bp_displayed_user_id() && ! bp_current_action() && 'document' === bp_current_component() ) {
			bp_update_is_directory( true, 'document' );

			/** This action is documented in bp-document/bp-document-screens.php */
			do_action( 'bp_document_screen_index' );

			add_filter( 'bp_get_buddypress_template', array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content', array( $this, 'directory_content' ) );

		}
	}

	/** Directory *************************************************************/

	/**
	 * Add template hierarchy to theme compat for the document directory page.
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 *
	 * @return array $templates Array of custom templates to look for.
	 * @since BuddyBoss 1.4.0
	 */
	public function directory_template_hierarchy( $templates ) {

		/**
		 * Filters the template hierarchy for the document directory page.
		 *
		 * @param array $index -directory Array holding template names to be merged into template list.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$new_templates = apply_filters(
			'bp_template_hierarchy_document_directory',
			array(
				'document/index-directory.php',
			)
		);

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with directory data.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function directory_dummy_post() {
		$document_page_id = bp_core_get_directory_page_id( 'document' );
		bp_theme_compat_reset_post(
			array(
				'ID'             => ! empty( $document_page_id ) ? $document_page_id : 0,
				'post_title'     => bp_get_directory_title( 'document' ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_content'   => '',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'is_page'        => true,
				'comment_status' => 'closed',
			)
		);
	}

	/**
	 * Filter the_content with the groups index template part.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'document/index', null, false );
	}

	/** Single ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for document permalink pages.
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 *
	 * @return array $templates Array of custom templates to look for.
	 * @since BuddyBoss 1.4.0
	 */
	public function single_template_hierarchy( $templates ) {

		/**
		 * Filters the template hierarchy for the document permalink pages.
		 *
		 * @param array $index Array holding template names to be merged into template list.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$new_templates = apply_filters(
			'bp_template_hierarchy_document_single_item',
			array(
				'document/single/index.php',
			)
		);

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with the displayed user's data.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function single_dummy_post() {
		$document_page_id = bp_core_get_directory_page_id( 'document' );
		bp_theme_compat_reset_post(
			array(
				'ID'             => ! empty( $document_page_id ) ? $document_page_id : 0,
				'post_title'     => __( 'Documents', 'buddyboss' ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_content'   => '',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'is_page'        => true,
				'comment_status' => 'closed',
			)
		);
	}

	/**
	 * Filter the_content with the members' document permalink template part.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function single_dummy_content() {
		return bp_buffer_template_part( 'document/single/home', null, false );
	}
}
