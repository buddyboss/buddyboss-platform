<?php
/**
 * @version 3.0.0
 */

if ( bp_activity_embed_has_activity( bp_current_action() ) ) :
	while ( bp_activities() ) :
		bp_the_activity();

		?>
		<div class="bp-embed-excerpt"><?php bp_activity_embed_excerpt(); ?></div>
		<?php
		bp_activity_embed_media();

	endwhile;
endif;
