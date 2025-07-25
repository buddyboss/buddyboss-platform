export const FeedRightSidebar = () => {
    return (
        <div className="bb-rl-preview-right-sidebar">
            {/* Updates Widget */}
            <div className="bb-rl-preview-widget">
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

            {/* Recently Active Members Widget */}
            <div className="bb-rl-preview-widget">
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

            {/* Recent Blog Posts Widget */}
            <div className="bb-rl-preview-widget">
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
        </div>
    );
};