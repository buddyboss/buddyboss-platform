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
		if ( empty( $url ) )
			return '';

		$rawattr = $attr;
		$attr = wp_parse_args( $attr, wp_embed_defaults() );

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
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$oembed_obj = _wp_oembed_get_object();

		// If oEmbed discovery is true, skip oEmbed provider check.
		$is_oembed_link = false;
		if ( !$attr['discover'] ) {
			foreach ( (array) $oembed_obj->providers as $provider_matchmask => $provider ) {
				$regex = ( $is_regex = $provider[1] ) ? $provider_matchmask : '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $provider_matchmask ), '#' ) ) . '#i';

				if ( preg_match( $regex, $url ) )
					$is_oembed_link = true;
			}

			// If url doesn't match a WP oEmbed provider, stop parsing.
			if ( !$is_oembed_link )
				return $this->maybe_make_link( $url );
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
			if ( !empty( $cache ) ) {

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
				$html = wp_oembed_get( $url, $attr );
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

		if ( isset( $type->component ) && ( 'activity_update' === $type->type || 'activity_comment' === $type->type ) ) {
			// Check if WordPress already embed the link then return the original content.
			if (strpos($content,'<iframe') !== false) {
				return $content;
			} else {
				// Find all the URLs from the content.
				preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $match);
				// Check if URL found.
				if( isset( $match[0] ) ) {
					$html = '';
					// Remove duplicate from array and run the loop
					foreach ( array_unique( $match[0] ) as $url ) {
						// Store oembed iframe in database cache for 1 day
						if ( false === ( $embed_code = get_transient( $url ) ) ) {

                            // Fetch the oembed code for URL.
                            $embed_code = wp_oembed_get( $url );

                            set_transient( $url, $embed_code, DAY_IN_SECONDS );

                        }
						// If oembed found then store into the $html
						if (strpos($embed_code,'<iframe') !== false) {
							$html .= '<p>'.$embed_code.'</p>';
						}
					}
					// If $html blank return original content
					if ( '' === $html ) {
						return $content;
						// Return the new content by adding oembed after the content.
					} else {
						return $content.$html;
					}
					// Else return original content.
				} else {
					return $content;
				}
			}
		}

		// Replace line breaks from all HTML elements with placeholders.
		$content = wp_replace_in_html_tags( $content, array( "\n" => '<!-- wp-line-break -->' ) );
		$old_content = $content;
		if ( preg_match( '#(^|\s|>)https?://#i', $content ) ) {

			$url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
			$content = preg_replace($url, '<p>$0</p>', $content);


			// Find URLs on their own line.
			$content = preg_replace_callback( '|^(\s*)(https?://[^\s<>"]+)(\s*)$|im', array( $this, 'autoembed_callback' ), $content );
			// Find URLs in their own paragraph.
			$content = preg_replace_callback( '|(<p(?: [^>]*)?>\s*)(https?://[^\s<>"]+)(\s*<\/p>)|i', array( $this, 'autoembed_callback' ), $content );
		}


		if (strpos($content,'<iframe') !== false) {

		} else {
			$content = $old_content;
		}

		// Put the line breaks back.
		return str_replace( '<!-- wp-line-break -->', "\n", $content );
	}
}
