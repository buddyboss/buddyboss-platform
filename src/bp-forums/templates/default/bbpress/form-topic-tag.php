<?php

/**
 * Edit Topic Tag
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<?php if ( current_user_can( 'edit_topic_tags' ) ) : ?>

	<div id="edit-topic-tag-<?php bbp_topic_tag_id(); ?>" class="bbp-topic-tag-form">

		<fieldset class="bbp-form" id="bbp-edit-topic-tag">

			<legend><?php /* translators: %s: topic tag name. */ printf( __( 'Manage Tag: "%s"', 'buddyboss-platform' ), bbp_get_topic_tag_name() ); ?></legend>

			<fieldset class="bbp-form" id="tag-rename">

				<legend><?php _e( 'Rename', 'buddyboss-platform' ); ?></legend>

				<div class="bp-feedback info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php _e( 'Leave the slug empty to have one automatically generated.', 'buddyboss-platform' ); ?></p>
				</div>

				<div class="bp-feedback info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php _e( 'Changing the slug affects its permalink. Any links to the old slug will stop working.', 'buddyboss-platform' ); ?></p>
				</div>

				<form id="rename_tag" name="rename_tag" method="post" action="<?php the_permalink(); ?>">

					<div>
						<label for="tag-name"><?php _e( 'Name:', 'buddyboss-platform' ); ?></label>
						<input type="text" id="tag-name" name="tag-name" size="20" maxlength="40" tabindex="<?php bbp_tab_index(); ?>" value="<?php echo esc_attr( bbp_get_topic_tag_name() ); ?>" />
					</div>

					<div>
						<label for="tag-slug"><?php _e( 'Slug:', 'buddyboss-platform' ); ?></label>
						<input type="text" id="tag-slug" name="tag-slug" size="20" maxlength="40" tabindex="<?php bbp_tab_index(); ?>" value="<?php echo esc_attr( apply_filters( 'editable_slug', bbp_get_topic_tag_slug() ) ); ?>" />
					</div>

					<div class="bbp-submit-wrapper">
						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="button submit"><?php esc_attr_e( 'Update', 'buddyboss-platform' ); ?></button>

						<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
						<input type="hidden" name="action" value="bbp-update-topic-tag" />

						<?php wp_nonce_field( 'update-tag_' . bbp_get_topic_tag_id() ); ?>

					</div>
				</form>

			</fieldset>

			<fieldset class="bbp-form" id="tag-merge">

				<legend><?php _e( 'Merge', 'buddyboss-platform' ); ?></legend>

				<div class="bp-feedback info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php _e( 'Merging tags together cannot be undone.', 'buddyboss-platform' ); ?></p>
				</div>

				<form id="merge_tag" name="merge_tag" method="post" action="<?php the_permalink(); ?>">

					<div>
						<label for="tag-existing-name"><?php _e( 'Existing tag:', 'buddyboss-platform' ); ?></label>
						<input type="text" id="tag-existing-name" name="tag-existing-name" size="22" tabindex="<?php bbp_tab_index(); ?>" maxlength="40" />
					</div>

					<div class="bbp-submit-wrapper">
						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="button submit" onclick="return confirm('<?php echo esc_js( /* translators: %s: topic tag name. */ sprintf( __( 'Are you sure you want to merge the "%s" tag into the tag you specified?', 'buddyboss-platform' ), bbp_get_topic_tag_name() ) ); ?>');"><?php esc_attr_e( 'Merge', 'buddyboss-platform' ); ?></button>

						<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
						<input type="hidden" name="action" value="bbp-merge-topic-tag" />

						<?php wp_nonce_field( 'merge-tag_' . bbp_get_topic_tag_id() ); ?>
					</div>
				</form>

			</fieldset>

			<?php if ( current_user_can( 'delete_topic_tags' ) ) : ?>

				<fieldset class="bbp-form" id="delete-tag">

					<legend><?php _e( 'Delete', 'buddyboss-platform' ); ?></legend>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php _e( 'This does not delete your discussions. Only the tag itself is deleted.', 'buddyboss-platform' ); ?></p>
					</div>
					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php _e( 'Deleting a tag cannot be undone.', 'buddyboss-platform' ); ?><br />
						<?php _e( 'Any links to this tag will no longer function.', 'buddyboss-platform' ); ?></p>
					</div>

					<form id="delete_tag" name="delete_tag" method="post" action="<?php the_permalink(); ?>">

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" class="button submit" onclick="return confirm('<?php echo esc_js( /* translators: %s: topic tag name. */ sprintf( __( 'Are you sure you want to delete the "%s" tag? This is permanent and cannot be undone.', 'buddyboss-platform' ), bbp_get_topic_tag_name() ) ); ?>');"><?php esc_attr_e( 'Delete', 'buddyboss-platform' ); ?></button>

							<input type="hidden" name="tag-id" value="<?php bbp_topic_tag_id(); ?>" />
							<input type="hidden" name="action" value="bbp-delete-topic-tag" />

							<?php wp_nonce_field( 'delete-tag_' . bbp_get_topic_tag_id() ); ?>
						</div>
					</form>

				</fieldset>

			<?php endif; ?>

		</fieldset>
	</div>

<?php endif; ?>
