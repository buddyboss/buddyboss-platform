<?php
/**
 * BP Nouveau Group's edit settings template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Select Group Settings', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Group Settings', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<div class="group-settings-selections">

	<fieldset class="radio group-status-type">
		<legend><?php esc_html_e( 'Privacy Options', 'buddyboss' ); ?></legend>

		<label for="group-status-public">
			<input type="radio" name="group-status" id="group-status-public" value="public"<?php if ( 'public' === bp_get_new_group_status() || ! bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="public-group-description" /> <?php esc_html_e( 'This is a public group', 'buddyboss' ); ?>
		</label>

		<ul id="public-group-description">
			<li><?php esc_html_e( 'Any site member can join this group.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'This group will be listed in the groups directory and in search results.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will be visible to any site member.', 'buddyboss' ); ?></li>
		</ul>

		<label for="group-status-private">
			<input type="radio" name="group-status" id="group-status-private" value="private"<?php if ( 'private' === bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="private-group-description" /> <?php esc_html_e( 'This is a private group', 'buddyboss' ); ?>
		</label>

		<ul id="private-group-description">
			<li><?php esc_html_e( 'Only people who request membership and are accepted can join the group.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'This group will be listed in the groups directory and in search results.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will only be visible to members of the group.', 'buddyboss' ); ?></li>
		</ul>

		<label for="group-status-hidden">
			<input type="radio" name="group-status" id="group-status-hidden" value="hidden"<?php if ( 'hidden' === bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="hidden-group-description" /> <?php esc_html_e( 'This is a hidden group', 'buddyboss' ); ?>
		</label>

		<ul id="hidden-group-description">
			<li><?php esc_html_e( 'Only people who are invited can join the group.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'This group will not be listed in the groups directory or search results.', 'buddyboss' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will only be visible to members of the group.', 'buddyboss' ); ?></li>
		</ul>

	</fieldset>

	<fieldset class="radio group-invitations">
		<legend><?php esc_html_e( 'Group Invitations', 'buddyboss' ); ?></legend>

		<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to invite others?', 'buddyboss' ); ?></p>

		<label for="group-invite-status-members">
			<input type="radio" name="group-invite-status" id="group-invite-status-members" value="members"<?php bp_group_show_invite_status_setting( 'members' ); ?> />
				<?php esc_html_e( 'All group members', 'buddyboss' ); ?>
		</label>

		<label for="group-invite-status-mods">
			<input type="radio" name="group-invite-status" id="group-invite-status-mods" value="mods"<?php bp_group_show_invite_status_setting( 'mods' ); ?> />
				<?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?>
		</label>

		<label for="group-invite-status-admins">
			<input type="radio" name="group-invite-status" id="group-invite-status-admins" value="admins"<?php bp_group_show_invite_status_setting( 'admins' ); ?> />
				<?php esc_html_e( 'Organizers only', 'buddyboss' ); ?>
		</label>

	</fieldset>

    <fieldset class="radio group-post-form">
        <legend><?php esc_html_e( 'Activity Feeds', 'buddyboss' ); ?></legend>

        <p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to post into the activity feed?', 'buddyboss' ); ?></p>

        <label for="group-activity-feed-status-members">
            <input type="radio" name="group-activity-feed-status" id="group-activity-feed-status-members" value="members"<?php bp_group_show_activity_feed_status_setting( 'members' ); ?> />
			<?php esc_html_e( 'All group members', 'buddyboss' ); ?>
        </label>

        <label for="group-activity-feed-status-mods">
            <input type="radio" name="group-activity-feed-status" id="group-activity-feed-status-mods" value="mods"<?php bp_group_show_activity_feed_status_setting( 'mods' ); ?> />
			<?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?>
        </label>

        <label for="group-activity-feed-status-admins">
            <input type="radio" name="group-activity-feed-status" id="group-activity-feed-status-admins" value="admins"<?php bp_group_show_activity_feed_status_setting( 'admins' ); ?> />
			<?php esc_html_e( 'Organizers only', 'buddyboss' ); ?>
        </label>

    </fieldset>

	<?php
	$group_types = bp_groups_get_group_types( array( 'show_in_create_screen' => true ), 'objects' );

	// Hide Group Types if none is selected in Users > Profile Type > E.g. (Students) > Allowed Group Types meta box.
	if ( false === bp_restrict_group_creation() && true === bp_member_type_enable_disable() ) {
		$get_all_registered_member_types = bp_get_active_member_types();
		if ( isset( $get_all_registered_member_types ) && !empty( $get_all_registered_member_types ) ) {

			$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );
			if ( '' !== $current_user_member_type ) {
				$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
				$include_group_type  = get_post_meta( $member_type_post_id,'_bp_member_type_enabled_group_type_create',true );
				if ( isset( $include_group_type ) && ! empty( $include_group_type ) && 'none' === $include_group_type[0] ) {
					$group_types = '';
				}
			}
		}
	}

	// Group type selection
	if ( $group_types ) : ?>

		<fieldset class="group-create-types">
			<legend><?php esc_html_e( 'Group Type', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'What type of group is this? (optional)', 'buddyboss' ); ?></p>
			<select id="bp-groups-type" name="group-types[]" autocomplete="off">
				<option value="" <?php selected( '', '' ); ?>><?php echo _x( '----', 'The option that sets a group type.', 'buddyboss' ); ?></option>
			<?php foreach ( $group_types as $type ) : ?>
				<?php
				if ( false === bp_restrict_group_creation() && true === bp_member_type_enable_disable() ) {

					$get_all_registered_member_types = bp_get_active_member_types();

					if ( isset( $get_all_registered_member_types ) && !empty( $get_all_registered_member_types ) ) {

						$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );

						if ( '' !== $current_user_member_type ) {

							$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
							$include_group_type = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true);

							if ( isset( $include_group_type ) && !empty( $include_group_type ) ) {
								if ( in_array( $type->name, $include_group_type ) ) {
									?>
									<option for="<?php printf( 'group-type-%s', $type->name ); ?>" value="<?php echo esc_attr( $type->name ) ?>" <?php selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) ) ? $type->name : '', $type->name ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
									<?php
								}
							} else {
								?>
								<option for="<?php printf( 'group-type-%s', $type->name ); ?>" value="<?php echo esc_attr( $type->name ) ?>" <?php selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) ) ? $type->name : '', $type->name ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
								<?php
							}

						} else {
							?>
							<option for="<?php printf( 'group-type-%s', $type->name ); ?>" value="<?php echo esc_attr( $type->name ) ?>" <?php selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) ) ? $type->name : '' , $type->name ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
							<?php
						}
					} else {
						?>
						<option for="<?php printf( 'group-type-%s', $type->name ); ?>" value="<?php echo esc_attr( $type->name ) ?>" <?php selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) ) ? $type->name : '' , $type->name ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
						<?php
					}
				} else {
					?>
					<option for="<?php printf( 'group-type-%s', $type->name ); ?>" value="<?php echo esc_attr( $type->name ) ?>" <?php selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) ) ? $type->name : '' , $type->name ); ?>><?php echo esc_html( $type->labels['singular_name'] ); ?></option>
					<?php
				}
				?>

			<?php endforeach; ?>
			</select>
		</fieldset>

	<?php endif; ?>

	<?php if ( bp_enable_group_hierarchies() ):
		$current_parent_group_id = bp_get_parent_group_id();
		$possible_parent_groups = bp_get_possible_parent_groups();
		?>

		<fieldset class="select group-parent">
			<legend><?php esc_html_e( 'Group Parent', 'buddyboss' ); ?></legend>
			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which group should be the parent of this group? (optional)', 'buddyboss' ); ?></p>
			<select id="bp-groups-parent" name="bp-groups-parent" autocomplete="off">
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php echo _x( '----', 'The option that sets a group to be a top-level group and have no parent.', 'buddyboss' ); ?></option>
				<?php
				if ( $possible_parent_groups ) {

					foreach ( $possible_parent_groups as $possible_parent_group ) {
						?>
						<option value="<?php echo $possible_parent_group->id; ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo esc_html( $possible_parent_group->name ); ?></option>
						<?php
					}
				}
				?>
			</select>
		</fieldset>
	<?php endif; ?>

</div><!-- // .group-settings-selections -->
