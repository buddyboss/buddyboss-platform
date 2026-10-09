/**
 * BuddyBoss shared admin-common layer — public API.
 *
 * Built once (BUILD_TARGET=common) and exposed on window.bbAdminCommon via the
 * webpack `library` output. App bundles import `@bb/admin-common`, which is
 * externalized to this global (see webpack.config.js), so the layer ships once.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

export { BBAdminHeader } from './components/BBAdminHeader';

// Knowledge Base modal subsystem — shared so every admin app shows the same
// in-app help experience. Consumers wrap their tree in <KbProvider>, call
// useKb() to open it, and mount <KnowledgeBaseModal />.
export { KbProvider, useKb } from './context/KbContext';
export { default as KnowledgeBaseModal } from './components/knowledge-base/KnowledgeBaseModal';
export { sanitizeKbArticle, safeImageUrl } from './utils/sanitizeKbArticle';
// KB taxonomy + curated-overrides helpers — consumed by the Settings Help/KB
// search screen (HelpScreen), which lives in the settings bundle and reads
// them from here now that the KB subsystem moved into the shared layer.
export { getTaxonomy, clearTaxonomy } from './components/knowledge-base/taxonomyCache';
export { getCuratedOverrides } from './components/knowledge-base/curatedOverrides';

// Shared HTML/URL sanitizer (DOMParser allowlist) — used by Settings and the
// Integrations marketplace; lives here so it ships once.
export { sanitizeHtml, safeUrl, sanitizeCustomColumns } from './utils/sanitize';
