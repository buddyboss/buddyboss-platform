import { kbReducer, INITIAL_STATE } from '../KbContext';

const base = () => ( { ...INITIAL_STATE, expandedSubcategories: new Set() } );

test( 'open with no options is backwards-compatible (isOpen only)', () => {
	const start = { ...base(), view: 'category', activeCategorySlug: 'x', activeArticleSlug: 'a' };
	const next = kbReducer( start, { type: 'open' } );
	expect( next.isOpen ).toBe( true );
	expect( next.view ).toBe( 'category' );
	expect( next.activeCategorySlug ).toBe( 'x' );
	expect( next.rootCategory ).toBe( null );
} );

test( 'open with rootCategory + resetToLanding is atomic', () => {
	const start = { ...base(), view: 'category', activeCategorySlug: 'x', activeArticleSlug: 'a', rootCategory: 'old' };
	const next = kbReducer( start, { type: 'open', rootCategory: 'membership', resetToLanding: true } );
	expect( next.isOpen ).toBe( true );
	expect( next.rootCategory ).toBe( 'membership' );
	expect( next.view ).toBe( 'landing' );
	expect( next.activeCategorySlug ).toBe( null );
	expect( next.activeArticleSlug ).toBe( null );
	expect( next.expandedSubcategories.size ).toBe( 0 );
} );

test( 'close does not clear rootCategory', () => {
	const start = { ...base(), isOpen: true, rootCategory: 'membership' };
	const next = kbReducer( start, { type: 'close' } );
	expect( next.isOpen ).toBe( false );
	expect( next.rootCategory ).toBe( 'membership' );
} );

test( 'INITIAL_STATE has rootCategory null', () => {
	expect( INITIAL_STATE.rootCategory ).toBe( null );
} );
