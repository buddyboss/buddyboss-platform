<?php

/**
 * Forums Formatting
 *
 * @package BuddyBoss\Formatting
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Kses **********************************************************************/

/**
 * Custom allowed tags for forum topics and replies
 *
 * Allows all users to post paragraphs, links, quotes, code, formatting, lists, and images
 *
 * @since bbPress (r4603)
 *
 * @return array Associative array of allowed tags and attributes
 */
function bbp_kses_allowed_tags() {
	return apply_filters(
		'bbp_kses_allowed_tags',
		array(

			// Paragraph
			'p'          => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),

			'abbr'       => array( 'title' => true ),

			'acronym'    => array( 'title' => true ),

			// Bold
			'b'          => array(),

			// Underline
			'u'          => array(),

			// Italic
			'i'          => array(),

			// Br
			'br'         => array(),

			// Links
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'rel'    => array(),
				'class'  => array(),
				'id'     => array(),
				'target' => array(),
			),

			// Span
			'span'       => array(
				'class' => array(),
				'id'    => array(),
			),

			// Quotes
			'blockquote' => array(
				'cite' => array(),
			),

			// Code
			'code'       => array(),
			'pre'        => array(),

			// Formatting
			'em'         => array(),
			'strong'     => array(),
			'del'        => array(
				'datetime' => true,
			),

			// Lists
			'ul'         => array(),
			'ol'         => array(
				'start' => true,
				'class' => true,
			),
			'li'         => array(
				'class' => true,
			),

			// Images
			'img'        => array(
				'src'             => true,
				'border'          => true,
				'alt'             => true,
				'height'          => true,
				'width'           => true,
				'class'           => true,
				'data-emoji-char' => true,
			),
		)
	);
}

/**
 * Custom kses filter for forum topics and replies, for filtering incoming data
 *
 * @since bbPress (r4603)
 *
 * @param string $data Content to filter, expected to be escaped with slashes
 * @return string Filtered content
 */
function bbp_filter_kses( $data = '' ) {
	return addslashes( wp_kses( stripslashes( $data ), bbp_kses_allowed_tags() ) );
}

/**
 * Custom kses filter for forum topics and replies, for raw data
 *
 * @since bbPress (r4603)
 *
 * @param string $data Content to filter, expected to not be escaped
 * @return string Filtered content
 */
function bbp_kses_data( $data = '' ) {
	return wp_kses( $data, bbp_kses_allowed_tags() );
}

/** Formatting ****************************************************************/

/**
 * Filter the topic or reply content and output code and pre tags
 *
 * @since bbPress (r4641)
 *
 * @param string $content Topic and reply content
 * @return string Partially encodedd content
 */
function bbp_code_trick( $content = '' ) {

	$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
	/**
	 * Added for convert &nbsp; to space fron content
	 */
	$content = str_replace( '&nbsp;', " ", $content );
	$content = preg_replace_callback( '|(`)(.*?)`|', 'bbp_encode_callback', $content );
	$content = preg_replace_callback( "!(^|\n)`(.*?)`!s", 'bbp_encode_callback', $content );

	return $content;
}

/**
 * When editing a topic or reply, reverse the code trick so the textarea
 * contains the correct editable content.
 *
 * @since bbPress (r4641)
 *
 * @param string $content Topic and reply content
 * @return string Partially encodedd content
 */
function bbp_code_trick_reverse( $content = '' ) {

	// Setup variables
	$openers = array( '<p>', '<br />' );
	$content = preg_replace_callback( '!(<pre><code>|<code>)(.*?)(</code></pre>|</code>)!s', 'bbp_decode_callback', $content );

	// Do the do
	$content = str_replace( $openers, '', $content );
	$content = str_replace( '</p>', "\n", $content );
	$content = str_replace( '<coded_br />', '<br />', $content );
	$content = str_replace( '<coded_p>', '<p>', $content );
	$content = str_replace( '</coded_p>', '</p>', $content );

	return $content;
}

/**
 * Filter the content and encode any bad HTML tags
 *
 * @since bbPress (r4641)
 *
 * @param string $content Topic and reply content
 * @return string Partially encodedd content
 */
function bbp_encode_bad( $content = '' ) {

	// Setup variables
	$content = _wp_specialchars( $content, ENT_NOQUOTES );
	$content = preg_split( '@(`[^`]*`)@m', $content, -1, PREG_SPLIT_NO_EMPTY + PREG_SPLIT_DELIM_CAPTURE );
	$allowed = bbp_kses_allowed_tags();
	$empty   = array(
		'br'    => true,
		'hr'    => true,
		'img'   => true,
		'input' => true,
		'param' => true,
		'area'  => true,
		'col'   => true,
		'embed' => true,
	);

	// Loop through allowed tags and compare for empty and normal tags
	foreach ( $allowed as $tag => $args ) {
		$preg = $args ? "{$tag}(?:\s.*?)?" : $tag;

		// Which walker to use based on the tag and arguments
		if ( isset( $empty[ $tag ] ) ) {
			array_walk( $content, 'bbp_encode_empty_callback', $preg );
		} else {
			array_walk( $content, 'bbp_encode_normal_callback', $preg );
		}
	}

	// Return the joined content array
	return implode( '', $content );
}

/** Code Callbacks ************************************************************/

/**
 * Callback to encode the tags in topic or reply content
 *
 * @since bbPress (r4641)
 *
 * @param array $matches
 * @return string
 */
function bbp_encode_callback( $matches = array() ) {

	// Trim inline code, not pre blocks (to prevent removing indentation)
	if ( '`' === $matches[1] ) {
		$content = trim( $matches[2] );
	} else {
		$content = $matches[2];
	}

	// Do some replacing
	$content = htmlspecialchars( $content, ENT_QUOTES );
	$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
	$content = preg_replace( "|\n\n\n+|", "\n\n", $content );
	$content = str_replace( '&amp;amp;', '&amp;', $content );
	$content = str_replace( '&amp;lt;', '&lt;', $content );
	$content = str_replace( '&amp;gt;', '&gt;', $content );

	// Wrap in code tags
	$content = '<code>' . $content . '</code>';

	// Wrap blocks in pre tags
	if ( '`' !== $matches[1] ) {
		$content = "\n<pre>" . $content . "</pre>\n";
	}

	return $content;
}

/**
 * Callback to decode the tags in topic or reply content
 *
 * @since bbPress (r4641)
 *
 * @param array $matches
 * @todo Experiment with _wp_specialchars()
 * @return string
 */
function bbp_decode_callback( $matches = array() ) {

	// Setup variables
	$trans_table = array_flip( get_html_translation_table( HTML_ENTITIES, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
	$amps        = array( '&#38;', '&#038;', '&amp;' );
	$single      = array( '&#39;', '&#039;' );
	$content     = $matches[2];
	$content     = strtr( $content, $trans_table );

	// Do the do
	$content = str_replace( '<br />', '<coded_br />', $content );
	$content = str_replace( '<p>', '<coded_p>', $content );
	$content = str_replace( '</p>', '</coded_p>', $content );
	$content = str_replace( $amps, '&', $content );
	$content = str_replace( $single, "'", $content );

	// Return content wrapped in code tags
	return '`' . $content . '`';
}

/**
 * Callback to replace empty HTML tags in a content string
 *
 * @since bbPress (r4641)
 *
 * @internal Used by bbp_encode_bad()
 * @param string $content
 * @param string $key Not used
 * @param string $preg
 */
function bbp_encode_empty_callback( &$content = '', $key = '', $preg = '' ) {
	if ( strpos( $content, '`' ) !== 0 ) {
		$content = preg_replace( "|&lt;({$preg})\s*?/*?&gt;|i", '<$1 />', $content );
	}
}

/**
 * Callback to replace normal HTML tags in a content string
 *
 * @since bbPress (r4641)
 *
 * @internal Used by bbp_encode_bad()
 * @param type $content
 * @param type $key
 * @param type $preg
 */
function bbp_encode_normal_callback( &$content = '', $key = '', $preg = '' ) {
	if ( strpos( $content, '`' ) !== 0 ) {
		$content = preg_replace( "|&lt;(/?{$preg})&gt;|i", '<$1>', $content );
	}
}

/** No Follow *****************************************************************/

/**
 * Catches links so rel=nofollow can be added (on output, not save)
 *
 * @since bbPress (r4865)
 * @param string $text Post text
 * @return string $text Text with rel=nofollow added to any links
 */
function bbp_rel_nofollow( $text = '' ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bbp_rel_nofollow_callback', $text );
}

/**
 * Adds rel=nofollow to a link
 *
 * @since bbPress (r4865)
 * @param array $matches
 * @return string $text Link with rel=nofollow added
 */
function bbp_rel_nofollow_callback( $matches = array() ) {
	$text = $matches[1];
	$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'" ), '', $text );

	// Extract URL from href.
	preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $match );

	$url_host      = ( isset( $match[0] ) && isset( $match[0][0] ) ? wp_parse_url( $match[0][0], PHP_URL_HOST ) : '' );
	$base_url_host = wp_parse_url( site_url(), PHP_URL_HOST );

	// If site link then nothing to do.
	if ( $url_host === $base_url_host || empty( $url_host ) ) {
		return "<a $text rel=\"nofollow\">";
		// Else open in new tab.
	} else {
		return "<a target='_blank' $text rel=\"nofollow\">";
	}
}

/** Make Clickable ************************************************************/

/**
 * Convert plaintext URI to HTML links.
 *
 * Converts URI, www and ftp, and email addresses. Finishes by fixing links
 * within links.
 *
 * This custom version of WordPress's make_clickable() skips links inside of
 * pre and code tags.
 *
 * @since bbPress (r4941)
 *
 * @param string $text Content to convert URIs.
 * @return string Content with converted URIs.
 */
function bbp_make_clickable( $text ) {
	$r               = '';
	$textarr         = preg_split( '/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // split out HTML tags
	$nested_code_pre = 0; // Keep track of how many levels link is nested inside <pre> or <code>

	foreach ( $textarr as $piece ) {

		if ( preg_match( '|^<code[\s>]|i', $piece ) || preg_match( '|^<pre[\s>]|i', $piece ) || preg_match( '|^<script[\s>]|i', $piece ) || preg_match( '|^<style[\s>]|i', $piece ) ) {
			$nested_code_pre++;
		} elseif ( $nested_code_pre && ( '</code>' === strtolower( $piece ) || '</pre>' === strtolower( $piece ) || '</script>' === strtolower( $piece ) || '</style>' === strtolower( $piece ) ) ) {
			$nested_code_pre--;
		}

		if ( $nested_code_pre || empty( $piece ) || ( $piece[0] === '<' && ! preg_match( '|^<\s*[\w]{1,20}+://|', $piece ) ) ) {
			$r .= $piece;
			continue;
		}

		// Long strings might contain expensive edge cases ...
		if ( 10000 < strlen( $piece ) ) {
			// ... break it up
			foreach ( _split_str_by_whitespace( $piece, 2100 ) as $chunk ) { // 2100: Extra room for scheme and leading and trailing paretheses
				if ( 2101 < strlen( $chunk ) ) {
					$r .= $chunk; // Too big, no whitespace: bail.
				} else {
					$r .= bbp_make_clickable( $chunk );
				}
			}
		} else {
			$ret = " {$piece} "; // Pad with whitespace to simplify the regexes
			$ret = apply_filters( 'bbp_make_clickable', $ret );
			$ret = substr( $ret, 1, -1 ); // Remove our whitespace padding.
			$r  .= $ret;
		}
	}

	// Cleanup of accidental links within links
	return preg_replace( '#(<a([ \r\n\t]+[^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $r );
}

/**
 * Make URLs clickable in content areas
 *
 * @since BuddyPress 2.6.0
 *
 * @param  string $text
 * @return string
 */
function bbp_make_urls_clickable( $text = '' ) {
	$url_clickable = '~
		([\\s(<.,;:!?])                                # 1: Leading whitespace, or punctuation
		(                                              # 2: URL
			[\\w]{1,20}+://                            # Scheme and hier-part prefix
			(?=\S{1,2000}\s)                           # Limit to URLs less than about 2000 characters long
			[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+     # Non-punctuation URL character
			(?:                                        # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
				[\'.,;:!?)]                            # Punctuation URL character
				[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
			)*
		)
		(\)?)                                          # 3: Trailing closing parenthesis (for parethesis balancing post processing)
	~xS';

	// The regex is a non-anchored pattern and does not have a single fixed starting character.
	// Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.
	return preg_replace_callback( $url_clickable, '_make_url_clickable_cb', $text );
}

/**
 * Make FTP clickable in content areas
 *
 * @since BuddyPress 2.6.0
 *
 * @see make_clickable()
 *
 * @param  string $text
 * @return string
 */
function bbp_make_ftps_clickable( $text = '' ) {
	return preg_replace_callback( '#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', '_make_web_ftp_clickable_cb', $text );
}

/**
 * Make emails clickable in content areas
 *
 * @since BuddyPress 2.6.0
 *
 * @see make_clickable()
 *
 * @param  string $text
 * @return string
 */
function bbp_make_emails_clickable( $text = '' ) {
	return preg_replace_callback( '#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $text );
}

/**
 * Make mentions clickable in content areas
 *
 * @since BuddyPress 2.6.0
 *
 * @see make_clickable()
 *
 * @param  string $text
 * @return string
 */
function bbp_make_mentions_clickable( $text = '' ) {
	return preg_replace_callback( '#([\s>])@([0-9a-zA-Z-_]+)#i', 'bbp_make_mentions_clickable_callback', $text );
}

/**
 * Callback to convert mention matchs to HTML A tag.
 *
 * @since BuddyPress 2.6.0
 *
 * @param array $matches Single Regex Match.
 *
 * @return string HTML A tag with link to user profile.
 */
function bbp_make_mentions_clickable_callback( $matches = array() ) {

	$user_id = bp_get_userid_from_mentionname( $matches[2] );

	// If not userid then return.
	if ( ! $user_id ) {
		return $matches[0];
	}

	// Get user; bail if not found
	$user = get_userdata( $user_id );
	if ( empty( $user ) || bbp_is_user_inactive( $user->ID ) ) {
		return $matches[0];
	}

	// Create the link to the user's profile
	$url    = bbp_get_user_profile_url( $user->ID );
	$anchor = '<a href="%1$s" rel="nofollow">@%2$s</a>';
	$link   = sprintf( $anchor, esc_url( $url ), esc_html( $matches[2] ) );

	return $matches[1] . $link;
}

/**
 * Convert mentions into links
 *
 * @since BuddyBoss 1.2.8
 *
 * @param string $data Content to convert mentions
 * @return string Filtered content
 */
function bbp_convert_mentions( $data ) {

	// Search mentions in the content.
	$usernames = bp_find_mentions_by_at_sign( array(), $data );

	// We have mentions!
	if ( ! empty( $usernames ) ) {
		foreach ( (array) $usernames as $user_id => $username ) {
			$pattern = '/(?<=[^A-Za-z0-9\_\/\.\-\*\+\=\%\$\#\?]|^)@' . preg_quote( $username, '/' ) . '\b(?!\/)/';
			$data = preg_replace( $pattern, "<a class='bp-suggestions-mention' href='" . bbp_get_user_profile_url( $user_id ) . "' rel='nofollow'>@$username</a>", $data );
		}

		// Temporary variable to avoid having to run bp_find_mentions_by_at_sign() again.
		buddypress()->forums->mentioned_users = $usernames;
	}

	return $data;
}

/**
 * Remove HTML tags from the content.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $content Content to filter.
 *
 * @return null|string|string[]
 */
function bbp_remove_html_tags( $content ) {
	// Removed html comments.
	$content = preg_replace( '/<!--(.|\s)*?-->/', '', $content );
	$content = preg_replace( '/&lt;!&#8211;(.|\s)*?&#8211;&gt;/', '', $content );

	return $content;
}
