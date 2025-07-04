<?php
/**
 * LearnDash Group Courses View Button Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="course-link">
	<a class="ld-set-cookie" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" data-group-id="<?php echo esc_attr( ( bp_is_group_single() ? bp_get_current_group_id() : '' ) ); ?>" href="<?php the_permalink(); ?>" class="button"><?php echo esc_html( $label ); ?></a>
</div>
