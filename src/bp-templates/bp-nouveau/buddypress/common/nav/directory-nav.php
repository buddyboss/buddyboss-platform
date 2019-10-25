<?php
/**
 * BP Nouveau Component's directory nav template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<?php
	$bp_nouveau = bp_nouveau();
	$has_nav    = bp_nouveau_has_nav( array( 'object' => 'directory' ) );
	$nav_count  = count( $bp_nouveau->sorted_nav );

if ( ! $has_nav || $nav_count <= 1 ) {
	unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
	return;
}
?>

<nav class="<?php bp_nouveau_directory_type_navs_class(); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Directory menu', 'buddyboss' ); ?>">

	<ul class="component-navigation <?php bp_nouveau_directory_list_class(); ?>">

		<?php
		while ( bp_nouveau_nav_items() ) :
			bp_nouveau_nav_item();

			if ( 'members' === bp_current_component() ) {

				if ( true === bp_member_type_enable_disable() ) {

					$bp_get_scope = bp_nouveau_get_nav_id();
					$bp_get_scope = explode( 'members-', $bp_get_scope );
					$bp_get_scope = $bp_get_scope[1];
					$member_types = bp_get_active_member_types();

					//$count = esc_html( number_format_i18n( bp_nouveau_get_nav_count() ) );
					$count = bp_nouveau_get_nav_count();

					if ( 'personal' === $bp_get_scope ) {
						$personal_friend_comma_separated_string = bp_get_friend_ids( bp_loggedin_user_id() );
						$friend_array                           = explode(
							',',
							$personal_friend_comma_separated_string
						);

						foreach ( $member_types as $member_type_id ) {

							if ( ! get_post_meta( $member_type_id, '_bp_member_type_enable_remove', true ) ) {
								continue;
							}

							$name    = bp_get_member_type_key( $member_type_id );
							$type_id = bp_member_type_term_taxonomy_id( $name );

							$exclude_user = bp_member_type_by_type( $type_id );

							foreach ( $exclude_user as $exclude ) {
								if ( in_array( $exclude, $friend_array ) ) {
									$count = count( array_diff( $friend_array, $exclude_user ) );
								}
							}
						}
					} elseif ( 'following' === $bp_get_scope ) {

						$args                             = array(
							'user_id' => bp_loggedin_user_id(),
						);
						$following_comma_separated_string = bp_get_following_ids( $args );
						$following_array                  = explode( ',', $following_comma_separated_string );

						foreach ( $member_types as $member_type_id ) {

							if ( ! get_post_meta( $member_type_id, '_bp_member_type_enable_remove', true ) ) {
								continue;
							}

							$name         = bp_get_member_type_key( $member_type_id );
							$type_id      = bp_member_type_term_taxonomy_id( $name );
							$exclude_user = bp_member_type_by_type( $type_id );

							foreach ( $exclude_user as $exclude ) {
								if ( in_array( $exclude, $following_array ) ) {
									$count = count( array_diff( $following_array, $exclude_user ) );
								}
							}
						}
					} else {
						$member_types = bp_get_active_member_types();
						foreach ( $member_types as $member_type_id ) {
							if ( ! get_post_meta( $member_type_id, '_bp_member_type_enable_remove', true ) ) {
								continue;
							}
							$name               = bp_get_member_type_key( $member_type_id );
							$type_id            = bp_member_type_term_taxonomy_id( $name );
							$exclude_user_list  = bp_member_type_by_type( $type_id );
							$final_exclude_list = array();
							foreach ( $exclude_user_list as $user ) {
								$last_activity = bp_get_user_last_activity( $user );
								if ( '' !== $last_activity ) {
									$final_exclude_list[] = $user;
								}
							}
							$count = $count - count( $final_exclude_list );
						}
					}
				} else {
					$count = bp_nouveau_get_nav_count();
				}
			} else {

				if ( 'groups' === bp_current_component() ) {

					if ( true === bp_disable_group_type_creation() ) {

						$bp_get_scope = bp_nouveau_get_nav_id();
						$bp_get_scope = explode( 'groups-', $bp_get_scope );
						$bp_get_scope = $bp_get_scope[1];
						$group_types  = bp_get_active_group_types();

						//$count = esc_html( number_format_i18n( bp_nouveau_get_nav_count() ) );
						$count = bp_nouveau_get_nav_count();

						if ( 'personal' === $bp_get_scope ) {
							$personal_group_ids = groups_get_user_groups( bp_loggedin_user_id() );
							$group_array        = $personal_group_ids['groups'];

							foreach ( $group_types as $group_type_id ) {

								if ( ! get_post_meta( $group_type_id, '_bp_group_type_enable_remove', true ) ) {
									continue;
								}

								$exclude_group_ids = array_unique( bp_get_groups_of_removed_group_types() );

								foreach ( $exclude_group_ids as $exclude ) {
									if ( in_array( $exclude, $group_array ) ) {
										$count = count( array_diff( $group_array, $exclude_group_ids ) );
									}
								}
							}
						} else {

							$exclude_group_ids = array_unique( bp_get_groups_of_removed_group_types() );
							$count             = $count - count( $exclude_group_ids );

						}
					} else {
						$count = bp_nouveau_get_nav_count();
					}
				} else {
					$count = bp_nouveau_get_nav_count();
				}
			}
			?>

			<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
				<a href="<?php bp_nouveau_nav_link(); ?>">
					<?php bp_nouveau_nav_link_text(); ?>

					<?php if ( bp_nouveau_nav_has_count() ) : ?>
						<span class="count"><?php echo esc_html( number_format_i18n( max( $count, 0 ) ), 'buddyboss' ); ?></span>
					<?php endif; ?>
				</a>
			</li>

		<?php endwhile; ?>

	</ul><!-- .component-navigation -->

</nav><!-- .bp-navs -->
