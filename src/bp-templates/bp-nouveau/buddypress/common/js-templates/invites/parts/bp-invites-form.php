<?php
/**
 * Invites form Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/invites/parts/bp-invites-form.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bp-invites-form">

	<label for="send-invites-control"><?php esc_html_e( 'Optional: Customize the message of your invite.', 'buddyboss' ); ?></label>
	<textarea id="send-invites-control" class="bp-faux-placeholder-label" placeholder="<?php _e( 'Type message','buddyboss' ); ?>"></textarea>

	<div class="action">
		<button type="button" id="bp-invites-reset" class="button bp-secondary-action"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></button>
		<button type="button" id="bp-invites-send" class="button bp-primary-action"><?php esc_html_e( 'Send', 'buddyboss' ); ?></button>
	</div>
</script>
