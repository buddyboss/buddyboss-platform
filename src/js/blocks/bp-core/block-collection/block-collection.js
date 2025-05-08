/**
 * WordPress dependencies.
 */
import { registerBlockCollection } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockCollection( 'buddyboss', {
	title: __( 'BuddyBoss', 'buddyboss' ),
	icon: '',
} );
