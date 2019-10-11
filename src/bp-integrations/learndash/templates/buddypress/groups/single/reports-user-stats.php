<?php
/**
 * Course Reports User Stats
 *
 * @package BuddyBoss\Integrations\LearnDash\Reports
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! groups_is_user_mod( bp_loggedin_user_id(), $group->id ) && ! groups_is_user_admin( bp_loggedin_user_id(), $group->id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
	if ( isset( $_GET ) && isset( $_GET['user'] ) && '' !== $_GET['user'] && (int) $_GET['user'] !== bp_loggedin_user_id() ) {
		?>
		<script>
			// Simulate an HTTP redirect:
			window.location.replace('<?php echo bp_get_group_permalink(); ?>');
		</script>
		<?php
	}
}
?>

<div class="ld-report-user-stats">
	<div class="user-info">
		<div class="user-avatar">
			<a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user->ID ) ); ?></a>
		</div>
		<div class="user-name">
			<h5 class="list-title member-name"><a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo bp_core_get_user_displayname( $user->ID ); ?></a></h5>
			<p class="item-meta"><?php echo groups_is_user_admin( $user->ID, $group->id ) ? __( 'Teacher', 'buddyboss' ) : __( 'Student', 'buddyboss' ); ?></p>
		</div>
	</div>

	<?php
	if ( $courseId && isset( $_GET ) && isset( $_GET['step'] ) && (int) $total > 0 ) {
		?>
		<div class="user-info">
			<div class="bp-ld-reports-progress-bar" data-percent="<?php echo $percentage; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
				$complete,
				$total,
				_n( 'step', 'steps', $total, 'buddyboss' )
			);
			?>
				</p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-courses' === $_GET['step'] && (int) $total > 0 && (int) $complete > 0 ) {
		?>
		<div class="user-info">
			<div class="bp-ld-reports-progress-bar" data-percent="<?php echo $percentage; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
				$complete,
				$total,
				_n( LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'courses' ), $total, 'buddyboss' )
			);
			?>
				</p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-lessons' === $_GET['step'] && (int) $total > 0 && (int) $complete > 0 ) {
		$data = bp_get_user_course_lesson_data( $_GET['course'], $user->ID );
		?>
		<div class="user-info">
			<div class="bp-ld-reports-progress-bar" data-percent="<?php echo $percentage; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
				$complete,
				$total,
				_n( LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'lessons' ), $total, 'buddyboss' )
			);
			?>
				</p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-topic' === $_GET['step'] && (int) $total > 0 && (int) $complete > 0 ) {
		$data = bp_get_user_course_lesson_data( $_GET['course'], $user->ID );
		?>
		<div class="user-info">
			<div class="bp-ld-reports-progress-bar" data-percent="<?php echo $percentage; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
				$complete,
				$total,
				_n( LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'topics' ), $total, 'buddyboss' )
			);
			?>
				</p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-quiz' === $_GET['step'] && (int) $total > 0 && (int) $complete > 0 ) {
		?>
		<div class="user-info">
			<div class="bp-ld-reports-progress-bar" data-percent="<?php echo $percentage; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
				$complete,
				$total,
				_n( LearnDash_Custom_Label::get_label( 'quiz' ), LearnDash_Custom_Label::get_label( 'quizzes' ), $total, 'buddyboss' )
			);
			?>
				</p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-assignment' === $_GET['step'] && (int) $total > 0 && (int) $complete > 0 ) {
		?>
		<div class="user-steps">
			<p>
			<?php
			printf(
				__( '<b>%1$d</b> %2$s Marked', 'buddyboss' ),
				$total,
				_n( 'Assignment', 'Assignments', $total, 'buddyboss' )
			);
			?>
				</p>
			<p>
			<?php
			if ( $unmarked > 0 ) {
				printf(
					__( '<b>%1$d</b> %2$s Unmarked', 'buddyboss' ),
					$unmarked,
					_n( 'Assignment', 'Assignments', $unmarked, 'buddyboss' )
				);
			}
			?>
				</p>
		</div>
		<?php
	}


	if ( $points ) :
		?>
		<div class="user-points">
			<p>
			<?php
			printf(
				__( '<b>%1$d</b> %2$s earned', 'buddyboss' ),
				$points,
				_n( 'point', 'points', $points, 'buddyboss' )
			);
			?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'messages' ) && $user->ID != bp_loggedin_user_id() ) : ?>
		<div class="user-message">
			<?php
				$link = apply_filters(
					'bp_get_send_private_message_link',
					wp_nonce_url(
						bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_activity_get_user_mentionname( $user->ID )
					)
				);

				echo bp_get_send_message_button(
					array(
						'link_href' => $link,
						'link_text' => __( 'Message', 'buddyboss' ),
					)
				);
			?>
		</div>
	<?php endif; ?>
</div>
<?php
	/**
	* @todo Should we be labeling Teacher and Student here?
	*/
if ( $courseId ) :
	$course = get_post( $courseId );
	?>
	<h3 class="ld-report-course-name"><?php echo $course->post_title; ?></h3>
<?php endif; ?>
