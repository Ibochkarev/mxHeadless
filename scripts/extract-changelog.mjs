import fs from 'node:fs';

const version = process.argv[2];
const path = process.argv[3] ?? 'core/components/mxheadless/docs/changelog.txt';

if (!version) {
  console.error('Usage: node scripts/extract-changelog.mjs <version> [changelog-path]');
  process.exit(1);
}

const source = fs.readFileSync(path, 'utf8');
const header = `## [${version}]`;
const start = source.indexOf(header);

if (start === -1) {
  console.error(`Changelog section not found: ${header}`);
  process.exit(1);
}

const rest = source.slice(start);
const nextSection = rest.indexOf('\n## [', header.length);

process.stdout.write(nextSection === -1 ? rest.trimEnd() : rest.slice(0, nextSection).trimEnd());
