<?php
/**
 * LearnDash Group Reports Navigation Template
 *
 * Available Variables:
 * $groupId - (int) Current social group id
 * $hasLdGroup - (bool) Current social group has an associated LearnDash group
 * $currentMenu - (str) Current sub menu name
 * $subMenus - (arr) Array of sub menu objects
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

// Don't show menu if only 1 item or less.
if ( count( $subMenus ) < 2 ) {
	return;
}

?>
<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group reports menu', 'buddyboss' ); ?>">
	<ul class="subnav">
		<?php foreach ( $subMenus as $menu ) : ?>
			<li class="<?php echo esc_attr( $currentMenu === $menu['slug'] ? 'current selected' : '' ); ?>">
				<a href="<?php echo esc_url( $menu['url'] ); ?>"><?php echo esc_html( $menu['name'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
