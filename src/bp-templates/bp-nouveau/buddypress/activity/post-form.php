<?php
/**
 * BuddyBoss - Activity Post Form
 *
 * @version 3.1.0
 */

?>

<?php
/*
 * Template tag to prepare the activity post form checks capability and enqueue needed scripts.
 */
bp_nouveau_before_activity_post_form();
?>

<h2 class="bp-screen-reader-text"><?php esc_html_e( 'Post Update', 'buddyboss' ); ?></h2>

<div id="bp-nouveau-activity-form-placeholder" class="bp-nouveau-activity-form-placeholder-<?php if ( !bp_is_active( 'media' ) ){ echo ' media-off'; } ?>"></div>

<div id="bp-nouveau-activity-form" class="activity-update-form<?php if ( !bp_is_active( 'media' ) ){ echo ' media-off'; } ?>"></div>

<?php
/*
 * Template tag to load the Javascript templates of the Post form UI.
 */
bp_nouveau_after_activity_post_form();
