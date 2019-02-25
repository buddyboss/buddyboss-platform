<?php
/**
 * BuddyPress Media - Users Photos
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php //do_action( 'template_notices' ); ?>

<h1 class="entry-title"><?php _e( 'Create an Album', 'buddyboss-media' );?>
	<?php
	global $bp;
	$albums_url = $bp->displayed_user->domain . buddyboss_media_component_slug() . '/albums/';
	?>
</h1>

<div id="buddypress">
	<form method="POST" id="buddyboss-media-album-create-form" class="standard-form">
		<?php wp_nonce_field( 'buddyboss_media_edit_album' );?>

		<div>
			<label for="album_title"><?php _e( 'Title (required)', 'buddyboss-media' );?></label>
			<input type="text" name="album_title" value="<?php if( isset( $_POST['album_title'] ) ){ echo esc_attr($_POST['album_title']); }?>">
		</div>

		<div>
			<label for="album_description"><?php _e( 'Description', 'buddyboss-media' );?></label>
			<textarea name="album_description"><?php if( isset( $_POST['album_description'] ) ){ echo esc_attr($_POST['album_description']); }?></textarea>
		</div>

		<?php if ( function_exists( 'buddyboss_wall' ) ): ?>
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

			$selected_val = isset( $_POST['album_visibility'] ) ? $_POST['album_visibility'] : '';

			foreach( $options as $key=>$val ){
				$selected = $selected_val == $key ? ' selected' : '';
				echo "<option value='" . esc_attr( $key ) . "' $selected>$val</option>";
			}
			?>
			</select>
		</div>
		<?php endif; ?>

		<div class="submit">
            <input type="submit" name="btn_submit" value="<?php esc_attr_e( 'Create Album', 'buddyboss-media' );?>">
		</div>

	</form>
</div>