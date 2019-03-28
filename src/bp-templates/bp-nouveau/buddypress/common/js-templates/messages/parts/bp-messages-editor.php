
<script type="text/html" id="tmpl-bp-messages-editor">
	<?php
	// Add a temporary filter on editor buttons
	add_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );

	wp_editor(
		'',
		'message_content',
		array(
			'textarea_name' => 'message_content',
			'teeny'         => false,
			'media_buttons' => false,
			'dfw'           => false,
			'tinymce'       => true,
			'quicktags'     => false,
			'tabindex'      => '3',
			'textarea_rows' => 5,
		)
	);

	// Remove the temporary filter on editor buttons
	remove_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );
	?>
</script>
