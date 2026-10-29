import { buildRegisteredFieldPayload, getRegisteredFieldPostValue } from '../format';

// An empty array must stay an empty array (FormData then sends nothing and
// save_fields_data() skips the field) unless the field opts in with
// extra_data.send_empty_as_clear. Sending '' for every empty array made the
// legacy bridge store [''] in WP Fusion restriction settings and made the
// course pickers insert course id 0 (PROD-10260).
describe( 'getRegisteredFieldPostValue', () => {
	var plain = { id: 'courses', type: 'ajax_multiselect', extra_data: {} };
	var optIn = { id: 'wpf_group_tag_link', type: 'ajax_multiselect', extra_data: { send_empty_as_clear: true } };

	test( 'null and undefined become an empty string', () => {
		expect( getRegisteredFieldPostValue( plain, null ) ).toBe( '' );
		expect( getRegisteredFieldPostValue( plain, undefined ) ).toBe( '' );
	} );

	test( 'empty array is kept for fields that do not opt in', () => {
		expect( getRegisteredFieldPostValue( plain, [] ) ).toEqual( [] );
		expect( getRegisteredFieldPostValue( { id: 'x' }, [] ) ).toEqual( [] );
	} );

	test( 'empty array becomes an empty string for opted-in fields', () => {
		expect( getRegisteredFieldPostValue( optIn, [] ) ).toBe( '' );
	} );

	test( 'non-empty arrays and scalars pass through unchanged', () => {
		expect( getRegisteredFieldPostValue( optIn, [ 'tag_a' ] ) ).toEqual( [ 'tag_a' ] );
		expect( getRegisteredFieldPostValue( plain, [ 5 ] ) ).toEqual( [ 5 ] );
		expect( getRegisteredFieldPostValue( plain, '0' ) ).toBe( '0' );
		expect( getRegisteredFieldPostValue( plain, 0 ) ).toBe( 0 );
	} );
} );

describe( 'buildRegisteredFieldPayload', () => {
	test( 'empty array fields keep [] unless opted in, on both payload keys', () => {
		var fields = [
			{ id: 'legacy_wpf_allow_tags', type: 'ajax_multiselect', extra_data: {} },
			{ id: 'cleared', type: 'ajax_multiselect', extra_data: { send_empty_as_clear: true } },
			{ id: 'ro', type: 'text', readonly: true },
		];
		var payload = buildRegisteredFieldPayload( fields, { legacy_wpf_allow_tags: [], cleared: [], ro: 'x' }, 0 );

		expect( payload.legacy_wpf_allow_tags ).toEqual( [] );
		expect( payload.registered_field_legacy_wpf_allow_tags ).toEqual( [] );
		expect( payload.cleared ).toBe( '' );
		expect( payload.registered_field_cleared ).toBe( '' );
		expect( payload ).not.toHaveProperty( 'ro' );
	} );
} );
