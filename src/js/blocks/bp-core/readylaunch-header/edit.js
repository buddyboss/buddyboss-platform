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
    SelectControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const editReadyLaunchHeader = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    const { layout, showSearch, showNotifications, showMessages } = attributes;

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Header Settings', 'buddyboss')} initialOpen={true}>
                    <SelectControl
                        label={__('Layout', 'buddyboss')}
                        value={layout}
                        options={[
                            { label: __('Default', 'buddyboss'), value: 'default' },
                            { label: __('Centered', 'buddyboss'), value: 'centered' },
                            { label: __('Minimal', 'buddyboss'), value: 'minimal' },
                        ]}
                        onChange={(value) => setAttributes({ layout: value })}
                    />
                    <ToggleControl
                        label={__('Show Search', 'buddyboss')}
                        checked={showSearch}
                        onChange={() => setAttributes({ showSearch: !showSearch })}
                    />
                    <ToggleControl
                        label={__('Show Notifications', 'buddyboss')}
                        checked={showNotifications}
                        onChange={() => setAttributes({ showNotifications: !showNotifications })}
                    />
                    <ToggleControl
                        label={__('Show Messages', 'buddyboss')}
                        checked={showMessages}
                        onChange={() => setAttributes({ showMessages: !showMessages })}
                    />
                </PanelBody>
            </InspectorControls>
            <Disabled>
                <ServerSideRender block="buddyboss/readylaunch-header" attributes={attributes} />
            </Disabled>
        </div>
    );
};

export default editReadyLaunchHeader; 