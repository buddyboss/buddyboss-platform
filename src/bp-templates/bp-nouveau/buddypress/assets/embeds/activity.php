<?php
/**
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_activity_embed_has_activity( bboss_current_action() ) ) :
	while ( bboss_activities() ) :
		bboss_the_activity();

		?>
		<div class="bp-embed-excerpt"><?php bboss_activity_embed_excerpt(); ?></div>
		<?php
		bboss_activity_embed_media();

	endwhile;
endif;
