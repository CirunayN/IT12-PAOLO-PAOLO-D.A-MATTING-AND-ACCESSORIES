import test from 'node:test';
import assert from 'node:assert/strict';
import {FilterMemory} from '../../resources/js/filter-cache.js';

test('recent repeated filters are reused and expire', () => {
    let now = 0;
    const cache = new FilterMemory(() => now);
    cache.put('name=oil', {rows: 2});
    assert.deepEqual(cache.get('name=oil'), {rows: 2});
    now = 15000;
    assert.equal(cache.get('name=oil'), null);
});
test('cache limits retained results and clears on mutation', () => {
    const cache = new FilterMemory(() => 0, 2);
    cache.put('one', 1); cache.put('two', 2);
    cache.get('one'); cache.put('three', 3);
    assert.equal(cache.get('two'), null);
    assert.equal(cache.get('one'), 1);
    cache.clear();
    assert.equal(cache.get('three'), null);
});
