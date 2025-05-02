const sass = require('sass');
const fs = require('fs');
const path = require('path');

// Paths
const srcDir = path.resolve(__dirname);
const destDir = path.resolve(__dirname, '..');

// Compile SCSS to CSS
try {
    console.log('Compiling SCSS files...');
    
    // Process the main settings.scss file
    const result = sass.compile(path.join(srcDir, 'settings.scss'), {
        style: 'compressed',
        loadPaths: [srcDir]
    });
    
    // Write the output CSS file
    fs.writeFileSync(
        path.join(destDir, 'settings.css'),
        result.css
    );
    
    console.log('SCSS compilation completed successfully');
} catch (error) {
    console.error('Error compiling SCSS:', error);
} 