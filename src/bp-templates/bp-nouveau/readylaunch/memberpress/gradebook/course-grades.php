<?php
/**
 * Template for displaying course grades in MemberPress Courses integration.
 *
 * @package BuddyBoss\MemberpressLMS
 *
 * @since 2.9.00
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! empty( $quizzes ) ) : ?>
	<div id="downloads" class="mpcs-section mpcs-resource-section">
		<div class="mpcs-section-header-static">
			<div class="mpcs-section-title">
				<span class="mpcs-section-title-text"><?php echo esc_html__( 'Quizzes', 'buddyboss' ); ?></span>
			</div>
		</div> <!-- mpcs-section-header -->
		<div class="mpcs-lessons" style="display: block;">

			<table class="mp-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Grade', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Bonus', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Date', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Action', 'buddyboss' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $quizzes as $row ) {
						?>
						<tr class="mpcs-lesson mpcs-gradebook-lesson">
							<td><a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link"><?php echo esc_html( $row->title ); ?></a></td>
							<td><?php echo esc_html( $row->score ); ?></td>
							<td><?php echo esc_html( $row->bonus ); ?></td>
							<td><?php echo esc_html( $row->date ); ?></td>
							<td>
								<div class="flex">
									<?php if ( $row->allow_retakes ) { ?>
										<a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link" target="_blank" aria-label="<?php esc_attr_e( 'Retake', 'buddyboss' ); ?>">
											<div class="mpcs-lesson-button">
												<span title="<?php esc_html_e( 'Retake', 'buddyboss' ); ?>"><i class="bb-icons-rl-arrow-counter-clockwise"></i></span>
											</div>
										</a>
									<?php } ?>
									<?php if ( ! empty( $row->feedback ) ) { ?>
										<a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link mpcs-lesson-button-feedback" target="_blank" aria-label="<?php esc_attr_e( 'Feedback', 'buddyboss' ); ?>">
											<div class="mpcs-lesson-button">
												<span title="<?php echo esc_attr( esc_html( $row->feedback ) ); ?>">
													<i class="bb-icons-rl-chat-teardrop-text"></i>
												</span>
											</div>
										</a>
									<?php } ?>
								</div>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

		</div> <!-- mpcs-lessons -->
	</div>
<?php endif; ?>

<?php if ( ! empty( $assignments ) ) : ?>
	<div id="links" class="mpcs-section mpcs-resource-section">
		<div class="mpcs-section-header-static">
			<div class="mpcs-section-title">
				<span class="mpcs-section-title-text"><?php echo esc_html__( 'Assignments', 'buddyboss' ); ?></span>
			</div>
		</div> <!-- mpcs-section-header -->
		<div class="mpcs-lessons" style="display: block;">
			<table class="mp-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Grade', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Bonus', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Date', 'buddyboss' ); ?></th>
						<th><?php esc_html_e( 'Action', 'buddyboss' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $assignments as $row ) {
						?>
						<tr class="mpcs-lesson mpcs-gradebook-lesson">
							<td><a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link"><?php echo esc_html( $row->title ); ?></a></td>
							<td><?php echo esc_html( $row->score ); ?></td>
							<td><?php echo esc_html( $row->bonus ); ?></td>
							<td><?php echo esc_html( $row->date ); ?></td>
							<td>
								<div class="flex">
									<?php if ( $row->allow_retakes ) { ?>
										<a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link" target="_blank" aria-label="<?php esc_attr_e( 'Retake', 'buddyboss' ); ?>">
											<div class="mpcs-lesson-button">
												<span title="<?php esc_html_e( 'Retake', 'buddyboss' ); ?>"><i class="bb-icons-rl-arrow-counter-clockwise"></i></span>
											</div>
										</a>
									<?php } ?>
									<?php if ( ! empty( $row->feedback ) ) { ?>
										<a href="<?php echo esc_url( $row->url ); ?>" class="mpcs-lesson-row-link mpcs-lesson-button-feedback" target="_blank" aria-label="<?php esc_attr_e( 'Feedback', 'buddyboss' ); ?>">
											<div class="mpcs-lesson-button">
												<span title="<?php echo esc_attr( esc_html( $row->feedback ) ); ?>">
													<i class="bb-icons-rl-chat-teardrop-text"></i>
												</span>
											</div>
										</a>
									<?php } ?>
								</div>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

		</div> <!-- mpcs-lessons -->
	</div>
<?php endif; ?>

<?php if ( ! empty( $resources->custom ) && ! empty( $resources->custom[0]->content ) ) : ?>
	<div id="custom" class="mpcs-resource-section">
		<?php if ( ! empty( $resources->labels['custom'] ) ) : ?>
			<h3><?php echo esc_html( $resources->labels['custom'] ); ?></h3>
		<?php endif; ?>
		<?php echo wp_kses_post( wpautop( $resources->custom[0]->content ) ); ?>
	</div>
<?php endif; ?>
<?php
// No content available.
if ( empty( $quizzes ) && empty( $assignments ) && empty( $resources->custom ) && empty( $resources->custom[0]->content ) ) {
	echo '<p class="bb-rl-mpcs-no-content">' . esc_html__( 'No content available.', 'buddyboss' ) . '</p>';
}
