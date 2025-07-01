<?php
/**
 * Edit Topic Tag Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( current_user_can( 'edit_topic_tags' ) ) : ?>

	<div id="edit-topic-tag-<?php bbp_topic_tag_id(); ?>" class="bbp-topic-tag-form bb-rl-forum-modal bb-rl-forum-modal--static">

		<fieldset class="bbp-form bb-rl-forum-form" id="bbp-edit-topic-tag">

			<div class="bb-rl-forum-modal-header">
				<h3><?php printf( esc_html__( 'Manage Tag: "%s"', 'buddyboss' ), bbp_get_topic_tag_name() ); ?></h3>
			</div>

			<div class="bb-rl-forum-modal-content">

				<fieldset class="bbp-form" id="tag-rename">

					<legend><?php esc_html_e( 'Rename', 'buddyboss' ); ?></legend>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Leave the slug empty to have one automatically generated.', 'buddyboss' ); ?></p>
					</div>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Changing the slug affects its permalink. Any links to the old slug will stop working.', 'buddyboss' ); ?></p>
					</div>

					<form id="rename_tag" name="rename_tag" method="post" action="<?php the_permalink(); ?>">

						<div>
							<label for="tag-name"><?php esc_html_e( 'Name:', 'buddyboss' ); ?></label>
							<input type="text" id="tag-name" name="tag-name" size="20" maxlength="40" tabindex="<?php bbp_tab_index(); ?>" value="<?php echo esc_attr( bbp_get_topic_tag_name() ); ?>" />
						</div>

						<div>
							<label for="tag-slug"><?php esc_html_e( 'Slug:', 'buddyboss' ); ?></label>
							<input type="text" id="tag-slug" name="tag-slug" size="20" maxlength="40" tabindex="<?php bbp_tab_index(); ?>" value="<?php echo esc_attr( apply_filters( 'editable_slug', bbp_get_topic_tag_slug() ) ); ?>" />
						</div>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit"><?php esc_attr_e( 'Update', 'buddyboss' ); ?></button>

							<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
							<input type="hidden" name="action" value="bbp-update-topic-tag" />

							<?php wp_nonce_field( 'update-tag_' . bbp_get_topic_tag_id() ); ?>

						</div>
					</form>

				</fieldset>

				<fieldset class="bbp-form" id="tag-merge">

					<legend><?php esc_html_e( 'Merge', 'buddyboss' ); ?></legend>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Merging tags together cannot be undone.', 'buddyboss' ); ?></p>
					</div>

					<form id="merge_tag" name="merge_tag" method="post" action="<?php the_permalink(); ?>">

						<div>
							<label for="tag-existing-name"><?php esc_html_e( 'Existing tag:', 'buddyboss' ); ?></label>
							<input type="text" id="tag-existing-name" name="tag-existing-name" size="22" tabindex="<?php bbp_tab_index(); ?>" maxlength="40" />
						</div>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit" onclick="return confirm('<?php echo esc_js( sprintf( __( 'Are you sure you want to merge the "%s" tag into the tag you specified?', 'buddyboss' ), bbp_get_topic_tag_name() ) ); ?>');"><?php esc_attr_e( 'Merge', 'buddyboss' ); ?></button>

							<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
							<input type="hidden" name="action" value="bbp-merge-topic-tag" />

							<?php wp_nonce_field( 'merge-tag_' . bbp_get_topic_tag_id() ); ?>
						</div>
					</form>

				</fieldset>

				<?php if ( current_user_can( 'delete_topic_tags' ) ) : ?>

					<fieldset class="bbp-form" id="delete-tag">

						<legend><?php esc_html_e( 'Delete', 'buddyboss' ); ?></legend>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php esc_html_e( 'This does not delete your discussions. Only the tag itself is deleted.', 'buddyboss' ); ?></p>
						</div>
						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Deleting a tag cannot be undone.', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Any links to this tag will no longer function.', 'buddyboss' ); ?></p>
						</div>

						<form id="delete_tag" name="delete_tag" method="post" action="<?php the_permalink(); ?>">

							<div class="bbp-submit-wrapper">
								<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit" onclick="return confirm('<?php echo esc_js( sprintf( __( 'Are you sure you want to delete the "%s" tag? This is permanent and cannot be undone.', 'buddyboss' ), bbp_get_topic_tag_name() ) ); ?>');"><?php esc_attr_e( 'Delete', 'buddyboss' ); ?></button>

								<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
								<input type="hidden" name="action" value="bbp-delete-topic-tag" />

								<?php wp_nonce_field( 'delete-tag_' . bbp_get_topic_tag_id() ); ?>
							</div>
						</form>

					</fieldset>

				<?php endif; ?>

			</div>

		</fieldset>
	</div>

<?php endif; ?>
