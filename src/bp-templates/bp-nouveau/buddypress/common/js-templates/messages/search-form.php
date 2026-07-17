<?php
/**
 * BP Nouveau Messages search form template.
 *
 * @since   BuddyPress 3.2.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<form action="" method="get" id="user_messages_search_form" class="bp-messages-search-form" data-bp-search="messages">
	<button type="submit" id="user_messages_search_submit">
		<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss-platform' ); ?></span>
	</button>
	<label for="user_messages_search" class="bp-screen-reader-text">
		<?php esc_html_e( 'Search Messages', 'buddyboss-platform' ); ?>
	</label>
	<input type="search" id="user_messages_search" placeholder="<?php esc_attr_e( 'Search&hellip;', 'buddyboss-platform' ); ?>"/>
	<button type="reset" id="user_messages_search_reset" class="bp-hide">
		<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss-platform' ); ?></span>
	</button>
</form>
