<?php
/**
 * BuddyPress email template.
 *
 * Magic numbers:
 *  1.618 = golden mean.
 *  1.35  = default body_text_size multipler. Gives default heading of 20px.
 *
 * @since BuddyPress 2.5.0
 * @version 3.1.0
 *
 * @package BuddyBoss\Core
 */

/*
Based on the Cerberus "Fluid" template by Ted Goas (http://tedgoas.github.io/Cerberus/).
License for the original template:


The MIT License (MIT)

Copyright (c) 2017 Ted Goas

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$settings = bp_email_get_appearance_settings();

?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
	<meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
	<title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

	<!-- CSS Reset -->
	<style type="text/css">
		/* What it does: Remove spaces around the email design added by some email clients. */
		/* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
		html,
		body {
			Margin: 0 !important;
			padding: 0 !important;
			height: 100% !important;
			width: 100% !important;
		}

		pre {
			background: #F5F6F7;
			border: 1px solid rgba(0, 0, 0, 0.03);
			margin: 0 auto;
			overflow: auto;
			padding: 10px;
			white-space: pre-wrap;
			font-size: 14px !important;
			border-radius: 4px;
		}

		blockquote {
			background: #e3e6ea;
			border-radius: 4px;
			padding: 12px;
			font-size: 20px;
			font-style: italic;
			font-weight: normal;
			letter-spacing: -0.24px;
			line-height: 30px;
			position: relative;
			margin: 0 0 15px 0;
		}

		blockquote p {
			margin: 0;
		}

		/* What it does: Stops email clients resizing small text. */
		* {
			-ms-text-size-adjust: 100%;
			-webkit-text-size-adjust: 100%;
		}

		/* What is does: Centers email on Android 4.4 */
		div[style*="margin: 16px 0"] {
			margin: 0 !important;
		}

		/* What it does: Stops Outlook from adding extra spacing to tables. */
		table,
		td {
			mso-table-lspace: 0pt !important;
			mso-table-rspace: 0pt !important;
		}

		/* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
		table {
			border-spacing: 0 !important;
			border-collapse: collapse !important;
			table-layout: fixed !important;
			margin: 0 auto !important;
		}

		table table table {
			table-layout: auto;
		}

		/* What it does: Uses a better rendering method when resizing images in IE. */
		/* & manages img max widths to ensure content body images don't exceed template width. */
		img {
			-ms-interpolation-mode:bicubic;
			height: auto;
			max-width: 100%;
		}

		/* What it does: A work-around for email clients meddling in triggered links. */
		*[x-apple-data-detectors],  /* iOS */
		.x-gmail-data-detectors,    /* Gmail */
		.x-gmail-data-detectors *,
		.aBn {
			border-bottom: 0 !important;
			cursor: default !important;
			color: inherit !important;
			text-decoration: none !important;
			font-size: inherit !important;
			font-family: inherit !important;
			font-weight: inherit !important;
			line-height: inherit !important;
		}

		/* What it does: Prevents Gmail from displaying an download button on large, non-linked images. */
		.a6S {
			display: none !important;
			opacity: 0.01 !important;
		}

		/* If the above doesn't work, add a .g-img class to any image in question. */
			img.g-img + div {
			display: none !important;
		}

		/* What it does: Prevents underlining the button text in Windows 10 */
		.button-link {
			text-decoration: none !important;
		}

		/* Remove links underline */
		a:not(.ab-item), .ii a[href] {
			color: <?php echo esc_attr( $settings['highlight_color'] ); ?> !important;
			text-decoration: none !important;
		}

		/* What it does: Forces Outlook.com to display emails full width. */
		.ExternalClass {
			width: 100%;
		}

		.recipient_text_color table {
			display: inline-table;
		}

		.bb-email-message-content p {
			margin: 0 0 5px 0;
		}

		/* MOBILE STYLES */
		@media screen and (max-width: 525px) {
			/* ALLOWS FOR FLUID TABLES */
			.wrapper {
				width: 100% !important;
				max-width: 100% !important;
			}

			/* ADJUSTS LAYOUT OF LOGO IMAGE */
			.logo img {
				margin: 0 auto !important;
			}

			/* USE THESE CLASSES TO HIDE CONTENT ON MOBILE */
			.mobile-hide {
				display: none !important;
			}

			.img-max {
				max-width: 100% !important;
				width: 100% !important;
				height: auto !important;
			}

			/* FULL-WIDTH TABLES */
			.responsive-table {
				width: 100% !important;
			}

			.mobile-text-center {
				text-align: center !important;
			}

			.mobile-text-left {
				text-align: left !important;
			}

			.repsonsive-padding {
				padding: 0 20px !important;
			}

			.responsive-set-height {
				font-size: 0 !important;
				line-height: 0 !important;
				height: 0 !important;
			}

			.mobile-block-full {
				display: block !important;
				width: 100% !important;
			}

			.mobile-block-padding-full {
				display: block !important;
				padding: 0 20px !important;
				width: 100% !important;
				box-sizing: border-box;
			}

			.avatar-wrap.mobile-center {
				margin: 20px auto 10px !important;
			}

			.group-avatar-wrap.mobile-center {
				margin: 10px auto 20px !important;
			}

			.mobile-padding-bottom {
				padding-bottom: 10px !important;
			}

			.mobile-button-center {
				margin: 5px auto 0 !important;
				width: 160px !important;
				display: block !important;
			}
		}
	</style>
</head>

<body class="email_bg" width="100%" bgcolor="<?php echo esc_attr( $settings['email_bg'] ); ?>" style="margin: 0; mso-line-height-rule: exactly;">
	<table cellpadding="0" cellspacing="0" border="0" height="100%" width="100%" bgcolor="<?php echo esc_attr( $settings['email_bg'] ); ?>" style="border-collapse:collapse;" class="email_bg"><tr><td valign="top">
		<center style="width: 100%; text-align: <?php echo esc_attr( $settings['direction'] ); ?>;">

		<!-- Visually Hidden Preheader Text : BEGIN -->
		<div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
			{{email.preheader}}
		</div>
		<!-- Visually Hidden Preheader Text : END -->

		<div style="max-width: 600px; margin: auto; padding: 10px;" class="email-container">
			<!--[if mso]>
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center">
			<tr>
			<td>
			<![endif]-->

			<!-- Email Header : BEGIN -->
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px;">
				<tr>
					<td style="text-align: left; padding: 50px 0 30px 0; font-family: sans-serif; mso-height-rule: exactly; font-weight: bold; color: <?php echo esc_attr( $settings['site_title_text_color'] ); ?>; font-size: <?php echo esc_attr( $settings['site_title_text_size'] . 'px' ); ?>" class="site_title_text_color site_title_text_size">
						<?php
						/**
						 * Fires before the display of the email template header.
						 *
						 * @since BuddyPress 2.5.0
						 */
						do_action( 'bp_before_email_header' );

						$blogname      = bp_get_option( 'blogname' );
						$attachment_id = isset( $settings['logo'] ) ? $settings['logo'] : '';

						if ( ! empty( $attachment_id ) ) {
							$image_src = wp_get_attachment_image_src( $attachment_id, array( 180, 45 ) );
							if ( ! empty( $image_src ) ) {
								?>
								<img src="<?php echo esc_attr( $image_src[0] ); ?>" alt="<?php echo esc_attr( $blogname ); ?>" style="margin:0; padding:0; border:none; display:block; max-height:auto; height:auto; width:<?php echo esc_attr( $settings['site_title_logo_size'] ); ?>px;" border="0" />
													 <?php
							} else {
								echo $blogname;
							}
						} else {
							echo $blogname;
						}

						/**
						 * Fires after the display of the email template header.
						 *
						 * @since BuddyPress 2.5.0
						 */
						do_action( 'bp_after_email_header' );
						?>
					</td>
					<td style="text-align: right; padding: 50px 0 30px 0; font-family: sans-serif; mso-height-rule: exactly; font-weight: normal; color: <?php echo esc_attr( $settings['recipient_text_color'] ); ?>; font-size: <?php echo esc_attr( $settings['recipient_text_size'] . 'px' ); ?>" class="recipient_text_color recipient_text_size">
						<?php
						/**
						 * Fires before the display of the email recipient.
						 *
						 * @since BuddyBoss 1.0.0
						 */
						do_action( 'bp_before_email_recipient' );

						// echo bp_get_option( 'blogname' );
						if ( bp_is_email_customizer() ) {
							echo '{{recipient.name}} <img src="' . apply_filters( 'bp_email_customizer_default_avatar', bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ) ) . '" width="34" height="34" style="border: 1px solid #b9babc; border-radius: 50%; margin-left: 12px; vertical-align: middle;" />';
						} else {
							bp_email_the_salutation( $settings );
						}

						/**
						 * Fires after the display of the email recipient.
						 *
						 * @since BuddyBoss 1.0.0
						 */
						do_action( 'bp_after_email_recipient' );
						?>
					</td>
				</tr>
			</table>
			<!-- Email Header : END -->

			<!-- Email Body : BEGIN -->
			<?php if ( bp_is_email_customizer() ) { ?>

			<!-- Start Table -->
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="<?php echo esc_attr( $settings['body_bg'] ); ?>" width="100%" style="border-collapse: separate !important; max-width: 600px; border-radius: 5px; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>" class="body_bg body_border_color">
				<tr>
					<td height="40px" style="font-size: 40px; line-height: 40px;">&nbsp;</td>
				</tr>
				<tr>
					<td align="center">
						<!-- LEFT COLUMN -->
						<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
							<tr>
								<td>
									<div class="body_text_color body_text_size" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.618 ) . 'px' ); ?>; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;">
										{{sender.name}} <?php _e( 'sent you a new message', 'buddyboss' ); ?>
									</div>
								</td>
							</tr>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
							<tr>
								<td valign="middle" width="10%" style="vertical-align: middle;">
									<a href="#" target="_blank">
										<img alt="<?php esc_html_e( 'Avatar', 'buddyboss' ); ?>" src="<?php echo apply_filters( 'bp_email_default_avatar', bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ) ); ?>" width="45" height="45" style="border: 1px solid #b9babc; border-radius: 50%; vertical-align: middle;" />
									</a>
									<span class="body_secondary_text_color body_text_size" style="margin-left: 10px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.618 ) . 'px' ); ?>; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; letter-spacing: -0.24px;">{{sender.name}}</span>
								</td>
							</tr>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
							<tr>
								<td>
									<table class="quote_bg body_border_color" cellspacing="0" cellpadding="0" border="0" width="100%" bgcolor="<?php echo esc_attr( $settings['quote_bg'] ); ?>" style="border-radius: 4px; border-collapse: separate !important; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>">
										<tr>
											<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
										</tr>
										<tr>
											<td align="center">
												<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
													<tr>
														<td>
															<div class="body_text_color body_text_size" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.618 ) . 'px' ); ?>; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;">
																{{usermessage}}
															</div>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
							<tr>
								<td>
									<a class="body_text_size highlight_color button_outline" href="#" target="_blank" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: <?php echo esc_attr( floor( $settings['body_text_size'] * 5.25 ) . 'px' ); ?>; text-align: center; height: <?php echo esc_attr( floor( $settings['body_text_size'] * 2.125 ) . 'px' ); ?>; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 2.125 ) . 'px' ); ?>; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>;padding: 0 10px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td height="45px" style="font-size: 45px; line-height: 45px;">&nbsp;</td>
				</tr>
			</table>
			<!-- End Table -->

			<?php } else { ?>
				<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="<?php echo esc_attr( $settings['body_bg'] ); ?>" width="100%" style="border-collapse: separate !important; max-width: 600px; border-radius: 5px; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>" class="body_bg body_border_color">

					<!-- 1 Column Text : BEGIN -->
					<tr>
						<td>
							<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
								<tr>
									<td style="padding: 20px 40px; font-family: sans-serif; mso-height-rule: exactly; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.618 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>" class="body_text_color body_text_size repsonsive-padding">
										{{{content}}}
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<!-- 1 Column Text : BEGIN -->

				</table>
			<?php } ?>
			<!-- Email Body : END -->

			<!-- Email Footer : BEGIN -->
			<br>
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="<?php echo esc_attr( $settings['direction'] ); ?>" width="100%" style="max-width: 600px; border-radius: 5px;">
				<tr>
					<td style="padding: 20px 40px; width: 100%; font-size: <?php echo esc_attr( $settings['footer_text_size'] . 'px' ); ?>; font-family: sans-serif; mso-height-rule: exactly; line-height: <?php echo esc_attr( floor( $settings['footer_text_size'] * 1.618 ) . 'px' ); ?>; text-align: center; color: <?php echo esc_attr( $settings['footer_text_color'] ); ?>;" class="footer_text_color footer_text_size repsonsive-padding">
						<?php
						/**
						 * Fires before the display of the email template footer.
						 *
						 * @since BuddyPress 2.5.0
						 */
						do_action( 'bp_before_email_footer' );
						?>

						<span class="footer_text"><?php echo nl2br( stripslashes( $settings['footer_text'] ) ); ?></span>
						<p style="margin: 5px 0;"><?php _e( "If you don't want to receive these emails in the future, please ", 'buddyboss' ); ?><a href="{{{unsubscribe}}}" style="text-decoration: none;"><?php esc_html_e( 'unsubscribe', 'buddyboss' ); ?></a>.</p>

						<?php
						/**
						 * Fires after the display of the email template footer.
						 *
						 * @since BuddyPress 2.5.0
						 */
						do_action( 'bp_after_email_footer' );
						?>
					</td>
				</tr>
				<tr>
					<td height="45px" style="font-size: 45px; line-height: 45px;">&nbsp;</td>
				</tr>
			</table>
			<!-- Email Footer : END -->

			<!--[if mso]>
			</td>
			</tr>
			</table>
			<![endif]-->
		</div>
	</center>
</td></tr>
</table>
<?php
if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
	wp_footer();
}
?>
</body>
</html>
