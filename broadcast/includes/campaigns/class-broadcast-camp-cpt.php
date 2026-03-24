<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the broadcast_camp_email custom post type used as the Gutenberg email canvas.
 *
 * Each campaign has a linked broadcast_camp_email post (stored in body_post_id).
 * Opening the Gutenberg editor for that post IS the visual email builder.
 */
class Broadcast_Camp_CPT {

	public static function init() {
		add_action( 'init',                        array( __CLASS__, 'register_post_type' ) );
		add_filter( 'allowed_block_types_all',     array( __CLASS__, 'allowed_blocks' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_action( 'admin_head',                  array( __CLASS__, 'editor_head_styles' ) );
	}

	// ── CPT registration ──────────────────────────────────────────────────────

	public static function register_post_type() {
		register_post_type( 'broadcast_camp_email', array(
			'labels' => array(
				'name'          => __( 'Campaign Emails', 'broadcast' ),
				'singular_name' => __( 'Campaign Email', 'broadcast' ),
				'edit_item'     => __( 'Email Builder', 'broadcast' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'rest_base'       => 'broadcast-camp-email',
			'supports'        => array( 'title', 'editor', 'custom-fields' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		) );

		register_post_meta( 'broadcast_camp_email', '_broadcast_camp_id', array(
			'type'         => 'integer',
			'single'       => true,
			'show_in_rest' => false,
		) );
	}

	// ── Restrict blocks to email-safe set ────────────────────────────────────

	public static function allowed_blocks( $allowed_blocks, $editor_context ) {
		if ( empty( $editor_context->post ) || 'broadcast_camp_email' !== $editor_context->post->post_type ) {
			return $allowed_blocks;
		}

		return array(
			'core/paragraph',
			'core/heading',
			'core/image',
			'core/buttons',
			'core/button',
			'core/columns',
			'core/column',
			'core/group',
			'core/spacer',
			'core/separator',
			'core/list',
			'core/list-item',
			'core/html',
			'core/quote',
			'core/table',
		);
	}

	// ── Gutenberg editor assets ───────────────────────────────────────────────

	public static function enqueue_editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'broadcast_camp_email' !== $screen->post_type ) {
			return;
		}

		$post_id     = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$campaign_id = $post_id ? absint( get_post_meta( $post_id, '_broadcast_camp_id', true ) ) : 0;
		$campaign    = null;

		if ( $campaign_id ) {
			global $wpdb;
			$table    = $wpdb->prefix . 'broadcast_campaigns';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM `{$table}` WHERE id = %d", $campaign_id ) );
		}

		wp_enqueue_script(
			'broadcast-camp-editor',
			BROADCAST_URL . 'assets/js/broadcast-camp-editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
			BROADCAST_CAMP_VERSION,
			true
		);

		wp_localize_script( 'broadcast-camp-editor', 'broadcastCampEditor', array(
			'campaign_id'   => $campaign_id,
			'campaign_name' => $campaign ? $campaign->name : '',
			'return_url'    => $campaign_id
				? admin_url( 'admin.php?page=broadcast-campaigns&action=edit&campaign_id=' . $campaign_id . '&msg=body_saved' )
				: admin_url( 'admin.php?page=broadcast-campaigns' ),
		) );

		wp_enqueue_style(
			'broadcast-camp-editor',
			BROADCAST_URL . 'assets/css/broadcast-camp-editor.css',
			array(),
			BROADCAST_CAMP_VERSION
		);
	}

	/**
	 * Inject editor styles into <head> for the email CPT.
	 */
	public static function editor_head_styles() {
		$screen = get_current_screen();
		if ( ! $screen || 'broadcast_camp_email' !== $screen->post_type ) {
			return;
		}
		?>
		<style id="broadcast-camp-editor-overrides">
		.edit-post-visual-editor__post-title-wrapper,
		.editor-post-title__block,
		.editor-post-title { display: none !important; }
		.editor-styles-wrapper {
			max-width: 620px !important;
			margin: 0 auto !important;
			background: #ffffff !important;
			box-shadow: 0 0 0 1px #e5e7eb, 0 4px 24px rgba(0,0,0,.08) !important;
			padding: 32px 24px !important;
		}
		.editor-visual-editor,
		.block-editor-writing-flow {
			background: #f3f4f6 !important;
		}
		.wp-block-image img { max-width: 100% !important; }
		</style>
		<?php
	}

	// ── Factory / helpers ─────────────────────────────────────────────────────

	/**
	 * Create a new broadcast_camp_email post linked to a campaign.
	 *
	 * @param int $campaign_id
	 * @return int  New post ID, or 0 on failure.
	 */
	public static function create_for_campaign( $campaign_id ) {
		$post_id = wp_insert_post( array(
			'post_type'   => 'broadcast_camp_email',
			'post_status' => 'draft',
			'post_title'  => 'Campaign ' . absint( $campaign_id ) . ' — Email Body',
			'meta_input'  => array(
				'_broadcast_camp_id' => absint( $campaign_id ),
			),
		) );
		return is_wp_error( $post_id ) ? 0 : $post_id;
	}

	/**
	 * Render block content for use as an email HTML body.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public static function render_email_html( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return '';
		}

		$content = apply_filters( 'the_content', $post->post_content );

		return self::wrap_for_email( $content );
	}

	/**
	 * Wrap rendered block HTML in a minimal email document.
	 */
	public static function wrap_for_email( $content ) {
		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<style>
body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}
img{-ms-interpolation-mode:bicubic;border:0;outline:0;text-decoration:none}
body{height:100%!important;margin:0;padding:0;width:100%!important;background:#f3f4f6}
.email-outer{background:#f3f4f6;padding:24px 0}
.email-inner{background:#ffffff;max-width:600px;margin:0 auto;border-radius:4px}
.email-body{padding:32px 24px}
p{margin:0 0 16px;line-height:1.6;color:#374151;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:15px}
h1,h2,h3,h4{margin:0 0 16px;color:#111827;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;line-height:1.3}
h1{font-size:28px}h2{font-size:22px}h3{font-size:18px}
a{color:#2271b1}
.wp-block-image img{max-width:100%;height:auto;display:block;margin:0 auto}
.wp-block-image{margin-bottom:16px}
.wp-block-buttons{text-align:center;margin-bottom:24px}
.wp-block-button{display:inline-block}
.wp-block-button__link{display:inline-block;padding:12px 28px;background:#2271b1;color:#ffffff!important;text-decoration:none;border-radius:4px;font-weight:600;font-size:15px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;mso-padding-alt:12px 28px}
.wp-block-separator{border:none;border-top:1px solid #e5e7eb;margin:24px 0;height:0}
.wp-block-spacer{display:block}
.wp-block-columns{display:table;width:100%;border-spacing:0;border-collapse:collapse;margin-bottom:16px}
.wp-block-column{display:table-cell;vertical-align:top;padding:0 8px}
.wp-block-column:first-child{padding-left:0}
.wp-block-column:last-child{padding-right:0}
.wp-block-quote{margin:0 0 16px;padding:12px 16px;border-left:4px solid #2271b1;background:#f0f7ff}
.wp-block-quote p{margin:0;font-style:italic}
ul,ol{padding-left:24px;margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6}
.wp-block-table table{border-collapse:collapse;width:100%}
.wp-block-table td,.wp-block-table th{border:1px solid #e5e7eb;padding:8px 12px;font-size:14px}
.wp-block-table th{background:#f9fafb;font-weight:600}
</style>
</head>
<body>
<div class="email-outer">
<div class="email-inner">
<div class="email-body">
<?php echo $content; ?>
</div>
</div>
</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}
}
