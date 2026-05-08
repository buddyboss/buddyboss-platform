/**
 * BuddyBoss Admin Settings 2.0 - Pusher verify hooks
 *
 * Extends the shared bb_verify_popup field type (VerifyPopupField.js) with
 * Pusher-specific behaviour around the `bb-pusher-app-custom-cluster`
 * related field, which is conditionally rendered (only when the cluster
 * select is set to "custom").
 *
 * Why this lives outside VerifyPopupField:
 *
 * The shared component's `allFilled` check requires every entry in
 * `related_fields` to have a value. Pusher registers the custom-cluster name
 * field as a related field so its value is forwarded to the verify AJAX on
 * submit, but when cluster ≠ "custom" that field isn't rendered — so the
 * shared check fails and disables the Connect button even though the four
 * always-visible credentials are filled.
 *
 * The shared component cannot fix this generically: detecting "field is
 * conditionally hidden" by DOM-querying alone produces false positives on
 * the first render of every empty form (DOM not committed yet → all
 * related fields look hidden → button wrongly enables for reCAPTCHA, Zoom,
 * etc.). The conditional logic is Pusher-specific, so it lives here.
 *
 * Hook used: `bb_admin_verify_field_button_disabled` — already documented
 * as the extension point for per-integration disabled-state overrides.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { addFilter } from '@wordpress/hooks';

// Pusher's verify-popup field name (`'name' => '_bb_pusher_verify'` in
// buddyboss-platform-pro/includes/integrations/pusher/bb-pusher-settings.php).
var FIELD_NAME = '_bb_pusher_verify';
var NAMESPACE  = 'buddyboss/pusher-verify';

var CLUSTER_FIELD = 'bb-pusher-app-cluster';

// Always-visible credentials; cluster select is here too (it is itself a
// related field, never conditionally hidden).
var ALWAYS_REQUIRED = [
	'bb-pusher-app-id',
	'bb-pusher-app-key',
	'bb-pusher-app-secret',
	'bb-pusher-app-cluster',
];

/**
 * Read a field's value from the DOM, falling back to the form-state values
 * map when the element isn't (yet) in the DOM. Mirrors the shared
 * VerifyPopupField lookup so the override stays consistent with default
 * behaviour for the always-visible fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} name   Field `name` attribute.
 * @param {Object} values Current form values.
 * @return {string} Trimmed value or empty string.
 */
function readFieldValue( name, values ) {
	var el = document.querySelector(
		'input[name="' + name + '"], select[name="' + name + '"], textarea[name="' + name + '"]'
	);
	var value = el ? ( el.value || '' ) : '';
	if ( ! value ) {
		value = values && values[ name ] ? values[ name ] : '';
	}
	return String( value ).trim();
}

/**
 * Override Pusher's Connect button disabled state when cluster ≠ "custom".
 *
 * Default behaviour (`'connect' === buttonState && ! allFilled`) leaves the
 * button disabled because `bb-pusher-app-custom-cluster` is in
 * `related_fields` but isn't rendered. We recompute "all filled" against the
 * four always-visible credentials only, leaving the Pro registration's
 * `related_fields` array untouched (so the verify AJAX still receives the
 * custom-cluster value when it IS filled).
 *
 * When cluster = "custom", the shared check is correct: custom-cluster is
 * rendered and its emptiness should keep the button disabled.
 *
 * @since BuddyBoss [BBVERSION]
 */
addFilter(
	'bb_admin_verify_field_button_disabled',
	NAMESPACE,
	function ( isDisabled, field, allFilled, values ) {
		if ( ! field || FIELD_NAME !== field.name ) {
			return isDisabled;
		}

		var cluster = readFieldValue( CLUSTER_FIELD, values );
		if ( 'custom' === cluster ) {
			return isDisabled;
		}

		var pusherAllFilled = ALWAYS_REQUIRED.every( function ( rf ) {
			return !! readFieldValue( rf, values );
		} );

		return ! pusherAllFilled;
	}
);
