/* jshint node:true, esversion: 9 */
/**
 * Recompress every entry of a zip with Zopfli DEFLATE — same zip format and
 * byte-identical file contents, just a better encoder than zlib level 9
 * (~4% smaller archive). Entries that Zopfli cannot beat STORE fall back to
 * STORE. Directory entries are dropped (paths recreate implicitly on extract).
 *
 * Used by the Gruntfile `zopfli_recompress` task, which runs it on
 * buddyboss-platform-plugin.zip after `compress` in the build chains.
 *
 * Usage: node bin/zopfli-recompress-zip.js in.zip out.zip
 *
 * @since BuddyBoss [BBVERSION]
 */
'use strict';
const fs = require('fs');
const yauzl = require('yauzl');
const crc32 = require('buffer-crc32');
const zopfli = require('@gfx/zopfli');

const [inPath, outPath] = process.argv.slice(2);

function dosDateTime(d) {
	const time = ((d.getHours() & 0x1f) << 11) | ((d.getMinutes() & 0x3f) << 5) | ((d.getSeconds() / 2) & 0x1f);
	const date = (((d.getFullYear() - 1980) & 0x7f) << 9) | (((d.getMonth() + 1) & 0xf) << 5) | (d.getDate() & 0x1f);
	return { time, date };
}

function readEntries(path) {
	return new Promise((resolve, reject) => {
		const entries = [];
		yauzl.open(path, { lazyEntries: true }, (err, zip) => {
			if (err) return reject(err);
			zip.readEntry();
			zip.on('entry', (entry) => {
				if (/\/$/.test(entry.fileName)) { // directory entry — skip, paths recreate implicitly
					zip.readEntry();
					return;
				}
				zip.openReadStream(entry, (e2, stream) => {
					if (e2) return reject(e2);
					const chunks = [];
					stream.on('data', (c) => chunks.push(c));
					stream.on('end', () => {
						entries.push({ name: entry.fileName, data: Buffer.concat(chunks), mtime: entry.getLastModDate() });
						zip.readEntry();
					});
					stream.on('error', reject);
				});
			});
			zip.on('end', () => resolve(entries));
			zip.on('error', reject);
		});
	});
}

async function main() {
	const entries = await readEntries(inPath);
	process.stderr.write(`entries: ${entries.length}\n`);
	const out = fs.openSync(outPath, 'w');
	let offset = 0;
	const central = [];
	let done = 0;
	for (const e of entries) {
		const deflated = Buffer.from(await new Promise((res, rej) =>
			zopfli.deflate(e.data, { numiterations: 15 }, (err, buf) => (err ? rej(err) : res(buf)))
		));
		// If zopfli can't beat STORE, store it (valid + smaller).
		const useStore = deflated.length >= e.data.length;
		const payload = useStore ? e.data : deflated;
		const method = useStore ? 0 : 8;
		const crc = crc32.unsigned(e.data);
		const nameBuf = Buffer.from(e.name, 'utf8');
		const { time, date } = dosDateTime(e.mtime || new Date(2026, 0, 1));

		const lfh = Buffer.alloc(30);
		lfh.writeUInt32LE(0x04034b50, 0);
		lfh.writeUInt16LE(20, 4);            // version needed
		lfh.writeUInt16LE(0x0800, 6);        // flags: UTF-8 names
		lfh.writeUInt16LE(method, 8);
		lfh.writeUInt16LE(time, 10);
		lfh.writeUInt16LE(date, 12);
		lfh.writeUInt32LE(crc, 14);
		lfh.writeUInt32LE(payload.length, 18);
		lfh.writeUInt32LE(e.data.length, 22);
		lfh.writeUInt16LE(nameBuf.length, 26);
		lfh.writeUInt16LE(0, 28);            // extra len
		fs.writeSync(out, lfh);
		fs.writeSync(out, nameBuf);
		fs.writeSync(out, payload);
		central.push({ nameBuf, method, time, date, crc, csize: payload.length, usize: e.data.length, offset });
		offset += 30 + nameBuf.length + payload.length;
		if (++done % 250 === 0) process.stderr.write(`  ${done}/${entries.length}\n`);
	}
	const cdStart = offset;
	for (const c of central) {
		const cdh = Buffer.alloc(46);
		cdh.writeUInt32LE(0x02014b50, 0);
		cdh.writeUInt16LE(0x031e, 4);        // made by: unix, v3.0
		cdh.writeUInt16LE(20, 6);
		cdh.writeUInt16LE(0x0800, 8);
		cdh.writeUInt16LE(c.method, 10);
		cdh.writeUInt16LE(c.time, 12);
		cdh.writeUInt16LE(c.date, 14);
		cdh.writeUInt32LE(c.crc, 16);
		cdh.writeUInt32LE(c.csize, 20);
		cdh.writeUInt32LE(c.usize, 24);
		cdh.writeUInt16LE(c.nameBuf.length, 28);
		// extra/comment/disk/int-attrs = 0
		cdh.writeUInt32LE(0x81a40000, 38);   // ext attrs: -rw-r--r--
		cdh.writeUInt32LE(c.offset, 42);
		fs.writeSync(out, cdh);
		fs.writeSync(out, c.nameBuf);
		offset += 46 + c.nameBuf.length;
	}
	const eocd = Buffer.alloc(22);
	eocd.writeUInt32LE(0x06054b50, 0);
	eocd.writeUInt16LE(central.length, 8);
	eocd.writeUInt16LE(central.length, 10);
	eocd.writeUInt32LE(offset - cdStart, 12);
	eocd.writeUInt32LE(cdStart, 16);
	fs.writeSync(out, eocd);
	fs.closeSync(out);
	process.stderr.write(`wrote ${outPath}: ${fs.statSync(outPath).size} bytes (in: ${fs.statSync(inPath).size})\n`);
}

main().catch((e) => { console.error(e); process.exit(1); });
