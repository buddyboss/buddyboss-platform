import { Header } from './Header';
import { LeftSidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';
import { Members } from './Members';
import { MembersRightSidebar } from './MembersRightSidebar';

export const PreviewPages = ({ page = 'activity', formData = {} }) => {
    let mainFeed = <Feed />;
    let feedRightSidebar = <FeedRightSidebar />;
    if (page === 'members') {
        mainFeed = <Members />;
        feedRightSidebar = <MembersRightSidebar />;
    }

    const previewMode = formData.bb_rl_theme_mode || 'light';
    const primaryColor = formData.bb_rl_color_light || formData.brand_colors || '#e57e3a';

    const pageView = (page = 'activity', previewMode = 'light') => (
        <div
            className={`bb-rl-preview-${page} bb-rl-preview-theme-${previewMode}`}
            style={{ '--bb-rl-preview-primary-color': primaryColor }}
        >
            <Header formData={formData} previewMode={previewMode} />
            <div className="bb-rl-preview-content">
                <LeftSidebar />
                <div className="bb-rl-preview-main">
                    {mainFeed}
                </div>
                {feedRightSidebar}
            </div>
        </div>
    );

    const splitView = (page = 'activity') => (
        <div className="bb-rl-preview-split-view">
            <div className="bb-rl-preview-split-view-left">
                {pageView(page, 'light')}
            </div>
            <div className="bb-rl-preview-split-view-right">
                {pageView(page, 'dark')}
            </div>
        </div>
    );

    if (previewMode === 'choice') {
        return splitView(page);
    } else {
        return pageView(page, previewMode);
    }
};
