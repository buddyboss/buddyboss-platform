<?php
/**
 * Core component classes.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enable oEmbeds in BuddyPress contexts.
 *
 * Extends WP_Embed class for use with BuddyPress.
 *
 * @since BuddyPress 1.5.0
 *
 * @see WP_Embed
 */
class BP_Embed extends WP_Embed {

	/**
	 * Constructor
	 *
	 * @global WP_Embed $wp_embed
	 */
	public function __construct() {
		global $wp_embed;

		// Make sure we populate the WP_Embed handlers array.
		// These are providers that use a regex callback on the URL in question.
		// Do not confuse with oEmbed providers, which require an external ping.
		// Used in WP_Embed::shortcode().
		$this->handlers = $wp_embed->handlers;

		if ( bp_use_embed_in_activity() ) {
			add_filter( 'bp_get_activity_content_body', array( &$this, 'autoembed' ), 8, 2 );
			add_filter( 'bp_get_activity_content_body', array( &$this, 'run_shortcode' ), 7, 2 );
		}

		if ( bp_use_embed_in_activity_replies() ) {
			add_filter( 'bp_get_activity_content', array( &$this, 'autoembed' ), 8, 2 );
			add_filter( 'bp_get_activity_content', array( &$this, 'run_shortcode' ), 7, 2 );
		}

		if ( bp_use_embed_in_private_messages() ) {
			add_filter( 'bp_get_the_thread_message_content', array( &$this, 'autoembed' ), 8 );
			add_filter( 'bp_get_the_thread_message_content', array( &$this, 'run_shortcode' ), 7 );
		}

		/**
		 * Filters the BuddyBoss Core oEmbed setup.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param BP_Embed $this Current instance of the BP_Embed. Passed by reference.
		 */
		do_action_ref_array( 'bp_core_setup_oembed', array( &$this ) );
	}

	/**
	 * The {@link do_shortcode()} callback function.
	 *
	 * Attempts to convert a URL into embed HTML. Starts by checking the
	 * URL against the regex of the registered embed handlers. Next, checks
	 * the URL against the regex of registered {@link WP_oEmbed} providers
	 * if oEmbed discovery is false. If none of the regex matches and it's
	 * enabled, then the URL will be passed to {@link BP_Embed::parse_oembed()}
	 * for oEmbed parsing.
	 *
	 * @param array  $attr Shortcode attributes.
	 * @param string $url  The URL attempting to be embeded.
	 * @return string The embed HTML on success, otherwise the original URL.
	 */
	public function shortcode( $attr, $url = '' ) {
		if ( empty( $url ) ) {
			return '';
		}

		$rawattr = $attr;
		$attr    = bp_parse_args( $attr, wp_embed_defaults() );

		// Use kses to convert & into &amp; and we need to undo this
		// See https://core.trac.wordpress.org/ticket/11311.
		$url = str_replace( '&amp;', '&', $url );

		// Look for known internal handlers.
		ksort( $this->handlers );
		foreach ( $this->handlers as $priority => $handlers ) {
			foreach ( $handlers as $hid => $handler ) {
				if ( preg_match( $handler['regex'], $url, $matches ) && is_callable( $handler['callback'] ) ) {
					if ( false !== $return = call_user_func( $handler['callback'], $matches, $attr, $url, $rawattr ) ) {

						/**
						 * Filters the oEmbed handler result for the provided URL.
						 *
						 * @since BuddyPress 1.5.0
						 *
						 * @param string $return Handler callback for the oEmbed.
						 * @param string $url    URL attempting to be embedded.
						 * @param array  $attr   Shortcode attributes.
						 */
						return apply_filters( 'embed_handler_html', $return, $url, $attr );
					}
				}
			}
		}

		/**
		 * Filters the embed object ID.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param int $value Value of zero.
		 */
		$id = apply_filters( 'embed_post_id', 0 );

		$unfiltered_html   = current_user_can( 'unfiltered_html' );
		$default_discovery = false;

		// Since 4.4, WordPress is now an oEmbed provider.
		if ( function_exists( 'wp_oembed_register_route' ) ) {
			$unfiltered_html   = true;
			$default_discovery = true;
		}

		/**
		 * Filters whether or not oEmbed discovery is on.
		 *
		 * @since BuddyPress 1.5.0
		 * @since BuddyPress 2.5.0 Default status of oEmbed discovery has been switched
		 *              to true to apply changes introduced in WordPress 4.4
		 *
		 * @param bool $default_discovery Current status of oEmbed discovery.
		 */
		$attr['discover'] = ( apply_filters( 'bp_embed_oembed_discover', $default_discovery ) && $unfiltered_html );

		// Set up a new WP oEmbed object to check URL with registered oEmbed providers.
		if ( file_exists( ABSPATH . WPINC . '/class-wp-oembed.php' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-oembed.php';
		} else {
			// class-oembed.php is deprecated in WordPress 5.3.0.
			require_once ABSPATH . WPINC . '/class-oembed.php';
		}

		$oembed_obj = _wp_oembed_get_object();

		// If oEmbed discovery is true, skip oEmbed provider check.
		$is_oembed_link = false;
		if ( ! $attr['discover'] ) {
			foreach ( (array) $oembed_obj->providers as $provider_matchmask => $provider ) {
				$regex = ( $is_regex = $provider[1] ) ? $provider_matchmask : '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $provider_matchmask ), '#' ) ) . '#i';

				if ( preg_match( $regex, $url ) ) {
					$is_oembed_link = true;
				}
			}

			// If url doesn't match a WP oEmbed provider, stop parsing.
			if ( ! $is_oembed_link ) {
				return $this->maybe_make_link( $url );
			}
		}

		return $this->parse_oembed( $id, $url, $attr, $rawattr );
	}

	/**
	 * Base function so BP components/plugins can parse links to be embedded.
	 *
	 * View an example to add support in {@link bp_activity_embed()}.
	 *
	 *       on success.
	 *       oEmbed failure.
	 *
	 * @param int    $id      ID to do the caching for.
	 * @param string $url     The URL attempting to be embedded.
	 * @param array  $attr    Shortcode attributes from {@link WP_Embed::shortcode()}.
	 * @param array  $rawattr Untouched shortcode attributes from
	 *                        {@link WP_Embed::shortcode()}.
	 * @return string The embed HTML on success, otherwise the original URL.
	 */
	public function parse_oembed( $id, $url, $attr, $rawattr ) {
		$id = intval( $id );

		if ( $id ) {
			// Setup the cachekey.
			$cachekey = '_oembed_' . md5( $url . serialize( $attr ) );

			// Let components / plugins grab their cache.
			$cache = '';

			/**
			 * Filters the cache value to be used in the oEmbed, if exists.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param string $cache    Empty initial cache value.
			 * @param int    $id       ID that the caching is for.
			 * @param string $cachekey Key to use for the caching in the database.
			 * @param string $url      The URL attempting to be embedded.
			 * @param array  $attr     Parsed shortcode attributes.
			 * @param array  $rawattr  Unparsed shortcode attributes.
			 */
			$cache = apply_filters( 'bp_embed_get_cache', $cache, $id, $cachekey, $url, $attr, $rawattr );

			// Grab cache and return it if available.
			if ( ! empty( $cache ) ) {

				/**
				 * Filters the found cache for the provided URL.
				 *
				 * @since BuddyPress 1.5.0
				 *
				 * @param string $cache   Cached HTML markup for embed.
				 * @param string $url     The URL being embedded.
				 * @param array  $attr    Parsed shortcode attributes.
				 * @param array  $rawattr Unparased shortcode attributes.
				 */
				return apply_filters( 'bp_embed_oembed_html', $cache, $url, $attr, $rawattr );

				// If no cache, ping the oEmbed provider and cache the result.
			} else {
				$html  = wp_oembed_get( $url, $attr );
				$cache = ( $html ) ? $html : $url;

				/**
				 * Fires if there is no existing cache and triggers cache setting.
				 *
				 * Lets components / plugins save their cache.
				 *
				 * @since BuddyPress 1.5.0
				 *
				 * @param string $cache    Newly cached HTML markup for embed.
				 * @param string $cachekey Key to use for the caching in the database.
				 * @param int    $id       ID to do the caching for.
				 */
				do_action( 'bp_embed_update_cache', $cache, $cachekey, $id );

				// If there was a result, return it.
				if ( $html ) {

					/** This filter is documented in bp-core/classes/class-bp-embed.php */
					return apply_filters( 'bp_embed_oembed_html', $html, $url, $attr, $rawattr );
				}
			}
		}

		// Still unknown.
		return $this->maybe_make_link( $url );
	}

	/**
	 * Passes any unlinked URLs that are on their own line to WP_Embed::shortcode() for potential embedding.
	 *
	 * @see WP_Embed::autoembed_callback()
	 *
	 * @param string $content The content to be searched.
	 * @return string Potentially modified $content.
	 */
	public function autoembed( $content, $type = '' ) {
		$is_activity = isset( $type->component ) && ( 'activity_update' === $type->type || 'activity_comment' === $type->type );
		$link_embed  = false;

		// Check the type of activity and return if not a bbPress activity.
		if (
			! empty( $type ) &&
			is_object( $type ) &&
			! empty( $type->component ) &&
			'bbpress' === $type->component &&
			! empty( $type->type ) &&
			in_array( $type->type, array( 'bbp_reply_create', 'bbp_topic_create' ), true ) &&
			metadata_exists( 'post', $type->item_id, '_link_embed' )
		) {
			return $content;
		}

		if ( $is_activity ) {

			if ( ! empty( $content ) && false !== strpos( '<iframe', $content ) ) {
				return apply_filters( 'bp_embeds', $content );
			}

			// check if preview url was used or not, if not return content without embed
			$link_embed = bp_activity_get_meta( $type->id, '_link_embed', true );
			if ( '0' === $link_embed ) {
				return $content;
			} elseif ( ! empty( $link_embed ) ) {
				$embed_data = bp_core_parse_url( $link_embed );

				if ( isset( $embed_data['wp_embed'] ) && $embed_data['wp_embed'] && ! empty( $embed_data['description'] ) ) {
					$embed_code = $embed_data['description'];
				} else {
					$embed_code = wp_oembed_get( $link_embed, array( 'discover' => false ) );

					if ( ! empty( $embed_code ) ) {
						$parsed_url_data = array(
							'title'       => ' ',
							'description' => $embed_code,
							'images'      => '',
							'error'       => '',
							'wp_embed'    => true,
						);
						$cache_key       = 'bb_oembed_' . md5( maybe_serialize( $link_embed ) );
						// set the transient.
						set_transient( $cache_key, $parsed_url_data, DAY_IN_SECONDS );
					}
				}

				if ( ! empty( $embed_code ) ) {

					if ( ! empty( $content ) ) {
						preg_match( '/(https?:\/\/[^\s<>"]+)/i', $content, $content_url );
						preg_match( '(<p(>|\s+[^>]*>).*?<\/p>)', $content, $content_tag );

						if ( ! empty( $content_url ) && empty( $content_tag ) ) {
							$content = sprintf( '<p>%s</p>', $content );
						}
					}

					$content   .= $embed_code;
					$link_embed = true;
				}
			} else {

				// check if preview url saved or not for activity, if saved we don't need embed
				$preview_data = bp_activity_get_meta( $type->id, '_link_preview_data', true );
				if ( ! empty( $preview_data['url'] ) ) {
					return $content;
				}
			}
		}

		// Replace line breaks from all HTML elements with placeholders.
		$content = wp_replace_in_html_tags( $content, array( "\n" => '<!-- wp-line-break -->' ) );

		if ( ! $link_embed && preg_match( '#(^|\s|>)https?://#i', $content ) && ! ( strpos( $content, 'download_document_file' ) || strpos( $content, 'download_media_file' ) || strpos( $content, 'download_video_file' ) ) ) {
			// Find URLs on their own line.
			if ( ! $is_activity ) {
				$content = preg_replace_callback( '|^(\s*)(https?://[^\s<>"]+)(\s*)$|im', array( $this, 'autoembed_callback' ), $content );
			}

			// Find URLs in their own paragraph.
			$content = $this->bb_get_content_autoembed_callback( $content );
		}

		$content = str_replace( '<!-- wp-line-break -->', "\n", $content );

		// add lazy loads for iframes to load on front end.
		if ( $is_activity && ! empty( $content ) ) {

			$old_content = $content;
			$content     = preg_replace( '/iframe(.*?)src=/is', 'iframe$1 data-lazy-type="iframe" data-src=', $content );

			// add the lazy class to the iframe element.
			if ( $content !== $old_content ) {
				preg_match( '/<iframe[^>]+(?:class)="([^"]*)"[^>]*>/', $content, $match );
				if ( ! empty( $match[0] ) ) {
					$content = preg_replace( '/class=(["\'])(.*?)["\']/is', 'class=$1lazy $2$1', $content );
				} else {
					$content = preg_replace( '/<iframe/is', '<iframe class="lazy"', $content );
				}
			}
		}

		// Put the line breaks back.
		return apply_filters( 'bp_autoembed', $content );
	}

	/**
	 * Add oembed to content.
	 *
	 * @since BuddyBoss 1.8.3
	 *
	 * @param string $content The content to be searched.
	 *
	 * @return string
	 */
	public function bb_get_content_autoembed_callback( $content ) {
		if ( false !== strpos( $content, '<iframe' ) ) {
			return $content;
		}

		$embed_urls = array();
		$flag       = true;

		if ( preg_match( '/<a.*?<\/a>(*SKIP)(*F)|(https?:\/\/[^\s<>"]+)/i', $content ) ) {
			preg_match_all( '/<a.*?<\/a>(*SKIP)(*F)|(https?:\/\/[^\s<>"]+)/i', $content, $embed_urls );
		}

		if ( ! empty( $embed_urls ) && ! empty( $embed_urls[0] ) ) {
			$embed_urls = array_filter( $embed_urls[0] );
			$embed_urls = array_unique( $embed_urls );

			foreach ( $embed_urls as $url ) {
				if ( false === $flag ) {
					continue;
				}

				$embed = $this->shortcode( array(), $url );
				if ( false !== strpos( $embed, '<iframe' ) ) {
					$is_embed = strpos( $content, $url );

					if ( false !== $is_embed ) {
						$flag    = false;
						$content = substr_replace( $content, $embed, $is_embed, strlen( $url ) );
					}
				}
			}
		}

		return $content;
	}
}
