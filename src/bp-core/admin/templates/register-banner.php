<?php
/**
 * "Register your email and get Memberships & Courses" banner markup.
 *
 * Included by bb_admin_render_register_banner() with $mode in scope:
 * - 'pitch'  — full banner (illustration, headline, CTA, modal) with the
 *              activate-license notice present but hidden; JS reveals it and
 *              hides the pitch elements after a successful registration.
 * - 'notice' — the activate-license notice strip only.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bb_reg_is_pitch    = 'pitch' === $mode;
$bb_reg_license_url = admin_url( 'admin.php?page=' . \BuddyBoss\Core\Admin\Mothership\BB_License_Page::SLUG );
?>
<div class="bb-reg-banner-holder">
	<div class="bb-reg-banner" role="region" aria-label="<?php esc_attr_e( 'Register your email and get Memberships and Courses', 'buddyboss' ); ?>">

		<?php if ( $bb_reg_is_pitch ) : ?>
			<div class="bb-reg-banner-image">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/register-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'BuddyBoss Register Banner', 'buddyboss' ); ?>" />
			</div>
		<?php endif; ?>

		<div class="bb-reg-content">
			<div class="bb-reg-notice" role="note" data-bb-reg-notice<?php echo $bb_reg_is_pitch ? ' hidden' : ''; ?>>
				<span class="bb-reg-notice__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
						<path d="M10 1.875C8.39303 1.875 6.82214 2.35152 5.486 3.24431C4.14985 4.1371 3.10844 5.40605 2.49348 6.8907C1.87852 8.37535 1.71762 10.009 2.03112 11.5851C2.34463 13.1612 3.11846 14.6089 4.25476 15.7452C5.39106 16.8815 6.8388 17.6554 8.4149 17.9689C9.99099 18.2824 11.6247 18.1215 13.1093 17.5065C14.594 16.8916 15.8629 15.8502 16.7557 14.514C17.6485 13.1779 18.125 11.607 18.125 10C18.1227 7.84581 17.266 5.78051 15.7427 4.25727C14.2195 2.73403 12.1542 1.87727 10 1.875ZM9.375 6.25C9.375 6.08424 9.44085 5.92527 9.55806 5.80806C9.67527 5.69085 9.83424 5.625 10 5.625C10.1658 5.625 10.3247 5.69085 10.4419 5.80806C10.5592 5.92527 10.625 6.08424 10.625 6.25V10.625C10.625 10.7908 10.5592 10.9497 10.4419 11.0669C10.3247 11.1842 10.1658 11.25 10 11.25C9.83424 11.25 9.67527 11.1842 9.55806 11.0669C9.44085 10.9497 9.375 10.7908 9.375 10.625V6.25ZM10 14.375C9.81458 14.375 9.63333 14.32 9.47916 14.217C9.32499 14.114 9.20482 13.9676 9.13387 13.7963C9.06291 13.625 9.04434 13.4365 9.08052 13.2546C9.11669 13.0727 9.20598 12.9057 9.33709 12.7746C9.4682 12.6435 9.63525 12.5542 9.81711 12.518C9.99896 12.4818 10.1875 12.5004 10.3588 12.5714C10.5301 12.6423 10.6765 12.7625 10.7795 12.9167C10.8825 13.0708 10.9375 13.2521 10.9375 13.4375C10.9375 13.6861 10.8387 13.9246 10.6629 14.1004C10.4871 14.2762 10.2486 14.375 10 14.375Z" fill="#DF7D05"/>
					</svg>
				</span>
				<span class="bb-reg-notice__text"><?php esc_html_e( 'Complete setup by activating your license', 'buddyboss' ); ?></span>
				<a class="bb-reg-notice__link" href="<?php echo esc_url( $bb_reg_license_url ); ?>">
					<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
						<path d="M13.8538 8.35403L9.35375 12.854C9.25993 12.9478 9.13268 13.0006 9 13.0006C8.86732 13.0006 8.74007 12.9478 8.64625 12.854C8.55243 12.7602 8.49972 12.633 8.49972 12.5003C8.49972 12.3676 8.55243 12.2403 8.64625 12.1465L12.2931 8.50028H2.5C2.36739 8.50028 2.24021 8.4476 2.14645 8.35383C2.05268 8.26006 2 8.13289 2 8.00028C2 7.86767 2.05268 7.74049 2.14645 7.64672C2.24021 7.55296 2.36739 7.50028 2.5 7.50028H12.2931L8.64625 3.85403C8.55243 3.76021 8.49972 3.63296 8.49972 3.50028C8.49972 3.3676 8.55243 3.24035 8.64625 3.14653C8.74007 3.05271 8.86732 3 9 3C9.13268 3 9.25993 3.05271 9.35375 3.14653L13.8538 7.64653C13.9002 7.69296 13.9371 7.74811 13.9623 7.80881C13.9874 7.86951 14.0004 7.93457 14.0004 8.00028C14.0004 8.06599 13.9874 8.13105 13.9623 8.19175C13.9371 8.25245 13.9002 8.30759 13.8538 8.35403Z" fill="currentColor"/>
					</svg>
				</a>
			</div>

			<?php if ( $bb_reg_is_pitch ) : ?>
				<div class="bb-reg-heads">
					<h2 class="bb-reg-title"><?php esc_html_e( 'Register your email and get Memberships & Courses', 'buddyboss' ); ?></h2>
					<p class="bb-reg-desc"><?php esc_html_e( 'Build courses and then connect them to memberships to start monetizing your community and grow your passive income.', 'buddyboss' ); ?></p>
				</div>

				<div class="bb-reg-cta">
					<button type="button" class="bb-reg-btn" data-bb-reg-open>
						<?php esc_html_e( 'Register with Email', 'buddyboss' ); ?>
					</button>
				</div>

				<p class="bb-reg-onus"><?php esc_html_e( 'Its On Us..!', 'buddyboss' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $bb_reg_is_pitch ) : ?>
		<?php
		/*
		 * Free-license registration modal. Its submit posts First/Last/Email to
		 * Platform's own `bb_get_free_license` AJAX action (the working
		 * Mothership issuance flow). Four states — form, loading, success /
		 * already-registered, error — are toggled by register-banner.js.
		 */
		?>
		<div class="bb-reg-modal" data-bb-reg-modal hidden>
			<div class="bb-reg-modal__overlay" data-bb-reg-close></div>
			<div class="bb-reg-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bb-reg-modal-title">
				<div class="bb-reg-modal__header">
					<h3 class="bb-reg-modal__title" id="bb-reg-modal-title"><?php esc_html_e( 'Get BuddyBoss Platform License Key', 'buddyboss' ); ?></h3>
					<button type="button" class="bb-reg-modal__close" data-bb-reg-close aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>
					</button>
				</div>

				<form class="bb-reg-modal__body" data-bb-reg-form novalidate>
					<div class="bb-reg-modal__field">
						<label for="bb-reg-first"><?php esc_html_e( 'First Name', 'buddyboss' ); ?> <span class="bb-reg-modal__req">*</span></label>
						<input type="text" id="bb-reg-first" data-bb-reg-first placeholder="<?php esc_attr_e( 'Enter your first name', 'buddyboss' ); ?>" required />
					</div>
					<div class="bb-reg-modal__field">
						<label for="bb-reg-last"><?php esc_html_e( 'Last Name', 'buddyboss' ); ?> <span class="bb-reg-modal__req">*</span></label>
						<input type="text" id="bb-reg-last" data-bb-reg-last placeholder="<?php esc_attr_e( 'Enter your last name', 'buddyboss' ); ?>" required />
					</div>
					<div class="bb-reg-modal__field">
						<label for="bb-reg-email"><?php esc_html_e( 'Email Address', 'buddyboss' ); ?> <span class="bb-reg-modal__req">*</span></label>
						<input type="email" id="bb-reg-email" data-bb-reg-email placeholder="<?php esc_attr_e( 'Enter your email address', 'buddyboss' ); ?>" required />
					</div>
					<p class="bb-reg-modal__error" data-bb-reg-inline-error role="alert" hidden></p>
					<div class="bb-reg-modal__footer">
						<button type="button" class="bb-reg-modal__btn bb-reg-modal__btn--ghost" data-bb-reg-close><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></button>
						<button type="submit" class="bb-reg-modal__btn bb-reg-modal__btn--primary"><?php esc_html_e( 'Register', 'buddyboss' ); ?></button>
					</div>
				</form>

				<div class="bb-reg-modal__state bb-reg-modal__state--loading" data-bb-reg-state="loading" hidden>
					<span class="bb-reg-modal__key" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#2F2F2F" viewBox="0 0 256 256"><path d="M216.57,39.43A80,80,0,0,0,83.91,120.78L28.69,176A15.86,15.86,0,0,0,24,187.31V216a16,16,0,0,0,16,16H72a8,8,0,0,0,8-8V208H96a8,8,0,0,0,8-8V184h16a8,8,0,0,0,5.66-2.34l9.56-9.57A79.73,79.73,0,0,0,160,176h.1A80,80,0,0,0,216.57,39.43ZM224,98.1c-1.09,34.09-29.75,61.86-63.89,61.9H160a63.7,63.7,0,0,1-23.65-4.51,8,8,0,0,0-8.84,1.68L116.69,168H96a8,8,0,0,0-8,8v16H72a8,8,0,0,0-8,8v16H40V187.31l58.83-58.82a8,8,0,0,0,1.68-8.84A63.72,63.72,0,0,1,96,95.92c0-34.14,27.81-62.8,61.9-63.89A64,64,0,0,1,224,98.1ZM192,76a12,12,0,1,1-12-12A12,12,0,0,1,192,76Z"></path></svg>
					</span>
					<p class="bb-reg-modal__loading-text">
						<span><?php esc_html_e( 'Getting Your License', 'buddyboss' ); ?></span>
						<span class="bb-reg-modal__spinner bb-reg-modal__spinner--inline" aria-hidden="true"></span>
					</p>
					<div class="bb-reg-modal__footer">
						<button type="button" class="bb-reg-modal__btn bb-reg-modal__btn--primary" data-bb-reg-cancel-loading><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></button>
					</div>
				</div>

				<div class="bb-reg-modal__state bb-reg-modal__state--result" data-bb-reg-state="success" hidden>
					<span class="bb-reg-modal__icon bb-reg-modal__icon--success" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#009951" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm45.66,85.66-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35a8,8,0,0,1,11.32,11.32Z"></path></svg>
					</span>
					<p class="bb-reg-modal__result-title"><?php esc_html_e( 'Your license key has been sent successfully', 'buddyboss' ); ?></p>
					<p class="bb-reg-modal__result-desc"><?php esc_html_e( 'Please check your inbox and verify your email to access your license key. If you don\'t see the email in your inbox, kindly check your spam or junk folder.', 'buddyboss' ); ?></p>
					<div class="bb-reg-modal__footer">
						<button type="button" class="bb-reg-modal__btn bb-reg-modal__btn--primary" data-bb-reg-done><?php esc_html_e( 'Close', 'buddyboss' ); ?></button>
					</div>
				</div>

				<div class="bb-reg-modal__state bb-reg-modal__state--result" data-bb-reg-state="already" hidden>
					<span class="bb-reg-modal__icon bb-reg-modal__icon--warning" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#DF7D05" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm-8,56a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm8,104a12,12,0,1,1,12-12A12,12,0,0,1,128,184Z"></path></svg>
					</span>
					<p class="bb-reg-modal__result-title"><?php esc_html_e( 'Your license has already been registered', 'buddyboss' ); ?></p>
					<p class="bb-reg-modal__result-desc">
						<?php
						printf(
							/* translators: %s is a link to the user's BuddyBoss account area. */
							esc_html__( 'Please visit your %s area to view or retrieve your license key.', 'buddyboss' ),
							'<a href="' . esc_url( 'https://buddyboss.com/my-account/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'account', 'buddyboss' ) . '</a>'
						);
						?>
					</p>
					<div class="bb-reg-modal__footer">
						<button type="button" class="bb-reg-modal__btn bb-reg-modal__btn--primary" data-bb-reg-done><?php esc_html_e( 'Close', 'buddyboss' ); ?></button>
					</div>
				</div>

				<div class="bb-reg-modal__state bb-reg-modal__state--result" data-bb-reg-state="error" hidden>
					<span class="bb-reg-modal__icon bb-reg-modal__icon--error" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#EC221F" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm-8,56a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm8,104a12,12,0,1,1,12-12A12,12,0,0,1,128,184Z"></path></svg>
					</span>
					<p class="bb-reg-modal__result-title" data-bb-reg-error-msg></p>
					<div class="bb-reg-modal__footer">
						<button type="button" class="bb-reg-modal__btn bb-reg-modal__btn--primary" data-bb-reg-done><?php esc_html_e( 'Close', 'buddyboss' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
