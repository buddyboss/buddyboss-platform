<?php
/**
 * ReadyLaunch - Search Loop Message template.
 *
 * The template for search results for messages.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $current_message; ?>
<li class="bp-search-item bp-search-item_message">
	<p class="message_participants">
		<?php
		esc_html_e( 'Conversation between', 'buddyboss' );
		$participants = array();
		foreach ( $current_message->recepients as $recepient_id ) {
			if ( (int) $recepient_id === get_current_user_id() ) {
				continue;
			}

			$participants[] = bp_core_get_userlink( $recepient_id );
		}

		echo ' ' . implode( ', ', $participants ) . ' ' . __( 'and you.', 'buddyboss' );
		?>

		<span class='view_thread_link'>
			<a href='<?php echo esc_url( trailingslashit( bp_loggedin_user_domain() ) . 'messages/view/' . $current_message->thread_id . '/' ); ?>'>
				<?php esc_html_e( 'View Conversation', 'buddyboss' ); ?>
			</a>
		</span>
	</p>

	<div class="conversation">
		<div class="item-avatar">
			<a href="<?php echo esc_url( bp_core_get_userlink( $current_message->sender_id, true, true ) ); ?>" data-bb-hp-profile="<?php echo esc_attr( $current_message->sender_id ); ?>">
				<?php
				echo bp_core_fetch_avatar(
					array(
						'item_id' => $current_message->sender_id,
						'width'   => 50,
						'height'  => 50,
					)
				);
				?>
			</a>
		</div>

		<div class="item">
			<div class="item-title">
				<a href="<?php echo esc_url( trailingslashit( bp_loggedin_user_domain() ) . 'messages/view/' . $current_message->thread_id . '/' ); ?>">
					<?php echo wp_kses_post( stripslashes( $current_message->subject ) ); ?>
				</a>
			</div>
			<div class="item-desc">
				<?php
					$content         = wp_strip_all_tags( $current_message->message );
					$trimmed_content = wp_trim_words( $content, 20, '&hellip;' );
					echo wp_kses_post( $trimmed_content );
				?>
			</div>
		</div>
	</div>

</li>
