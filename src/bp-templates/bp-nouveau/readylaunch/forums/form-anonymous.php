<?php
/**
 * Anonymous User Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( bbp_current_user_can_access_anonymous_user_form() ) : ?>

	<?php do_action( 'bbp_theme_before_anonymous_form' ); ?>

	<fieldset class="bbp-form bb-rl-forum-anonymous-form">
		<legend><?php ( bbp_is_topic_edit() || bbp_is_reply_edit() ) ? esc_html_e( 'Author Information', 'buddyboss' ) : esc_html_e( 'Your information:', 'buddyboss' ); ?></legend>

		<?php do_action( 'bbp_theme_anonymous_form_extras_top' ); ?>

		<p>
			<label for="bbp_anonymous_author"><?php esc_html_e( 'Name (required):', 'buddyboss' ); ?></label><br />
			<input type="text" id="bbp_anonymous_author"  value="<?php bbp_author_display_name(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_anonymous_name" />
		</p>

		<p>
			<label for="bbp_anonymous_email"><?php esc_html_e( 'Email (will not be published) (required):', 'buddyboss' ); ?></label><br />
			<input type="text" id="bbp_anonymous_email"   value="<?php bbp_author_email(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_anonymous_email" />
		</p>

		<p>
			<label for="bbp_anonymous_website"><?php esc_html_e( 'Website:', 'buddyboss' ); ?></label><br />
			<input type="text" id="bbp_anonymous_website" value="<?php bbp_author_url(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_anonymous_website" />
		</p>

		<?php do_action( 'bbp_theme_anonymous_form_extras_bottom' ); ?>

	</fieldset>

	<?php do_action( 'bbp_theme_after_anonymous_form' ); ?>

<?php endif; ?>
