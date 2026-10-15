import { resolveRootParentId } from '../landingScope';

const terms = [
	{ id: 10, parent: 0, slug: 'membership', name: 'Membership' },
	{ id: 11, parent: 10, slug: 'billing', name: 'Billing' },
	{ id: 20, parent: 0, slug: 'courses', name: 'Courses' },
];

test( 'unset root → 0 (full KB)', () => {
	expect( resolveRootParentId( terms, null ) ).toBe( 0 );
	expect( resolveRootParentId( terms, '' ) ).toBe( 0 );
	expect( resolveRootParentId( terms, undefined ) ).toBe( 0 );
} );

test( 'valid root → that term id', () => {
	expect( resolveRootParentId( terms, 'membership' ) ).toBe( 10 );
} );

test( 'invalid root → 0 (full KB fallback, R5)', () => {
	expect( resolveRootParentId( terms, 'does-not-exist' ) ).toBe( 0 );
	expect( console ).toHaveWarned();
} );
