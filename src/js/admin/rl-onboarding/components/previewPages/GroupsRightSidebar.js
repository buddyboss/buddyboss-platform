export const GroupsRightSidebar = ({ formData = {} }) => {
    const { bb_rl_groups_sidebars } = formData;
    
    // Helper function to check if a widget should be shown
    const shouldShowWidget = (widgetId) => {
        // If no configuration exists, show all widgets by default
        if ( typeof bb_rl_groups_sidebars === 'undefined' ) {
            return true;
        }
        
        // Show widget if it's in the array
        return bb_rl_groups_sidebars.includes(widgetId);
    };

    // Define all widgets
    const widgets = [
        {
            id: 'about_group',
            component: (
                <div className="bb-rl-preview-widget" key="about_group">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">About group</h3>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-group-info">
                            <div className="bb-rl-preview-group-info-item">
                                <div className="bb-rl-preview-group-info-icon">
                                    <i className="bb-icons-rl-globe-simple"></i>
                                </div>
                                <div className="bb-rl-preview-group-info-content">
                                    <span className="bb-rl-preview-group-info-label">Public group</span>
                                    <span className="bb-rl-preview-group-info-description">Anyone can join the group</span>
                                </div>
                            </div>

                            <div className="bb-rl-preview-group-info-item">
                                <div className="bb-rl-preview-group-info-icon">
                                    <i className="bb-icons-rl-users"></i>
                                </div>
                                <div className="bb-rl-preview-group-info-content">
                                    <span className="bb-rl-preview-group-info-label">344 members</span>
                                    <span className="bb-rl-preview-group-info-description">Total members in the group</span>
                                </div>
                            </div>

                            <div className="bb-rl-preview-group-info-item">
                                <div className="bb-rl-preview-group-info-icon">
                                    <i className="bb-icons-rl-pulse"></i>
                                </div>
                                <div className="bb-rl-preview-group-info-content">
                                    <span className="bb-rl-preview-group-info-label">Active 1 hour ago</span>
                                    <span className="bb-rl-preview-group-info-description">Last post by any member</span>
                                </div>
                            </div>
                        </div>

                        <div className="bb-rl-preview-group-organizers">
                            <div className="bb-rl-preview-group-organizers-header">
                                <span className="bb-rl-preview-group-organizers-title">Organizers</span>
                                <div className="bb-rl-preview-group-organizers-avatars">
                                    <div className="bb-rl-preview-organizer-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Organizer" />
                                    </div>
                                    <div className="bb-rl-preview-organizer-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="Organizer" />
                                    </div>
                                    <div className="bb-rl-preview-organizer-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira.png`} alt="Organizer" />
                                    </div>
                                    <span className="bb-rl-preview-organizers-more">+2 more</span>
                                </div>
                            </div>
                        </div>

                        <div className="bb-rl-preview-group-description">
                            <p>Welcome to our UX Group! Dive into user-centric design. Share insights, ask questions, and collaborate. Let's shape the future of UX toget...</p>
                            <button className="bb-rl-preview-show-more-btn">Show more</button>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'group_members',
            component: (
                <div className="bb-rl-preview-widget" key="group_members">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Group members</h3>
                        <span className="bb-rl-preview-widget-link">See all</span>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-group-members">
                            <div className="bb-rl-preview-group-members-tabs">
                                <button className="bb-rl-preview-group-tab bb-rl-preview-group-tab-active">Active</button>
                                <button className="bb-rl-preview-group-tab">New</button>
                                <button className="bb-rl-preview-group-tab">Popular</button>
                            </div>

                            <div className="bb-rl-preview-group-members-list">
                                <div className="bb-rl-preview-group-member-item">
                                    <div className="bb-rl-preview-group-member-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Elena Mathew" />
                                    </div>
                                    <div className="bb-rl-preview-group-member-info">
                                        <span className="bb-rl-preview-group-member-name">Elena Mathew</span>
                                        <span className="bb-rl-preview-group-member-status">Active 2 minutes ago</span>
                                    </div>
                                </div>

                                <div className="bb-rl-preview-group-member-item">
                                    <div className="bb-rl-preview-group-member-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="John Muller" />
                                    </div>
                                    <div className="bb-rl-preview-group-member-info">
                                        <span className="bb-rl-preview-group-member-name">John Muller</span>
                                        <span className="bb-rl-preview-group-member-status">Active 5 minutes ago</span>
                                    </div>
                                </div>

                                <div className="bb-rl-preview-group-member-item">
                                    <div className="bb-rl-preview-group-member-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira.png`} alt="Mira Zai" />
                                    </div>
                                    <div className="bb-rl-preview-group-member-info">
                                        <span className="bb-rl-preview-group-member-name">Mira Zai</span>
                                        <span className="bb-rl-preview-group-member-status">Active 10 minutes ago</span>
                                    </div>
                                </div>

                                <div className="bb-rl-preview-group-member-item">
                                    <div className="bb-rl-preview-group-member-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Ros Taylor" />
                                    </div>
                                    <div className="bb-rl-preview-group-member-info">
                                        <span className="bb-rl-preview-group-member-name">Ros Taylor</span>
                                        <span className="bb-rl-preview-group-member-status">Active 15 minutes ago</span>
                                    </div>
                                </div>

                                <div className="bb-rl-preview-group-member-item">
                                    <div className="bb-rl-preview-group-member-avatar">
                                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Elena Mathew" />
                                    </div>
                                    <div className="bb-rl-preview-group-member-info">
                                        <span className="bb-rl-preview-group-member-name">Sarah Chen</span>
                                        <span className="bb-rl-preview-group-member-status">Active 20 minutes ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )
        }
    ];

    // Filter widgets based on configuration
    const visibleWidgets = widgets.filter(widget => shouldShowWidget(widget.id));

    return (
        <div className="bb-rl-preview-right-sidebar">
            {visibleWidgets.map(widget => widget.component)}
        </div>
    );
};