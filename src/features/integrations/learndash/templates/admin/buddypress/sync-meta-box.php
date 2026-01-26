

<input type="hidden" name="bp-ld-sync-enable" value="0" />
<label>
	<input
		type="checkbox"
		name="bp-ld-sync-enable"
		value="1"
		autocomplete="off"
		<?php checked( $hasLdGroup, true ); ?>
	/>
	<?php _e( 'Yes, I want this group to have a LearnDash group.', 'buddyboss' ); ?>
</label>

<p>
	<b><?php _e( 'Associated Group:', 'buddyboss' ); ?></b>

	<select name="bp-ld-sync-id" style="width: 100%; margin-top: 10px;" autocomplete="off">
		<option value="0"><?php _e( 'None', 'buddyboss' ); ?></option>
		<?php foreach ( $availableLdGroups as $group ) : ?>
			<?php $selected = $group->ID == $ldGroupId ? 'selected' : ''; ?>
			<option value="<?php echo $group->ID; ?>" <?php echo $selected; ?>>
				<?php echo $group->post_title; ?> (ID: <?php echo $group->ID; ?>)
			</option>
		<?php endforeach; ?>
	</select>

	<?php if ( $hasLdGroup ) : ?>
		<a href="<?php echo get_edit_post_link( $ldGroupId ); ?>" target="_blank">
			<?php _e( 'Edit Group', 'buddyboss' ); ?>
		</a>
	<?php endif; ?>
</p>
