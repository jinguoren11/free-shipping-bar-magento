/* Run: node Test/js/luma-bar.test.js   (no dependencies). Loads the real AMD module with small stand-ins for Magento's libraries. */
'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

// --- tiny stand-ins ---
function observable(initial) {
    let value = initial; const subs = [];
    const f = function (v) { if (arguments.length) { value = v; subs.forEach((s) => s(v)); } return value; };
    f.subscribe = (fn) => subs.push(fn);
    return f;
}
const ko = {observable, pureComputed: (fn) => () => fn()};
let sectionObs = observable(null);
const reloads = [];
const customerData = {get: () => sectionObs, reload: (names) => reloads.push(names)};
const $ = {mage: {cookies: {get: () => null}}};
const Component = {extend: (proto) => {
    const defaults = proto.defaults || {};
    return function () {
        const o = Object.assign({}, defaults, proto);
        o._super = function () {};
        return o;
    };
}};
const timers = [];
const docState = {target: null, mount: null};
const sandbox = {
    define: (deps, factory) => { sandbox.exported = factory(Component, $, ko, customerData); },
    window: {setTimeout: (fn) => { timers.push(fn); return timers.length; }},
    document: {
        documentElement: {getAttribute: () => 'en'},
        querySelector: (sel) => (sel.indexOf('data-gdf-mount') >= 0 ? docState.mount : docState.target)
    }
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../view/frontend/web/js/view/bar.js'), 'utf8'), sandbox);
const Bar = sandbox.exported;

const base = {visible: true, store_code: 'default', lang: 'en', state: 'progress', percent: 20, message: 'm', bar_color: '#123456',
    show_bar: true, show_percent: false, positions: {minicart: true, cart: true, product: true},
    selectors: {minicart: '', cart: '', product: ''}, placement: 'after'};
function make(position, kind, data) {
    sectionObs = observable(data);
    const c = Bar();
    c.position = position;
    c.kind = kind;
    c.initialize();
    return c;
}
const withSel = (pos, sel) => Object.assign({}, base, {selectors: Object.assign({}, base.selectors, {[pos]: sel})});

// standard positions
assert.strictEqual(make('cart', 'page', base).isVisible(), true);
assert.strictEqual(make('product', 'page', base).isVisible(), true);
assert.strictEqual(make('minicart', 'region', base).isVisible(), true);

// P3: the spare footer mount must show nothing when no custom mini cart position is set
assert.strictEqual(make('minicart', 'footer', base).isVisible(), false, 'stray bar below the footer');

// custom mini cart position: the region steps aside, the footer mount shows once its element is found
const mc = withSel('minicart', '.my-minicart');
assert.strictEqual(make('minicart', 'region', mc).isVisible(), false);
docState.target = null; docState.mount = null; timers.length = 0;
let f = make('minicart', 'footer', mc);
assert.strictEqual(f.isVisible(), false, 'element not found: nothing is shown');
assert.ok(timers.length > 0);
const moved = [];
docState.mount = {contains: () => false};
docState.target = {parentNode: {insertBefore: (n, ref) => moved.push(ref)}, nextSibling: 'NEXT', insertBefore() {}, appendChild() {}, firstChild: null};
timers.shift()();
assert.strictEqual(f.isVisible(), true);
assert.deepStrictEqual(moved, ['NEXT']);

// the mini cart never shows the empty-cart hint
const empty = Object.assign({}, base, {state: 'empty', percent: 0});
assert.strictEqual(make('minicart', 'region', empty).isVisible(), false);
assert.strictEqual(make('cart', 'page', empty).isVisible(), true, 'cart page keeps the hint');
assert.strictEqual(make('product', 'page', empty).isVisible(), true, 'product page keeps the hint');

// switched off / hidden section
assert.strictEqual(make('cart', 'page', Object.assign({}, base, {positions: {cart: false}})).isVisible(), false);
assert.strictEqual(make('cart', 'page', {visible: false, store_code: 'default', lang: 'en'}).isVisible(), false);

// a custom position on a page mount that is never found shows nothing and does not throw
docState.target = null; docState.mount = {contains: () => false};
assert.strictEqual(make('cart', 'page', withSel('cart', '.nope')).isVisible(), false);

// querySelector throwing (bad selector) must not escape
sandbox.document.querySelector = () => { throw new Error('bad'); };
assert.strictEqual(make('cart', 'page', withSel('cart', '<<<')).isVisible(), false);

// store view switched: ask for fresh data
reloads.length = 0;
sandbox.document.querySelector = () => null;
make('cart', 'page', Object.assign({}, base, {lang: 'es'}));
assert.ok(reloads.length > 0);

console.log('luma bar.js: all checks passed');
