import { Header } from './Header';
import { LeftSidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';
import { Members } from './Members';
import { MembersRightSidebar } from './MembersRightSidebar';
import { Group } from './Group';
import { GroupsRightSidebar } from './GroupsRightSidebar';
import { GroupHeadBar } from './GroupHeadBar';

export const PreviewPages = ({ page = 'groups', formData = {} }) => {
    let mainFeed = <Feed />;
    let feedRightSidebar = <FeedRightSidebar formData={formData} />;
    if (page === 'members') {
        mainFeed = <Members />;
        feedRightSidebar = <MembersRightSidebar formData={formData} />;
    } else if (page === 'groups') {
        mainFeed = <Group />;
        feedRightSidebar = <GroupsRightSidebar formData={formData} />;
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
                <LeftSidebar sideMenuItems={formData.bb_rl_side_menu || []} customLinks={formData.bb_rl_custom_links || []} />
                <div className="bb-rl-preview-main-content">
                    {
                        page === 'groups' && (
                            <GroupHeadBar />
                        )
                    }
                    <div className="bb-rl-preview-main">
                        {mainFeed}
                    </div>
                    {feedRightSidebar}
                </div>
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
