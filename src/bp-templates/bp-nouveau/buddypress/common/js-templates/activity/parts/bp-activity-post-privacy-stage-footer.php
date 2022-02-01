<?php
/**
 * The template for displaying activity post privacy stage footer
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-privacy-stage-footer.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-post-privacy-stage-footer">
	<div class="privacy-status-actions">
		<input type="button" id="privacy-status-back" class="text-button small" value="<?php esc_html_e( 'Back', 'buddyboss' ); ?>">
		<input type="button" id="privacy-status-group-back" class="text-button small" value="<?php esc_html_e( 'Back', 'buddyboss' ); ?>">
		<input type="submit" id="privacy-status-submit" class="button" name="privacy-status-submit" value="<?php esc_html_e( 'Save', 'buddyboss' ); ?>">
	</div>
</script>
