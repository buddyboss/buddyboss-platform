/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import editReadyLaunchHeader from './edit';

/**
 * Register block
 */
registerBlockType( metadata, {
    icon: {
        background: '#fff',
        foreground: '#d84800',
        src: 'admin-home',
    },
    edit: editReadyLaunchHeader,
} );
