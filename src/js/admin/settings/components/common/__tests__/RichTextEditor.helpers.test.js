import {
	addLinkModalCommand,
	getRichTextEditorContent,
	getRichTextEditorHtml,
	stripLinkEditorAttributes,
} from '../RichTextEditor';

// The Settings 2.0 editors read their value through these helpers on Save.
// The Code tab hides TinyMCE, so its own getContent() is stale there, and the
// wplink plugin's editing attributes must never be stored (PROD-10488).

var FLAGGED = '<p><a href="http://{{{message.url}}}" data-wplink-url-error="true">Read</a> <a data-wplink-edit="true" href="https://example.org">x</a></p>';
var CLEAN = '<p><a href="http://{{{message.url}}}">Read</a> <a href="https://example.org">x</a></p>';

function mockEditor( options ) {
	var editor = {
		id: options.id || 'ed',
		hidden: !! options.hidden,
		content: options.content,
		textarea: options.textarea,
		isHidden: function () {
			return editor.hidden;
		},
		load: jest.fn( function () {
			editor.content = editor.textarea;
		} ),
		getContent: jest.fn( function () {
			return editor.content;
		} ),
	};
	return editor;
}

afterEach( function () {
	delete window.tinymce;
	delete window.wp;
	delete window.wpLink;
} );

describe( 'stripLinkEditorAttributes', () => {
	test( 'removes the wplink edit mark and broken-link flag from links', () => {
		expect( stripLinkEditorAttributes( FLAGGED ) ).toBe( CLEAN );
	} );

	test( 'leaves other content and non-strings unchanged', () => {
		expect( stripLinkEditorAttributes( CLEAN ) ).toBe( CLEAN );
		expect( stripLinkEditorAttributes( '<p data-wplink-edit="true">x</p>' ) ).toBe( '<p data-wplink-edit="true">x</p>' );
		expect( stripLinkEditorAttributes( undefined ) ).toBeUndefined();
		expect( stripLinkEditorAttributes( '' ) ).toBe( '' );
	} );
} );

describe( 'getRichTextEditorHtml', () => {
	test( 'Visual tab: returns the TinyMCE HTML without loading the textarea', () => {
		var editor = mockEditor( { content: '<p>Visual</p>', textarea: 'Stale' } );
		window.tinymce = { get: jest.fn( () => editor ) };

		expect( getRichTextEditorHtml( 'ed', 'fallback' ) ).toBe( '<p>Visual</p>' );
		expect( window.tinymce.get ).toHaveBeenCalledWith( 'ed' );
		expect( editor.load ).not.toHaveBeenCalled();
	} );

	test( 'Code tab: loads the textarea first, so a hand-typed link is kept', () => {
		var editor = mockEditor( {
			hidden: true,
			content: '<p>Stale</p>',
			textarea: '<p>Text <a href="https://example.org">link</a></p>',
		} );
		window.tinymce = { get: () => editor };

		expect( getRichTextEditorHtml( 'ed', 'fallback' ) ).toBe( '<p>Text <a href="https://example.org">link</a></p>' );
		expect( editor.load ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'strips the wplink attributes from the editor content', () => {
		window.tinymce = { get: () => mockEditor( { content: FLAGGED } ) };

		expect( getRichTextEditorHtml( 'ed', '' ) ).toBe( CLEAN );
	} );

	test( 'no editor: returns the fallback, without the wplink attributes', () => {
		window.tinymce = { get: () => null };
		expect( getRichTextEditorHtml( 'ed', FLAGGED ) ).toBe( CLEAN );

		delete window.tinymce;
		expect( getRichTextEditorHtml( 'ed', '<p>x</p>' ) ).toBe( '<p>x</p>' );
		expect( getRichTextEditorHtml( 'ed', undefined ) ).toBeUndefined();
	} );
} );

describe( 'getRichTextEditorContent', () => {
	test( 'returns wp.editor.getContent(), which covers the Code tab', () => {
		window.wp = { editor: { getContent: jest.fn( () => 'Line one\n\nLine <a href="https://example.org">two</a>' ), removep: jest.fn() } };

		expect( getRichTextEditorContent( 'ed', '<p>stale</p>' ) ).toBe( 'Line one\n\nLine <a href="https://example.org">two</a>' );
		expect( window.wp.editor.getContent ).toHaveBeenCalledWith( 'ed' );
		expect( window.wp.editor.removep ).not.toHaveBeenCalled();
	} );

	test( 'editor not mounted: runs removep() on an HTML fallback, without the wplink attributes', () => {
		window.wp = {
			editor: {
				getContent: () => undefined,
				removep: jest.fn( ( html ) => html.replace( /<\/?p>/g, '' ) ),
			},
		};

		expect( getRichTextEditorContent( 'ed', FLAGGED ) ).toBe( '<a href="http://{{{message.url}}}">Read</a> <a href="https://example.org">x</a>' );
		expect( window.wp.editor.removep ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'plain-text fallback and missing wp.editor return the fallback', () => {
		window.wp = { editor: { getContent: () => undefined, removep: jest.fn() } };
		expect( getRichTextEditorContent( 'ed', 'plain' ) ).toBe( 'plain' );
		expect( window.wp.editor.removep ).not.toHaveBeenCalled();

		delete window.wp;
		expect( getRichTextEditorContent( 'ed', FLAGGED ) ).toBe( CLEAN );
	} );
} );

describe( 'addLinkModalCommand', () => {
	function mockSetupEditor() {
		var handlers = {};
		var editor = {
			id: 'ed',
			on: jest.fn( ( name, cb ) => {
				handlers[ name ] = cb;
			} ),
			addCommand: jest.fn(),
			dom: {
				getParent: jest.fn( () => null ),
				setAttribs: jest.fn(),
			},
			selection: {
				getStart: () => ( {} ),
				getContent: () => 'plain text',
				select: jest.fn(),
			},
			$: jest.fn( () => ( { attr: jest.fn() } ) ),
			init: () => handlers.init(),
		};
		return editor;
	}

	test( 'replaces WP_Link on init so the link button and Cmd+K open wpLink', () => {
		var editor = mockSetupEditor();
		window.wpLink = { open: jest.fn() };

		addLinkModalCommand( editor );
		expect( editor.addCommand ).not.toHaveBeenCalled();

		editor.init();
		expect( editor.addCommand ).toHaveBeenCalledWith( 'WP_Link', expect.any( Function ) );

		editor.addCommand.mock.calls[ 0 ][ 1 ]();
		expect( window.wpLink.open ).toHaveBeenCalledWith( 'ed' );
	} );

	test( 'marks the link under the cursor so wpLink edits it', () => {
		var editor = mockSetupEditor();
		var link = { nodeName: 'A' };
		editor.dom.getParent = jest.fn( () => link );
		window.wpLink = { open: jest.fn() };

		addLinkModalCommand( editor );
		editor.init();
		editor.addCommand.mock.calls[ 0 ][ 1 ]();

		expect( editor.dom.setAttribs ).toHaveBeenCalledWith( link, { 'data-wplink-edit': true } );
		expect( window.wpLink.open ).toHaveBeenCalledWith( 'ed' );
	} );

	test( 'keeps the core command when wpLink is not loaded', () => {
		var editor = mockSetupEditor();

		addLinkModalCommand( editor );
		editor.init();

		expect( editor.addCommand ).not.toHaveBeenCalled();
	} );
} );
