<?php
/**
 * ReadyLaunch - Member Sent Invites template.
 *
 * This template handles displaying sent member invitations.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'invites_sent_template' );

$email = trim( bb_filter_input_string( INPUT_GET, 'email' ) );
if ( isset( $email ) && '' !== $email ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice success">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$text = __( 'Invitations were sent successfully to the following email addresses:', 'buddyboss' );
			echo esc_html( trim( $text . ' ' . $email ) );
			?>
		</p>
	</aside>
	<?php
}

$failed = trim( bb_filter_input_string( INPUT_GET, 'failed' ) );
if ( isset( $failed ) && '' !== $failed ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$text = __( 'Invitations did not send because these email addresses are invalid:', 'buddyboss' );
			echo esc_html( trim( $text . ' ' . $failed ) );
			?>
		</p>

	</aside>
	<?php
}

$exists = trim( bb_filter_input_string( INPUT_GET, 'exists' ) );
if ( isset( $exists ) && '' !== $exists ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$text = __( 'Invitations did not send to the following email addresses, because they are already members:', 'buddyboss' );
			echo esc_html( trim( $text . ' ' . $exists ) );
			?>
		</p>

	</aside>
	<?php
}

$duplicates = trim( bb_filter_input_string( INPUT_GET, 'duplicates' ) );
if ( isset( $duplicates ) && '' !== $duplicates ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$text = __( 'Invitations did not send to the following email addresses, because they are already invited:', 'buddyboss' );
			echo esc_html( trim( $text . ' ' . $duplicates ) );
			?>
		</p>

	</aside>
	<?php
}

$restricted = trim( bb_filter_input_string( INPUT_GET, 'restricted' ) );
if ( isset( $restricted ) && '' !== $restricted ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$text = __( 'Invitations did not send to the following email addresses, because the address or domain has been blacklisted:', 'buddyboss' );
			echo esc_html( trim( $text . ' ' . $restricted ) );
			?>
		</p>

	</aside>
	<?php
}
?>
<script>window.history.replaceState(null, null, window.location.pathname);</script>
<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Sent Invites', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'You have sent invitation emails to the following people:', 'buddyboss' ); ?>
</p>

<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
	<thead>
	<tr>
		<th class="title"><?php esc_html_e( 'Name', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Email', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Invited', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Status', 'buddyboss' ); ?></th>
	</tr>
	</thead>

	<tbody>

	<?php
	$paged                         = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$sent_invites_pagination_count = apply_filters( 'sent_invites_pagination_count', 25 );
	$args                          = array(
		'posts_per_page' => $sent_invites_pagination_count,
		'post_type'      => bp_get_invite_post_type(),
		'author'         => bp_loggedin_user_id(),
		'paged'          => $paged,
	);
	$the_query                     = new WP_Query( $args );

	if ( $the_query->have_posts() ) {

		while ( $the_query->have_posts() ) :
			$the_query->the_post();

			$post_id = get_the_ID();
			?>
			<tr>
				<td class="field-name">
					<span><?php echo get_post_meta( $post_id, '_bp_invitee_name', true ); ?></span>
				</td>
				<td class="field-email">
					<span><?php echo get_post_meta( $post_id, '_bp_invitee_email', true ); ?></span>
				</td>
				<td class="field-email">
					<span>
						<?php
						$date = get_the_date( '', $post_id );
						echo $date;
						?>
					</span>
				</td>
				<td class="field-email">
					<?php

					$allow_custom_registration = bp_allow_custom_registration();
					$bp_invitee_status         = get_post_meta( $post_id, '_bp_invitee_status', true );

					if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
						$class       = ( '1' === $bp_invitee_status ) ? 'registered' : 'invited';
						$revoke_link = '';
						$title       = ( '1' === $bp_invitee_status ) ? __( 'Registered', 'buddyboss' ) : __( 'Invited', 'buddyboss' );
					} else {
						$class       = ( '1' === $bp_invitee_status ) ? 'registered' : 'revoked-access';
						$revoke_link = bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_invites_slug() . '/revoke-invite';
						$title       = ( '1' === $bp_invitee_status ) ? __( 'Registered', 'buddyboss' ) : __( 'Revoke Invite', 'buddyboss' );
					}
					$alert_message = ( '1' === $bp_invitee_status ) ? __( 'Registered', 'buddyboss' ) : __( 'Are you sure you want to revoke this invitation?', 'buddyboss' );
					$icon          = ( '1' === $bp_invitee_status ) ? 'check' : 'x-circle';

					if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
						?>
						<span class="bp-invitee-status">
							<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
							<?php echo $title; ?>
						</span>
						<?php
					} elseif ( 'registered' === $class ) {

						?>
							<span class="bp-invitee-status">
								<span class="bb-icons-rl-<?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?>
							</span>
							<?php
					} else {
						?>
							<span class="bp-invitee-status">
								<a data-revoke-access="<?php echo esc_url( $revoke_link ); ?>" data-name="<?php echo esc_attr( $alert_message ); ?>" id="<?php echo esc_attr( $post_id ); ?>" class="<?php echo esc_attr( $class ); ?>" href="javascript:void(0);">
									<span class="bb-icons-rl-<?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?>
								</a>
							</span>
							<?php

					}
					?>
				</td>
			</tr>
			<?php
		endwhile;

	} else {
		?>
		<tr>
			<td colspan="4" class="field-name">
				<span><?php esc_html_e( 'You haven\'t sent any invitations yet.', 'buddyboss' ); ?></span>
			</td>
		</tr>
		<?php
	}

	$total_pages = $the_query->max_num_pages;
	if ( $total_pages > 1 ) {
		?>
		<tr>
			<td colspan="4" class="field-name bp-pagination">
				<div class="bp-pagination-links bottom">
					<p class="pag-data">
						<?php
							$current_page = max( 1, get_query_var( 'paged' ) );
							echo paginate_links(
								array(
									'base'      => get_pagenum_link( 1 ) . '%_%',
									'format'    => '?paged=%#%',
									'current'   => $current_page,
									'total'     => $total_pages,
									'prev_text' => __( '« Prev', 'buddyboss' ),
									'next_text' => __( 'Next »', 'buddyboss' ),
								)
							);
						?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	wp_reset_postdata();
	?>

	</tbody>
</table>

<?php
bp_nouveau_member_hook( 'after', 'invites_sent_template' );
