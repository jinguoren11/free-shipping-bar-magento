/* Run: node Test/js/hyva-bar.test.js   (no dependencies) */
'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');

let factory = null;
const timers = [];
global.window = global;
global.window.Alpine = {data: (name, f) => { assert.strictEqual(name, 'gdfFsb'); factory = f; }};
global.window.setTimeout = (fn) => { timers.push(fn); return timers.length; };
const events = [];
global.window.dispatchEvent = (e) => { events.push(e.type); };
global.window.addEventListener = () => {};
const docState = {target: null};
global.document = {querySelector: () => docState.target};
require(path.join(__dirname, '../../Compat/Hyva/view/frontend/web/js/hyva-bar.js'));
assert.ok(factory, 'component registered');

function make(attrs, store) {
    const calls = [];
    const el = {
        getAttribute: (k) => attrs[k] || null,
        contains: () => false
    };
    const c = factory();
    c.$el = el;
    c.$store = store || {privateContent: {loaded: false}};
    c.calls = calls;
    return c;
}
const initialEmpty = {visible: true, store_code: 'default', lang: 'en', state: 'empty', percent: 0, message: 'Free shipping on orders over $50.00',
    bar_color: '#3b6ea5', show_bar: true, show_percent: false, positions: {minicart: false, cart: true, product: true},
    selectors: {minicart: '', cart: '', product: ''}, placement: 'after'};
const attrs = (extra) => Object.assign({'data-position': 'cart', 'data-kind': 'page', 'data-initial': JSON.stringify(initialEmpty)}, extra || {});

// 1. first-time visitor: empty-cart state from the page is shown, no track for "empty"
let c = make(attrs());
c.init();
assert.strictEqual(c.isVisible, true);
assert.strictEqual(c.message, 'Free shipping on orders over $50.00');
assert.strictEqual(c.showTrack, false);

// 2. section data wins over the initial state
const progress = Object.assign({}, initialEmpty, {state: 'progress', percent: 20, message: 'Add $40.00 more to get free shipping!'});
c.onPrivateContent({detail: {data: {'free-shipping-bar': progress}}});
assert.strictEqual(c.message, 'Add $40.00 more to get free shipping!');
assert.strictEqual(c.showTrack, true);
assert.strictEqual(c.fillStyle.width, '20%');
assert.strictEqual(c.barClass['gdf-fsb-bar--done'], false);

// 3. reached state
c.onPrivateContent({detail: {data: {'free-shipping-bar': Object.assign({}, progress, {state: 'done', percent: 100})}}});
assert.strictEqual(c.barClass['gdf-fsb-bar--done'], true);
assert.strictEqual(c.percentText, '100%');

// 4. hidden section (virtual cart) and a position that is switched off
c.onPrivateContent({detail: {data: {'free-shipping-bar': {visible: false, store_code: 'default', lang: 'en'}}}});
assert.strictEqual(c.isVisible, false);
c = make(attrs({'data-position': 'minicart'}));
c.init();
assert.strictEqual(c.isVisible, false, 'minicart switched off in settings');

// 5. section missing from the event (e.g. main module disabled): fall back, never throw
c = make(attrs());
c.init();
c.onPrivateContent({detail: {data: {cart: {}}}});
assert.strictEqual(c.isVisible, true);
c.onPrivateContent(null);
c.onPrivateContent({});

// 6. broken initial JSON must not throw and shows nothing
c = make(attrs({'data-initial': '{not json'}));
c.init();
assert.strictEqual(c.isVisible, false);

// 7. custom position: element not found -> nothing is shown and the retry is scheduled
const withSel = Object.assign({}, initialEmpty, {selectors: {minicart: '', cart: '.my-cart-title', product: ''}, placement: 'after'});
docState.target = null;
timers.length = 0;
c = make(attrs({'data-initial': JSON.stringify(withSel)}));
c.init();
assert.strictEqual(c.isVisible, false);
assert.ok(timers.length > 0, 'retries later');

// 8. custom position: element appears later -> bar moves there ("after") and shows
const inserted = [];
docState.target = {parentNode: {insertBefore: (n, ref) => inserted.push(['insertBefore', ref])}, nextSibling: 'NEXT', insertBefore() {}, appendChild() {}, firstChild: 'FIRST'};
timers.shift()();
assert.strictEqual(c.placed, true);
assert.strictEqual(c.isVisible, true);
assert.deepStrictEqual(inserted[0], ['insertBefore', 'NEXT']);

// 9. other placements
for (const [placement, expect] of [['before', 'target'], ['append', 'append'], ['prepend', 'FIRST']]) {
    const calls = [];
    const t = {parentNode: {insertBefore: (n, ref) => calls.push(['pb', ref === t ? 'target' : ref])}, nextSibling: 'NEXT', firstChild: 'FIRST',
        insertBefore: (n, ref) => calls.push(['ib', ref]), appendChild: () => calls.push(['append', 'append'])};
    docState.target = t;
    const d = Object.assign({}, withSel, {placement});
    const x = make(attrs({'data-initial': JSON.stringify(d)}));
    x.init();
    assert.strictEqual(x.placed, true, placement);
    assert.strictEqual(calls[0][1], expect, placement);
}

// 10. a selector that makes querySelector throw -> nothing shown, no exception
global.document = {querySelector: () => { throw new Error('bad selector'); }};
c = make(attrs({'data-initial': JSON.stringify(withSel)}));
c.init();
assert.strictEqual(c.isVisible, false);
global.document = {querySelector: () => docState.target};

// 11. mini cart "footer" kind is shown only with a custom position
const mc = Object.assign({}, initialEmpty, {positions: {minicart: true, cart: true, product: true}});
c = make({'data-position': 'minicart', 'data-kind': 'footer', 'data-initial': JSON.stringify(mc)});
c.init();
assert.strictEqual(c.isVisible, false, 'no custom position -> hidden');

// 12. store view switched: asks the page to reload section data exactly once
events.length = 0;
c = make(attrs());
c.init();
const other = Object.assign({}, progress, {store_code: 'es'});
c.onPrivateContent({detail: {data: {'free-shipping-bar': other}}});
c.onPrivateContent({detail: {data: {'free-shipping-bar': other}}});
assert.deepStrictEqual(events, ['reload-customer-section-data']);

// 13. Hyva cart drawer (kind "drawer"): shown with items, hidden for an empty cart, hidden when a custom position is set
const mcAll = Object.assign({}, initialEmpty, {positions: {minicart: true, cart: true, product: true}});
const drawerAttrs = (d) => ({'data-position': 'minicart', 'data-kind': 'drawer', 'data-initial': JSON.stringify(d)});
c = make(drawerAttrs(mcAll));
c.init();
assert.strictEqual(c.isVisible, false, 'empty cart: no hint in the mini cart');
c.onPrivateContent({detail: {data: {'free-shipping-bar': Object.assign({}, mcAll, {state: 'progress', percent: 20, message: 'Add $40.00 more to get free shipping!'})}}});
assert.strictEqual(c.isVisible, true, 'drawer with items');
assert.strictEqual(c.message, 'Add $40.00 more to get free shipping!');
c.onPrivateContent({detail: {data: {'free-shipping-bar': Object.assign({}, mcAll, {state: 'done', percent: 100})}}});
assert.strictEqual(c.isVisible, true);
const mcCustom = Object.assign({}, mcAll, {state: 'progress', selectors: {minicart: '.my-minicart', cart: '', product: ''}});
docState.target = null;
c = make(drawerAttrs(mcCustom));
c.init();
assert.strictEqual(c.isVisible, false, 'custom mini cart position set: drawer mount steps aside');
timers.length = 0;
c.startPlacing();
assert.strictEqual(timers.length, 0, 'drawer mount never moves itself');
// the spare footer mount takes over only once its element is found
c = make({'data-position': 'minicart', 'data-kind': 'footer', 'data-initial': JSON.stringify(mcCustom)});
c.init();
assert.strictEqual(c.isVisible, false);
docState.target = {parentNode: {insertBefore() {}}, nextSibling: null, insertBefore() {}, appendChild() {}, firstChild: null};
timers.shift()();
assert.strictEqual(c.isVisible, true);
// footer + progress without custom position: nothing (this was the stray bar below the footer, P3)
c = make({'data-position': 'minicart', 'data-kind': 'footer', 'data-initial': JSON.stringify(Object.assign({}, mcAll, {state: 'progress'}))});
c.init();
assert.strictEqual(c.isVisible, false);

console.log('hyva-bar.js: all checks passed');
