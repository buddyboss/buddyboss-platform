<?php
/**
 * The BP_RankMath_Title paper.
 *
 * @since      1.0.22
 * @package    RankMath
 * @subpackage RankMath\Paper
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Paper;

defined( 'ABSPATH' ) || exit;

/**
 * BP_RankMath_Title Error.
 */
class BP_RankMath_Title implements IPaper {

	/**
	 * Retrieves the SEO title.
	 *
	 * @param string $title Document title.
	 *
	 * @return string
	 */
	public function title( $title = '' ) {
		if ( bboss_is_user() && bboss_is_current_component( 'xprofile' ) ) {
			$custom_title = get_user_meta( bboss_displayed_user_id(), 'first_name', true );
			if ( empty( $custom_title ) ) {
				$custom_title = get_user_meta( bboss_displayed_user_id(), 'nickname', true );
			}
		} else {
			$action       = bboss_current_action();
			$custom_title = isset( bboss()->groups->current_group->name ) ? bboss()->groups->current_group->name : get_the_title();
			if ( 'admin' === $action ) {
				$action = esc_html__( 'Manage', 'buddyboss' );
			}

			if ( 'my-groups' === $action ) {
				$action = esc_html__( 'Groups', 'buddyboss' );
			}

			if ( isset( $action ) && ! empty( $action ) ) {
				$custom_title = ucfirst( $action ) . ' - ' . ( ! empty( $title ) ? $title : $custom_title . ' - ' . bboss_get_site_name() );
			}
		}

		return $custom_title;
	}

	/**
	 * Retrieves the SEO description.
	 *
	 * @return string
	 */
	public function description() {
		return isset( bboss()->groups->current_group->description ) ? bboss()->groups->current_group->description : '';
	}

	/**
	 * Retrieves the Advanced Robots.
	 *
	 * @return string
	 */
	public function advanced_robots() {
		return array();
	}

	/**
	 * Retrieves the robots.
	 *
	 * @return string
	 */
	public function robots() {
		return array();
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		return array();
	}

	/**
	 * Retrieves meta keywords.
	 *
	 * @return string
	 */
	public function keywords() {
		return array();
	}
}


/**
 * Add Page Title on Platform Group Page in Rank Math Plugin
 */
function bp_helper_rankmath_group_page_support( $title ) {

	if ( bboss_is_current_component( 'activate' ) || bboss_is_current_component( 'register' ) ) {
		return;
	}

	if (
		bboss_is_active( 'groups' ) &&
		(
			! empty( bboss()->groups->current_group ) ||
			bboss_is_current_component( 'groups' )
		)
	) {
		$group_page = new BP_RankMath_Title();
		$title      = $group_page->title( $title );
	}

	return $title;
}

add_filter( 'rank_math/frontend/title', 'RankMath\Paper\bp_helper_rankmath_group_page_support' );
