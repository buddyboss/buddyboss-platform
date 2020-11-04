<?php
bp_nouveau_before_loop();

if ( bp_has_moderation( bp_ajax_querystring( 'moderation' ) ) ) :
	while ( bp_moderation() ) :
		bp_the_moderation();
		//bp_get_template_part( 'document/document-entry' );
	endwhile;
else :
	bp_nouveau_user_feedback( 'moderation-requests-none' );
endif;

bp_nouveau_after_loop();
