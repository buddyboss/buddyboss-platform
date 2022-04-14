<?php
/**
 * BuddyBoss Video Theme Compatibility.
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main theme compat class for BuddyBoss Video.
 *
 * This class sets up the necessary theme compatibility actions to safely output
 * video template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Video_Theme_Compat {

	/**
	 * Set up the video component theme compatibility.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_video' ) );
	}

	/**
	 * Set up the theme compatibility hooks, if we're looking at an video page.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function is_video() {

		// Bail if not looking at a group.
		if ( ! bp_is_video_component() ) {
			return;
		}

		// Video Directory.
		if ( ! bp_displayed_user_id() && ! bp_current_action() ) {
			bp_update_is_directory( true, 'video' );

			/** This action is documented in bp-video/bp-video-screens.php */
			do_action( 'bp_video_screen_index' );

			add_filter( 'bp_get_buddypress_template', array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content', array( $this, 'directory_content' ) );
		}
	}

	/** Directory *************************************************************/

	/**
	 * Add template hierarchy to theme compat for the video directory page.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 *
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {

		/**
		 * Filters the template hierarchy for the video directory page.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $index-directory Array holding template names to be merged into template list.
		 */
		$new_templates = apply_filters(
			'bp_template_hierarchy_video_directory',
			array(
				'video/index-directory.php',
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
	 * @since BuddyBoss 1.7.0
	 */
	public function directory_dummy_post() {
		$video_page_id = bp_core_get_directory_page_id( 'video' );
		bp_theme_compat_reset_post(
			array(
				'ID'             => ! empty( $video_page_id ) ? $video_page_id : 0,
				'post_title'     => bp_get_directory_title( 'video' ),
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
	 * @since BuddyBoss 1.7.0
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'video/index', null, false );
	}

	/** Single ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for video permalink pages.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 *
	 * @return array $templates Array of custom templates to look for.
	 */
	public function single_template_hierarchy( $templates ) {

		/**
		 * Filters the template hierarchy for the video permalink pages.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $index Array holding template names to be merged into template list.
		 */
		$new_templates = apply_filters(
			'bp_template_hierarchy_video_single_item',
			array(
				'video/single/index.php',
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
	 * @since BuddyBoss 1.7.0
	 */
	public function single_dummy_post() {
		$video_page_id = bp_core_get_directory_page_id( 'video' );
		bp_theme_compat_reset_post(
			array(
				'ID'             => ! empty( $video_page_id ) ? $video_page_id : 0,
				'post_title'     => __( 'Videos', 'buddyboss' ),
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
	 * Filter the_content with the members' video permalink template part.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function single_dummy_content() {
		return bp_buffer_template_part( 'video/single/home', null, false );
	}
}
