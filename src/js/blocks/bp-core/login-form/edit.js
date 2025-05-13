/**
 * WordPress dependencies.
 */
import {
    InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const editLoginForm = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { title, forgotPwdLink } = attributes;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddyboss' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Title', 'buddyboss' ) }
						value={ title }
						onChange={ ( text ) => {
							setAttributes( { title: text } );
						} }
					/>
					<ToggleControl
						label={ __( 'Include the link to reset the user password', 'buddyboss' ) }
						checked={ !! forgotPwdLink }
						onChange={ () => {
							setAttributes( { forgotPwdLink: ! forgotPwdLink } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="buddyboss/login-form" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editLoginForm;
