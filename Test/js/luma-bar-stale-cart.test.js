/* Run: node Test/js/luma-bar-stale-cart.test.js   (no dependencies)
 * F2 (ASUS 355): after an order is placed the next page must not keep showing the old "Add $X more" data.
 * The page that places the order has no bar, so on the next page the bar compares Magento's cart section signature
 * (data_id|summary_count|subtotal) with the one it saved last time and reloads its own section when they differ. */
'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const code = fs.readFileSync(path.join(__dirname, '../../view/frontend/web/js/view/bar.js'), 'utf8');

function observable(initial) {
    let value = initial; const subs = [];
    const f = function (v) { if (arguments.length) { value = v; subs.forEach((s) => s(v)); } return value; };
    f.subscribe = (fn) => subs.push(fn);
    return f;
}

// one "page load": fresh module state, shared browser storage
function pageLoad(storage, cartNow, sectionNow) {
    const reloads = []; const forces = []; const timers = [];
    const cartObs = observable(cartNow); const sectionObs = observable(sectionNow || {store_code: 'default', lang: 'en', visible: true});
    const customerData = {get: (n) => (n === 'cart' ? cartObs : sectionObs), reload: (names, force) => { reloads.push(names); forces.push(force); }};
    const Component = {extend: (proto) => () => Object.assign({_super() {}}, proto.defaults || {}, proto)};
    const sandbox = {
        define: (deps, factory) => { sandbox.exported = factory(Component, {mage: {cookies: {get: () => null}}}, {observable, pureComputed: (fn) => () => fn()}, customerData); },
        window: {
            setTimeout: (fn) => { timers.push(fn); return timers.length; }, clearTimeout: () => {},
            localStorage: storage
        },
        document: {documentElement: {getAttribute: () => 'en'}, querySelector: () => null}
    };
    vm.runInNewContext(code, sandbox);
    const c = sandbox.exported(); c.position = 'cart'; c.kind = 'page'; c.initialize();
    return {reloads, forces, timers, cartObs, sectionObs, run: () => timers.splice(0).forEach((t) => t())};
}
const makeStorage = () => { const m = {}; return {getItem: (k) => (k in m ? m[k] : null), setItem: (k, v) => { m[k] = String(v); }}; };

const cartWithItems = {data_id: 1700000001, summary_count: 2, subtotalAmount: 44};
const cartAfterOrder = {data_id: 1700000099, summary_count: 0, subtotalAmount: 0};

// 1. normal browsing: same cart as last time, nothing reloaded
let storage = makeStorage();
let p = pageLoad(storage, cartWithItems);
p.run(); p.reloads.length = 0;
p = pageLoad(storage, cartWithItems);
p.run();
assert.strictEqual(p.reloads.length, 0, 'unchanged cart: no extra reload');

// 2. the order was placed on pages without a bar: the next page already holds the emptied cart -> reload
p = pageLoad(storage, cartAfterOrder);
assert.ok(p.timers.length > 0, 'a reload is scheduled');
p.run();
assert.strictEqual(JSON.stringify(p.reloads[p.reloads.length - 1]), '["free-shipping-bar"]');
assert.strictEqual(storage.getItem('gdf_fsb_cart_sig'), '1700000099||', 'the new signature is saved');

// 3. and only once: the page after that does not reload again
p = pageLoad(storage, cartAfterOrder); p.run(); p.reloads.length = 0;
p = pageLoad(storage, cartAfterOrder); p.run();
assert.strictEqual(p.reloads.length, 0);

// 4. the cart section arrives after the component started: the live subscription still reloads and saves
storage = makeStorage();
p = pageLoad(storage, {}); p.run(); p.reloads.length = 0;
p.cartObs(cartWithItems); p.run();
assert.strictEqual(p.reloads.length, 1);
assert.strictEqual(storage.getItem('gdf_fsb_cart_sig'), '1700000001|2|44');

// 5. blocked storage must not break anything
const broken = {getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); }};
p = pageLoad(broken, cartWithItems); p.run();
p.cartObs(cartAfterOrder); p.run();
assert.strictEqual(p.reloads.length >= 1, true);

// 6. Luma sequence (ASUS 357): stored bar data is from before the order, the saved signature equals the cart the page
// starts with (so the first compare sees no change), then Magento's own refresh brings the empty cart.
const staleBar = {store_code: 'default', lang: 'en', visible: true, state: 'progress', percent: 80, positions: {cart: true}};
storage = makeStorage(); storage.setItem('gdf_fsb_cart_sig', '1700000001|2|44');
p = pageLoad(storage, cartWithItems, staleBar); p.run(); p.reloads.length = 0;
p.cartObs(cartAfterOrder);
p.run();
assert.ok(p.reloads.length >= 1, 'the empty cart makes the bar reload');
// the reload answers do not arrive / do not fix it: the disagreement check retries (forced), but only 3 times
const before = p.reloads.length;
for (let i = 0; i < 6; i++) { p.sectionObs(Object.assign({}, staleBar)); p.run(); }
assert.ok(p.forces.slice(before).every((f) => f === true), 'retries are forced reloads');
assert.ok(p.reloads.length - before <= 3, 'never more than 3 retries in a row');
// once the answer is right, the counter is reset and nothing more is asked
const okBar = Object.assign({}, staleBar, {state: 'empty', percent: 0});
p.sectionObs(okBar); p.run(); const settled = p.reloads.length; p.run();
assert.strictEqual(p.reloads.length, settled);

// 7. the section data is stale and the cart section already shows the empty cart when the page starts
storage = makeStorage();
p = pageLoad(storage, cartAfterOrder, staleBar);
p.run();
assert.ok(p.reloads.length >= 1, 'start-up disagreement is repaired');

// 8. cart with items but bar says empty (the other direction)
p = pageLoad(makeStorage(), cartWithItems, Object.assign({}, staleBar, {state: 'empty'}));
p.run();
assert.ok(p.reloads.length >= 1);

// 9. no state / no cart data yet: never treated as stale
p = pageLoad(makeStorage(), {}, staleBar); p.run();
assert.strictEqual(p.reloads.length, 0);

console.log('luma bar.js stale-cart checks passed');
