export const MembersRightSidebar = () => {
    return (
        <div className="bb-rl-preview-right-sidebar">
            {/* About Widget */}
            <div className="bb-rl-preview-widget">
                <div className="bb-rl-preview-widget-header">
                    <h3 className="bb-rl-preview-widget-title">About</h3>
                </div>
                <div className="bb-rl-preview-widget-content">
                    <div className="bb-rl-preview-profile-fields">
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">First Name</span>
                            <span className="bb-rl-preview-field-value">John</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Last Name</span>
                            <span className="bb-rl-preview-field-value">Muller</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Gender</span>
                            <span className="bb-rl-preview-field-value">Male</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Contact Widget */}
            <div className="bb-rl-preview-widget">
                <div className="bb-rl-preview-widget-header">
                    <h3 className="bb-rl-preview-widget-title">Contact</h3>
                </div>
                <div className="bb-rl-preview-widget-content">
                    <div className="bb-rl-preview-profile-fields">
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Phone</span>
                            <span className="bb-rl-preview-field-value">+1 634 568 908</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Address</span>
                            <span className="bb-rl-preview-field-value">456 E Main Street, New York, NY 10001, USA</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Email</span>
                            <span className="bb-rl-preview-field-value bb-rl-preview-field-link">johnmuller@xmail.com</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Website / Portfolio</span>
                            <span className="bb-rl-preview-field-value bb-rl-preview-field-link">johnmuller.com</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Work Widget */}
            <div className="bb-rl-preview-widget">
                <div className="bb-rl-preview-widget-header">
                    <h3 className="bb-rl-preview-widget-title">Work</h3>
                </div>
                <div className="bb-rl-preview-widget-content">
                    <div className="bb-rl-preview-profile-fields">
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Designation</span>
                            <span className="bb-rl-preview-field-value">UX Writer</span>
                        </div>
                        <div className="bb-rl-preview-profile-field">
                            <span className="bb-rl-preview-field-label">Company</span>
                            <span className="bb-rl-preview-field-value">NitroFox Digital</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Network Widget */}
            <div className="bb-rl-preview-widget">
                <div className="bb-rl-preview-widget-header">
                    <h3 className="bb-rl-preview-widget-title">Network</h3>
                </div>
                <div className="bb-rl-preview-widget-content">
                    <div className="bb-rl-preview-network-tabs">
                        <div className="bb-rl-preview-network-tab bb-rl-preview-network-tab-active">
                            <span>Followers (31)</span>
                        </div>
                        <div className="bb-rl-preview-network-tab">
                            <span>Following (12)</span>
                        </div>
                    </div>
                    <div className="bb-rl-preview-network-grid">
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Network Member" />
                        </div>
                        <div className="bb-rl-preview-network-avatar">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Network Member" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};
