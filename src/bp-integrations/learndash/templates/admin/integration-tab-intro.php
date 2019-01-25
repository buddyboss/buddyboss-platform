<div class="wrap bp-about-wrap">
	<h3><?php _e( 'LearnDash Settings', 'buddyboss' ); ?></h3>

	<p><?php
		printf(
			__('We integrated with %s. Below are the features if the plugin is activated:', 'buddyboss'),
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://www.learndash.com/',
				__('LearnDash', 'buddyboss')
			)
		)
	?></p>

	<ul class="wp-list">
		<li>
			<span class="dashicons dashicons-editor-unlink"></span>
			<?php _e('Members syncing between groups.', 'buddyboss'); ?>
		</li>
		<li>
			<span class="dashicons dashicons-analytics"></span>
			<?php _e('Course reports within groups.', 'buddyboss'); ?>
		</li>
	</ul>
</div>
