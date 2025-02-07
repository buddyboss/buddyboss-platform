<?php
/**
 * The template for members settings ( Export Data )
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */
?>

<h2 class="screen-heading data-export-screen">
	<?php esc_html_e( 'Request an export of your data', 'buddyboss' ); ?>
</h2>

<?php bp_nouveau_user_feedback( 'member-data-export' ); ?>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/export/' ); ?>" name="account-data-export-form" id="account-data-export-form" class="standard-form" method="post">
	<?php bp_nouveau_submit_button( 'member-data-export' ); ?>
</form>
