<?php
/**
 * The template for BP Nouveau Component's directory filters template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_current_component() === 'activity' ) {
	return '';
}

$bb_get_filter_id = bboss_nouveau_get_filter_id();
?>

<div id="bb-rl-dir-filters" class="component-filters clearfix">
	<div id="bb-rl-<?php bboss_nouveau_filter_container_id(); ?>" class="last filter bb-rl-filter">
		<label class="bb-rl-filter-label" for="<?php echo esc_attr( $bb_get_filter_id ); ?>">
			<span ><?php bboss_nouveau_filter_label(); ?></span>
		</label>
		<div class="select-wrap">
			<select id="<?php echo esc_attr( $bb_get_filter_id ); ?>" data-bp-filter="<?php bboss_nouveau_filter_component(); ?>">
				<?php bboss_nouveau_filter_options(); ?>
			</select>
			<span class="select-arrow" aria-hidden="true"></span>
		</div>
	</div>
</div>
