
<input type="hidden" name="bp-ld-sync-enable" value="0" />
<label>
	<input
		type="checkbox"
		name="bp-ld-sync-enable"
		value="1"
		autocomplete="off"
		<?php checked( $checked, true ); ?>
	/>
	<?php _e( 'Yes, I want this group to have a social group', 'buddyboss' ); ?>
</label>

<p>
	<b><?php _e( 'Associated Group:', 'buddyboss' ); ?></b>

	<select name="bp-ld-sync-id" style="width: 100%; margin-top: 10px;" autocomplete="off">
		<option value="0"><?php _e( 'None', 'buddyboss' ); ?></option>
		<?php foreach ( $availableBpGroups as $group ) : ?>
			<?php $selected = $group->id == $bpGroupId ? 'selected' : ''; ?>
			<option value="<?php echo $group->id; ?>" <?php echo $selected; ?>>
				<?php echo $group->name; ?> (ID: <?php echo $group->id; ?>)
			</option>
		<?php endforeach; ?>
	</select>

	<?php if ( $hasBpGroup ) : ?>
		<a href="<?php echo bp_get_admin_url( "admin.php?page=bp-groups&gid={$bpGroupId}&action=edit" ); ?>" target="_blank">
			<?php _e( 'edit group', 'buddyboss' ); ?>
		</a>
	<?php endif; ?>
</p>
