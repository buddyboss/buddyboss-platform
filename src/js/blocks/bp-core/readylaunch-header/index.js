/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import editReadyLaunchHeader from './edit';
import metadata from './block.json';

/**
 * Register block
 */
registerBlockType(metadata, {
    apiVersion: 2,
    title: __('ReadyLaunch Header', 'buddyboss'),
    description: __('Add a customizable header for ReadyLaunch theme.', 'buddyboss'),
    category: 'buddyboss',
    icon: {
        background: '#fff',
        foreground: '#d84800',
        src: 'menu',
    },
    supports: {
        html: false,
        align: ['wide', 'full'],
        spacing: {
            margin: true,
            padding: true,
            blockGap: true,
        },
        color: {
            background: true,
            text: true,
            link: true,
        },
        typography: {
            fontSize: true,
            lineHeight: true,
        },
    },
    attributes: {
        layout: {
            type: 'string',
            default: 'default',
        },
        showSearch: {
            type: 'boolean',
            default: true,
        },
        showNotifications: {
            type: 'boolean',
            default: true,
        },
        showMessages: {
            type: 'boolean',
            default: true,
        },
    },
    edit: editReadyLaunchHeader,
    save: function Save({ attributes }) {
        const blockProps = useBlockProps.save();
        const { layout, showSearch, showNotifications, showMessages } = attributes;

        return (
            <div {...blockProps}>
                <div className={`