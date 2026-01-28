const fs = require('fs');
const path = require('path');

// Escape special characters for PHP string literals
function escapePhpString(str) {
  return str
    .replace(/\\/g, '\\\\')  // Escape backslashes first
    .replace(/"/g, '\\"')    // Escape double quotes
    .replace(/\n/g, '\\n')   // Escape newlines
    .replace(/\r/g, '\\r')   // Escape carriage returns
    .replace(/\t/g, '\\t')   // Escape tabs
    .replace(/\$/g, '\\$');  // Escape dollar signs (PHP variables)
}

// Recursively convert JSON object to PHP array syntax
function jsonToPhpArray(data) {
  if (Array.isArray(data)) {
    return `array(${data.map(jsonToPhpArray).join(', ')})`;
  } else if (typeof data === 'object' && data !== null) {
    return `array(${Object.entries(data).map(([key, value]) => `"${escapePhpString(key)}" => ${jsonToPhpArray(value)}`).join(', ')})`;
  } else if (typeof data === 'string') {
    return `"${escapePhpString(data)}"`;
  } else if (typeof data === 'number' || typeof data === 'boolean') {
    return data;
  }
  return 'null'; // handle null / undefined values
}

// Convert JSON file to PHP file
function convertJsonToPhp(jsonPath, phpPath) {
  // Validate input file exists
  if (!fs.existsSync(jsonPath)) {
    console.error(`Error: JSON file not found: ${jsonPath}`);
    process.exit(1);
  }

  // Read and parse JSON file with validation
  let jsonData;
  try {
    const fileContent = fs.readFileSync(jsonPath, 'utf-8');
    jsonData = JSON.parse(fileContent);
  } catch (error) {
    console.error(`Error: Invalid JSON in ${jsonPath}: ${error.message}`);
    process.exit(1);
  }

  // Convert JSON data to PHP array
  try {
    const phpArrayContent = jsonToPhpArray(jsonData);
    const phpContent = `<?php\n\n$bb_icons_data = ${phpArrayContent};\n`;

    // Write to PHP file
    fs.writeFileSync(phpPath, phpContent);
    console.log(`Successfully converted ${jsonPath} to ${phpPath}`);
  } catch (error) {
    console.error(`Error writing PHP file ${phpPath}: ${error.message}`);
    process.exit(1);
  }
}

// Convert fonts map to PHP array
const jsonFilePath = path.resolve(__dirname, '../src/bp-templates/bp-nouveau/icons/font-map.json');
const phpFilePath = path.resolve(__dirname, '../src/bp-templates/bp-nouveau/icons/font-map.php');
convertJsonToPhp(jsonFilePath, phpFilePath);