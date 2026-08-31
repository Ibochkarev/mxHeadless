import fs from 'node:fs';
import YAML from 'yaml';

const path = process.argv[2] ?? 'docs/openapi.yaml';
const source = fs.readFileSync(path, 'utf8');
const doc = YAML.parse(source);

if (!doc || typeof doc !== 'object') {
  throw new Error(`${path}: could not parse YAML document`);
}

if (typeof doc.openapi !== 'string' || !doc.openapi.startsWith('3.')) {
  throw new Error(`${path}: expected OpenAPI 3.x (got ${doc.openapi ?? 'missing'})`);
}

if (!doc.info?.title || !doc.info?.version) {
  throw new Error(`${path}: info.title and info.version are required`);
}

if (!doc.paths || Object.keys(doc.paths).length === 0) {
  throw new Error(`${path}: paths must not be empty`);
}

console.log(`OpenAPI ${doc.openapi}: ${Object.keys(doc.paths).length} paths in ${path}`);
