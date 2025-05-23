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
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const EditReadyLaunchHeader = ({ attributes, setAttributes }) => {
    const {
        showSearch,
        showMessages,
        showNotifications,
        showProfileMenu,
        darkMode,
    } = attributes;

    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Header Settings', 'buddyboss')} initialOpen={true}>
                    <ToggleControl
                        label={__('Show Search', 'buddyboss')}
                        checked={showSearch}
                        onChange={() => setAttributes({ showSearch: !showSearch })}
                    />
                    <ToggleControl
                        label={__('Show Messages', 'buddyboss')}
                        checked={showMessages}
                        onChange={() => setAttributes({ showMessages: !showMessages })}
                    />
                    <ToggleControl
                        label={__('Show Notifications', 'buddyboss')}
                        checked={showNotifications}
                        onChange={() => setAttributes({ showNotifications: !showNotifications })}
                    />
                    <ToggleControl
                        label={__('Show Profile Menu', 'buddyboss')}
                        checked={showProfileMenu}
                        onChange={() => setAttributes({ showProfileMenu: !showProfileMenu })}
                    />
                    <ToggleControl
                        label={__('Dark Mode', 'buddyboss')}
                        checked={darkMode}
                        onChange={() => setAttributes({ darkMode: !darkMode })}
                    />
                </PanelBody>
            </InspectorControls>
            <Disabled>
                <ServerSideRender
                    block="buddyboss/readylaunch-header"
                    attributes={attributes}
                />
            </Disabled>
        </div>
    );
};

export default EditReadyLaunchHeader; 