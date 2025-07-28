export const FeedRightSidebar = ({ formData = {} }) => {
    const { bb_rl_activity_sidebars = [] } = formData;
    
    // Helper function to check if a widget is enabled
    const isWidgetEnabled = (widgetId) => {
        // If no configuration exists, show all widgets by default
        if (!bb_rl_activity_sidebars || bb_rl_activity_sidebars.length === 0) {
            return true;
        }
        
        const widget = bb_rl_activity_sidebars.find(item => item.id === widgetId);
        return widget ? widget.enabled : false;
    };

    // Helper function to get widget order
    const getWidgetOrder = (widgetId) => {
        // If no configuration exists, use default order
        if (!bb_rl_activity_sidebars || bb_rl_activity_sidebars.length === 0) {
            const defaultOrders = {
                'complete_profile': 1,
                'latest_updates': 2, 
                'active_members': 3,
                'recent_blog_posts': 4
            };
            return defaultOrders[widgetId] || 999;
        }
        
        const widget = bb_rl_activity_sidebars.find(item => item.id === widgetId);
        return widget ? widget.order : 999; // Default high order for widgets not in config
    };

    // Define all widgets with their configurations
    const widgets = [
        {
            id: 'complete_profile',
            order: getWidgetOrder('complete_profile'),
            enabled: isWidgetEnabled('complete_profile'),
            component: (
                <div className="bb-rl-preview-widget" key="complete_profile">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Complete your profile</h3>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-profile-completion">
                            <div className="bb-rl-preview-profile-completion-header">
                                <div className="bb-rl-preview-profile-completion-percent">64% <span>Complete</span></div>
                                <div className="bb-rl-preview-profile-progress-bar">
                                    <div className="bb-rl-preview-profile-progress-fill" style={{ width: '64%' }}></div>
                                </div>
                            </div>
                            <div className="bb-rl-preview-profile-completion-list">
                                <div className="bb-rl-preview-profile-completion-item">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">General Information</span>
                                    <span className="bb-rl-preview-profile-completion-count">5/9</span>
                                </div>
                                <div className="bb-rl-preview-profile-completion-item bb-rl-preview-profile-completion-item-completed">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-check-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">Profile Photo</span>
                                    <span className="bb-rl-preview-profile-completion-count">1/1</span>
                                </div>
                                <div className="bb-rl-preview-profile-completion-item bb-rl-preview-profile-completion-item-completed">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-check-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">Cover Photo</span>
                                    <span className="bb-rl-preview-profile-completion-count">1/1</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'latest_updates',
            order: getWidgetOrder('latest_updates'),
            enabled: isWidgetEnabled('latest_updates'),
            component: (
                <div className="bb-rl-preview-widget" key="latest_updates">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Updates</h3>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-update-item">
                            <div className="bb-rl-preview-update-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Elena Mathew" />
                            </div>
                            <div className="bb-rl-preview-update-content">
                                <span className="bb-rl-preview-update-text">Elena Mathew posted an update</span>
                                <span className="bb-rl-preview-update-time">2 minutes ago</span>
                            </div>
                        </div>

                        <div className="bb-rl-preview-update-item">
                            <div className="bb-rl-preview-update-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="John Muller" />
                            </div>
                            <div className="bb-rl-preview-update-content">
                                <span className="bb-rl-preview-update-text">John Muller posted an update</span>
                                <span className="bb-rl-preview-update-time">32 minutes ago</span>
                            </div>
                        </div>

                        <div className="bb-rl-preview-update-item">
                            <div className="bb-rl-preview-update-avatar bb-rl-preview-update-avatar-group">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Ros" />
                            </div>
                            <div className="bb-rl-preview-update-content">
                                <span className="bb-rl-preview-update-text"><strong>Ros Taylor</strong> posted an update</span>
                                <span className="bb-rl-preview-update-time">1 hour ago</span>
                            </div>
                        </div>

                        <div className="bb-rl-preview-update-item">
                            <div className="bb-rl-preview-update-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Elena Mathew" />
                            </div>
                            <div className="bb-rl-preview-update-content">
                                <span className="bb-rl-preview-update-text">Elena Mathew reposted an update</span>
                                <span className="bb-rl-preview-update-time">6 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'active_members',
            order: getWidgetOrder('active_members'),
            enabled: isWidgetEnabled('active_members'),
            component: (
                <div className="bb-rl-preview-widget" key="active_members">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Recently active members</h3>
                        <span className="bb-rl-preview-widget-link">See all</span>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-members-grid">
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Member" />
                            </div>
                            <div className="bb-rl-preview-member-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="Member" />
                            </div>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'recent_blog_posts',
            order: getWidgetOrder('recent_blog_posts'),
            enabled: isWidgetEnabled('recent_blog_posts'),
            component: (
                <div className="bb-rl-preview-widget" key="recent_blog_posts">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Recent blog posts</h3>
                        <span className="bb-rl-preview-widget-link">See all</span>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-blog-post">
                            <div className="bb-rl-preview-blog-image">
                                <div className="bb-rl-preview-blog-image-placeholder"></div>
                            </div>
                            <div className="bb-rl-preview-blog-content">
                                <h4 className="bb-rl-preview-blog-title">Travel Adventures on a Budget</h4>
                                <span className="bb-rl-preview-blog-time">5 hours ago</span>
                            </div>
                        </div>

                        <div className="bb-rl-preview-blog-post">
                            <div className="bb-rl-preview-blog-image">
                                <div className="bb-rl-preview-blog-image-placeholder bb-rl-preview-blog-image-red"></div>
                            </div>
                            <div className="bb-rl-preview-blog-content">
                                <h4 className="bb-rl-preview-blog-title">Travel Adventures on a Budget</h4>
                                <span className="bb-rl-preview-blog-time">5 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            )
        }
    ];

    // Filter enabled widgets and sort by order
    const enabledWidgets = widgets
        .filter(widget => widget.enabled)
        .sort((a, b) => a.order - b.order);

    return (
        <div className="bb-rl-preview-right-sidebar">
            {enabledWidgets.map(widget => widget.component)}
        </div>
    );
};