<?php
/**
 * Tests for bp_core_replace_tokens_in_text().
 *
 * @package BuddyBoss\Tests
 */

/**
 * Email token replacement in text.
 *
 * @group core
 * @group emails
 */
class BB_Tests_Core_Functions_BpCoreReplaceTokensInText extends BP_UnitTestCase {

	/**
	 * Tokens used by every test.
	 *
	 * @return array
	 */
	protected function get_tokens() {
		return array(
			'message.url'   => 'http://example.org/members/admin/messages/view/42/',
			'user-site.url' => 'https://site.example.org/',
			'site.domain'   => 'example.org',
			'sender.name'   => 'A&B',
		);
	}

	/**
	 * The scheme the link dialog adds in front of a URL token is removed.
	 */
	public function test_link_dialog_scheme_is_removed_before_a_url_token() {
		$text = '<a href="http://{{{message.url}}}">View message</a>';

		$this->assertSame(
			'<a href="http://example.org/members/admin/messages/view/42/">View message</a>',
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}

	/**
	 * Works for https, single quotes, spaces around = and {{escaped}} tokens.
	 */
	public function test_scheme_is_removed_for_https_single_quotes_and_escaped_tokens() {
		$text = "<a href='https://{{message.url}}'>x</a> <a href = \"HTTP://{{{user-site.url}}}\">y</a>";

		$this->assertSame(
			"<a href='http://example.org/members/admin/messages/view/42/'>x</a> <a href = \"https://site.example.org/\">y</a>",
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}

	/**
	 * A link to a bare URL token is replaced as before.
	 */
	public function test_bare_url_token_is_unchanged() {
		$text = '<a href="{{{message.url}}}">View message</a>';

		$this->assertSame(
			'<a href="http://example.org/members/admin/messages/view/42/">View message</a>',
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}

	/**
	 * A scheme in front of a token that isn't a full URL is kept.
	 */
	public function test_scheme_is_kept_when_the_token_is_not_a_full_url() {
		$text = '<a href="http://{{{site.domain}}}/about">About</a>';

		$this->assertSame(
			'<a href="http://example.org/about">About</a>',
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}

	/**
	 * Only links are changed, not plain text.
	 */
	public function test_text_outside_a_link_is_not_changed() {
		$text = 'Open http://{{{message.url}}} to reply.';

		$this->assertSame(
			'Open http://http://example.org/members/admin/messages/view/42/ to reply.',
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}

	/**
	 * Unknown tokens are left as they are.
	 */
	public function test_unknown_token_is_left_alone() {
		$text = '<a href="http://{{{unknown.url}}}">x</a> {{sender.name}}';

		$this->assertSame(
			'<a href="http://{{{unknown.url}}}">x</a> A&amp;B',
			bp_core_replace_tokens_in_text( $text, $this->get_tokens() )
		);
	}
}
