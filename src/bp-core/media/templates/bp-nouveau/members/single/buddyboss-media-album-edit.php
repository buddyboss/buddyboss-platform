<?php
/**
 * BuddyPress Media - Users Photos
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php //do_action( 'template_notices' ); ?>

<?php if ( buddyboss_media_has_albums( ) ) : ?>
	<?php while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
		<h2 class="entry-title"><?php _e( 'Edit Album', 'buddyboss-media' );?>
			<?php buddyboss_media_btn_delete_album(); ?>
		</h2>

		<form method="POST" id="buddyboss-media-album-edit-form" class="standard-form">
			<?php wp_nonce_field( 'buddyboss_media_edit_album' );?>

			<input type="hidden" name="hdn_album_id" value="<?php buddyboss_media_album_id(); ?>" >

			<div>
				<label for="album_title"><?php _e( 'Title (required)', 'buddyboss-media' );?></label>
				<input type="text" name="album_title" value="<?php echo esc_attr( buddyboss_media_album_get_title() );?>">
			</div>

			<div>
				<label for="album_description"><?php _e( 'Description', 'buddyboss-media' );?></label>
				<textarea name="album_description"><?php buddyboss_media_album_description();?></textarea>
			</div>

			<div>
				<label for="album_privacy"><?php _e( 'Visibility (required)', 'buddyboss-media' );?></label>
				<select name="album_privacy">
				<?php
				$options = array(
					'public'	=> __('Everyone', 'buddyboss-media'),
					'private'	=> __('Only Me', 'buddyboss-media'),
					'members'	=> __('Logged In Users', 'buddyboss-media'),
				);

				if( bp_is_active( 'friends' ) ){
					$options['friends'] = __('My Friends', 'buddyboss-media');
				}

				$selected_option = buddyboss_media_album_get_privacy();
				foreach( $options as $key=>$val ){
					$selected = $selected_option==$key ? ' selected' : '';
					echo "<option value='" . esc_attr( $key ) . "' $selected >$val</option>";
				}
				?>
				</select>
			</div>

			<div class="submit">
				<input type="submit" name="btn_submit" value="<?php esc_attr_e( 'Save', 'buddyboss-media' );?>">
			</div>

		</form>


	<?php endwhile;?>

<?php else: ?>
	<div id="message" class="info">
		<p><?php _e( 'There were no albums found.', 'buddyboss-media' ); ?></p>
	</div>
<?php endif; ?>
