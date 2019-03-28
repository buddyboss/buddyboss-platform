
<script type="text/html" id="tmpl-bp-messages-form">
	<?php bp_nouveau_messages_hook( 'before', 'compose_content' ); ?>

	<label for="send-to-input"><?php esc_html_e( 'New Message', 'buddyboss' ); ?></label>
	<select
		name="send_to[]"
		class="send-to-input"
		id="send-to-input"
		placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>"
		autocomplete="off"
		multiple="multiple"
		style="width: 100%"
	>
		<?php if ( ! empty( $_GET['r'] ) ): ?>
			<option value="@<?php echo esc_attr( $_GET['r'] ); ?>" selected>
				<?php echo bp_core_get_user_displayname( get_user_by( 'login', $_GET['r'] )->ID ); ?>
			</option>
		<?php endif; ?>
	</select>

	<div id="bp-message-content"></div>

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>

	<div class="submit">
		<input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php esc_attr_e( 'Send', 'buddyboss' ); ?>"/>
		<input type="button" id="bp-messages-reset" class="text-button small bp-secondary-action" value="<?php esc_attr_e( 'Reset', 'buddyboss' ); ?>"/>
	</div>
</script>
