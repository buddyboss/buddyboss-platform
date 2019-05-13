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
		<?php if ( ! empty( $_GET['r'] ) ):

			$user = bp_get_user_by_nickname( $_GET['r'] );
			$name = bp_core_get_user_displayname( $user->ID );
			?>
			<option value="@<?php echo esc_attr( $_GET['r'] ); ?>" selected data-action="<?php echo get_user_by( 'login', $_GET['r'] )->ID; ?>"><?php echo esc_html( $name ); ?></option>
		<?php endif; ?>
	</select>

	<div id="bp-message-content"></div>

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>
</script>
<script type="text/html" id="tmpl-bp-messages-form-submit">
    <input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php esc_attr_e( 'Send', 'buddyboss' ); ?>"/>
</script>
