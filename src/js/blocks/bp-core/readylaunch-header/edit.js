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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const editReadyLaunchHeader = ( { attributes, setAttributes } ) => {
    const blockProps = useBlockProps();
    const { headerText } = attributes;

    return (
        <div { ...blockProps }>
            <InspectorControls>
                <PanelBody title={ __( 'Settings', 'buddyboss' ) } initialOpen={ true }>
                    <TextControl
                        label={ __( 'Header Text', 'buddyboss' ) }
                        value={ headerText }
                        onChange={ ( text ) => {
                            setAttributes( { headerText: text } );
                        } }
                    />
                </PanelBody>
            </InspectorControls>
            <Disabled>
                <ServerSideRender block="buddyboss/readylaunch-header" attributes={ attributes } />
            </Disabled>
        </div>
    );
};

export default editReadyLaunchHeader; 