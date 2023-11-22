/*
 * Read https://cloud.google.com/translate/docs/quickstart-client-libraries
 * for Google APIs authentication
 */

const fs = require('fs');
const gettextParser = require('gettext-parser');
const sleep = require('sleep');
const args = require('yargs').argv;
const colors = require('colors');
const Bottleneck = require('bottleneck');
const { Translate } = require('@google-cloud/translate').v2;

const limiter = new Bottleneck({
	maxConcurrent: 1, // Adjust the number of concurrent requests
	minTime: 1000, // Adjust the minimum time between requests in milliseconds
});

// Your Google Project Id
const projectId = args.project_id;

// Path to Google Cloud API credentials JSON file
const credentialsPath = 'google-service-account.json';

// PO source file
const po_source_path = args.po_source;

// PO backup file
const po_bkp_path = po_source_path + '.backup';

// PO destination file
const po_path = args.po_dest;

// MO destination file
const mo_path = args.mo;

// Target language
const target = args.lang;

// Path to the whitelist file
const whitelistFilePath = 'whitelist.txt';

// Read the whitelist from the file
const whitelist = fs.readFileSync(whitelistFilePath, 'utf-8').split('\n').filter(Boolean);

if (!(projectId && credentialsPath && po_source_path && po_bkp_path && po_path && mo_path && target)) {
	showHelp();
	process.exit(-1);
}

function showHelp() {
	const scriptName = args.$0;

	const usage = `
Usage: node ${scriptName}
  --project_id={your google project id}
  --po_source={path of empty PO file}
  --po_dest={path of translated PO file}
  --mo={path of translated MO file}
  --lang={language code}
`.red;

	console.log(usage);

	console.log('\nExample:');
	const example = `
  ${scriptName}
    --project_id=example
    --po_source=/Users/diego/it-source.po
    --po_dest=/Users/diego/it.po
    --mo=/Users/diego/it.mo
    --lang=it
  `;
	console.log(example);
}
async function tr(text) {
	// Instantiates a client with the provided credentials
	const translate = new Translate({
		projectId,
		keyFilename: credentialsPath,
	});
	const [translation] = await translate.translate(text, target);
	return translation;
}

async function start() {
	const maxRetries = 3;
	let retries = 0;
	let success = false;

	while (!success && retries < maxRetries) {
		try {
			// Read the input PO file
			const input = fs.readFileSync(po_source_path);
			const po = gettextParser.po.parse(input);

			for (const k in po.translations['']) {
				if (
					po.translations[''][k] &&
					po.translations[''][k].msgstr &&
					po.translations[''][k].msgstr[0] === '' &&
					!po.translations[''][k].comments?.translator && // Check if not already translated
					!whitelist.includes(k) // Check if not in the whitelist
				) {
					// Translate the string if it's not already translated and not in the whitelist
					let translation = await tr(k);
					console.log(`${k.yellow}\n${translation.cyan}\n`);
					po.translations[''][k].msgstr[0] = translation;

					if (!po.translations[''][k].comments) po.translations[''][k].comments = {};

					po.translations[''][k].comments.translator = 'automatic translation';

					// Backup the original PO file
					// const output_po_backup = gettextParser.po.compile(po);
					// fs.writeFileSync(po_bkp_path, output_po_backup);

					// Sleep to avoid flooding the Google APIs
					sleep.msleep(500);
				}
			}

			// Write the translated MO file
			const output_mo = gettextParser.mo.compile(po);
			fs.writeFileSync(mo_path, output_mo);

			// Write the translated PO file
			const output_po = gettextParser.po.compile(po);
			fs.writeFileSync(po_path, output_po);
			success = true;
		} catch (error) {
			console.error(`Error in start function (Retry ${retries + 1}):`, error.message);
			retries++;
			// Optionally, add a delay before the next retry
			sleep.msleep(500);
		}
	}

	if (!success) {
		console.error(`Failed after ${maxRetries} retries. Please handle this appropriately.`);
	}
}

start().catch(error => console.error('Unhandled error:', error));
