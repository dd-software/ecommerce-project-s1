/**
 * Check mínimo del ruteo (sin framework): carga router.js con stubs de DOM
 * y verifica que parse() mapea bien hash -> path/params.
 * Correr: node tests/router.test.js
 */
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');

const code = fs.readFileSync(path.join(__dirname, '../public/js/router.js'), 'utf8');
const sandbox = { location: { hash: '' }, window: {}, document: { addEventListener() {} }, URLSearchParams, console };
vm.createContext(sandbox);
vm.runInContext(code + '\nthis.Router = Router;', sandbox);
const R = sandbox.Router;

const parse = (hash) => { sandbox.location.hash = hash; return R.parse(); };

assert.equal(parse('').path, 'home');                 // sin hash -> home
assert.equal(parse('#/').path, 'home');               // raíz -> home
assert.equal(parse('#/catalogo').path, 'catalogo');
assert.equal(parse('#/producto/8').path, 'producto/8'); // detalle conserva el id
assert.equal(parse('#/contacto').path, 'contacto');

const p = parse('#/catalogo?cat=3');
assert.equal(p.path, 'catalogo');
assert.equal(p.params.get('cat'), '3');

// guard de categoría numérica (slug del nav no debe filtrar todavía)
assert.ok(/^\d+$/.test('3'));
assert.ok(!/^\d+$/.test('repuestos'));

console.log('router parse OK');
