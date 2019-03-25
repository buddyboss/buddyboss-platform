<?php
/**
 * BuddyBoss Media templates
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>

	<?php bp_nouveau_before_media_directory_content(); ?>

	<?php bp_nouveau_template_notices(); ?>

	<div class="screen-content">

		<?php bp_nouveau_media_hook( 'before_directory', 'list' ); ?>

		<div id="media-stream" class="media" data-bp-list="media">

				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-media-loading' ); ?></div>

		</div><!-- .media -->

		<?php bp_nouveau_after_media_directory_content(); ?>

	</div><!-- // .screen-content -->

