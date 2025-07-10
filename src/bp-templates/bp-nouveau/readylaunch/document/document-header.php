<?php
/**
 * ReadyLaunch - Document Header template.
 *
 * This template handles displaying the document header for group and my profile.
 *
 * @since      BuddyBoss 2.9.00
 * @subpackage BP_Nouveau\ReadyLaunch
 * @package    BuddyBoss\Template
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

$is_group      = function_exists( 'bp_is_group' ) && bp_is_group();
$is_my_profile = function_exists( 'bp_is_my_profile' ) && bp_is_my_profile();

if (
	(
		$is_group ||
		$is_my_profile
	) &&
	bp_has_document( bp_ajax_querystring( 'document' ) )
) {
	?>
	<div class="bp-document-listing">
		<div class="bp-media-header-wrap bb-rl-documents-header-wrap">
			<div id="search-documents-form" class="media-search-form" data-bp-search="document">
				<form action="" method="get" class="bp-dir-search-form search-form-has-reset" id="group-document-search-form" autocomplete="off">
					<button type="submit" id="group-document-search-submit" class="nouveau-search-submit search-form_submit" name="group_document_search_submit">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
					</button>
					<label for="group-document-search" class="bp-screen-reader-text"><?php esc_html_e( 'Search documents', 'buddyboss' ); ?></label>
					<input id="group-document-search" name="document_search" type="search" placeholder="<?php esc_attr_e( 'Search documents', 'buddyboss' ); ?>">
					<button type="reset" class="search-form_reset">
						<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
						<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
					</button>
				</form>
			</div>

			<?php
			bp_get_template_part( 'document/add-folder' );
			bp_get_template_part( 'document/add-document' );
			?>

		</div>
	</div><!-- .bp-document-listing -->
	<?php
}
