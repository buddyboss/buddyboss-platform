const fs = require('fs');
const { execSync } = require('child_process');

const potFilePath = 'src/languages/buddyboss.pot';

try {
    // Read the current POT file
    let potContent = fs.readFileSync(potFilePath, 'utf8');

    // Get current version from package.json
    const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    const bbVersion = packageJson.BBVersion || '2.8.61';

    // Get WP-CLI version for X-Generator
    let wpCliVersion = 'WP-CLI';
    try {
        wpCliVersion = execSync('wp cli version --skip-plugins --skip-themes', { encoding: 'utf8' }).trim();
    } catch (error) {
        console.log('Could not detect WP-CLI version, using default');
    }

    // Get current date in the required format
    const now = new Date();
    const potCreationDate = now.toISOString().replace('T', ' ').replace(/\.\d{3}Z$/, '+00:00');

    // Define the correct header format to match grunt-wp-i18n output
    const correctHeaders = `# Copyright (C) ${now.getFullYear()} BuddyBoss
# This file is distributed under the GPLv2 or later (license.txt).
msgid ""
msgstr ""
"Project-Id-Version: BuddyBoss Platform ${bbVersion}\\n"
"Report-Msgid-Bugs-To: https://www.buddyboss.com/contact/\\n"
"POT-Creation-Date: ${potCreationDate}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=utf-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"PO-Revision-Date: ${now.getFullYear()}-MO-DA HO:MI+ZONE\\n"
"Last-Translator: BuddyBoss <support@buddyboss.com>\\n"
"Language-Team: BuddyBoss <support@buddyboss.com>\\n"
"Language: en\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"X-Poedit-Country: United States\\n"
"X-Poedit-SourceCharset: UTF-8\\n"
"X-Poedit-KeywordsList: "
"__;_e;_x:1,2c;_ex:1,2c;_n:1,2;_nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_"
"attr__;esc_html__;esc_attr_e;esc_html_e;esc_attr_x:1,2c;esc_html_x:1,2c;\\n"
"X-Poedit-Basepath: ../\\n"
"X-Poedit-SearchPath-0: .\\n"
"X-Poedit-Bookmarks: \\n"
"X-Textdomain-Support: yes\\n"
"X-Generator: ${wpCliVersion}\\n"
"X-Domain: buddyboss\\n"

`;

    // Find the first actual msgid entry (not the header)
    const firstMsgidMatch = potContent.match(/\n(#[^\n]*\n)*msgid\s+"[^"]/);

    if (firstMsgidMatch) {
        const contentStartIndex = potContent.indexOf(firstMsgidMatch[0]);
        const originalContent = potContent.substring(contentStartIndex);

        // Combine corrected headers with original content
        const newPotContent = correctHeaders + originalContent;

        // Write the corrected POT file
        fs.writeFileSync(potFilePath, newPotContent, 'utf8');

        console.log('POT file headers fixed successfully to match grunt-wp-i18n format!');
    } else {
        console.error('Could not find content section in POT file');
        process.exit(1);
    }

} catch (error) {
    console.error('Error fixing POT file headers:', error.message);
    process.exit(1);
}
