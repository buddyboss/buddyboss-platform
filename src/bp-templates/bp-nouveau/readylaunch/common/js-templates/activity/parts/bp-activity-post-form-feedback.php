<?php
/**
 * ReadyLaunch - The template for displaying activity post form feedback.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-form-feedback">
	<span class="bb-icons-rl-fill" aria-hidden="true"></span>
	<div class="bb-rl-notice--content">
		{{{data.message}}}
		<button class="bb-rl-notice__close" aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
			<i class="bb-icons-rl-x"></i>
		</button>
	</div>
</script>
