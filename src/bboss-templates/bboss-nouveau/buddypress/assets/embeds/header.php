<?php
/**
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$displayed_user_link = bboss_get_displayed_user_link();
?>
<div id="bp-embed-header">
	<div class="bp-embed-avatar">
		<a href="<?php echo esc_url( $displayed_user_link ); ?>">
			<?php bboss_displayed_user_avatar( 'type=thumb&width=36&height=36' ); ?>
		</a>
	</div>

	<p class="wp-embed-heading">
		<a href="<?php echo esc_url( $displayed_user_link ); ?>">
			<?php bboss_displayed_user_fullname(); ?>
		</a>
	</p>

	<?php if ( bboss_is_active( 'activity' ) && bboss_activity_do_mentions() ) : ?>
		<p class="bp-embed-mentionname">@<?php bboss_displayed_user_mentionname(); ?></p>
	<?php endif; ?>
</div>
