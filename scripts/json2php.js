const fs = require('fs');
const path = require('path');

// Recursively convert JSON object to PHP array syntax
function jsonToPhpArray(data) {
  if (Array.isArray(data)) {
    return `array(${data.map(jsonToPhpArray).join(', ')})`;
  } else if (typeof data === 'object' && data !== null) {
    return `array(${Object.entries(data).map(([key, value]) => `"${key}" => ${jsonToPhpArray(value)}`).join(', ')})`;
  } else if (typeof data === 'string') {
    return `"${data}"`;
  } else if (typeof data === 'number' || typeof data === 'boolean') {
    return data;
  }
  return 'null'; // handle null / undefined values
}

// Convert JSON file to PHP file
function convertJsonToPhp(jsonPath, phpPath) {
  try {
    // Read and parse JSON file
    const jsonData = JSON.parse(fs.readFileSync(jsonPath, 'utf-8'));

    // Convert JSON data to PHP array
    const phpArrayContent = jsonToPhpArray(jsonData);
    const phpContent = `<?php\n\n$bb_icons_data = ${phpArrayContent};\n`;

    // Write to PHP file
    fs.writeFileSync(phpPath, phpContent);
    console.log(`Successfully converted ${jsonPath} to ${phpPath}`);
  } catch (error) {
    console.error('Error converting JSON to PHP:', error);
  }
}

// Convert fonts map to PHP array
const jsonFilePath = path.resolve(__dirname, '../src/bp-templates/bp-nouveau/icons/font-map.json');
const phpFilePath = path.resolve(__dirname, '../src/bp-templates/bp-nouveau/icons/font-map.php');
convertJsonToPhp(jsonFilePath, phpFilePath);