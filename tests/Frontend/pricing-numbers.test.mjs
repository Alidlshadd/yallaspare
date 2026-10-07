import test from 'node:test';
import assert from 'node:assert/strict';
import { plainNumber, rateUnits, pricePlan, usdText, toIqd, targetUsd } from '../../resources/js/pricing-numbers.js';

test('Arabic digits and correctly grouped prices normalize without accepting malformed grouping', () => {
    assert.equal(plainNumber('١٧٠٬٠٠٠'), '170000');
    assert.equal(plainNumber('١٢٫٥٠'), '12.50');
    assert.equal(plainNumber('۱۷۰٬۰۰۰'), '170000');
    assert.equal(plainNumber('۱۲٫۵۰'), '12.50');
    assert.equal(plainNumber('17,000'), '17000');
    assert.equal(plainNumber('17 000'), '17000');
    assert.equal(rateUnits('12,34'), null);
    assert.equal(rateUnits('1 2'), null);
});

test('rate validation matches the server boundary', () => {
    for (const value of ['0', '-1', '100000000.01', '1.001', 'Infinity', '']) assert.equal(rateUnits(value), null);
    assert.equal(rateUnits('100000000'), 10000000000n);
    assert.equal(rateUnits('0.01'), 1n);
});

test('USD conversion rounds half-up without floating point drift', () => {
    assert.equal(pricePlan('USD', 'usd', '12.50', '', rateUnits('170000')).iqd, 21250n);
    assert.equal(toIqd(10001n, rateUnits('150000')), 1500n);
    assert.equal(toIqd(10010n, rateUnits('150000')), 1502n);
});

test('target dinars retain the four-decimal USD amount needed to round-trip', () => {
    const rate = rateUnits('170000');
    const plan = pricePlan('USD', 'iqd', '10', '16,000', rate);
    assert.equal(usdText(plan.usd), '9.4118');
    assert.equal(plan.iqd, 16000n);
    for (const target of [1n, 14999n, 16000n, 99999999n]) assert.equal(toIqd(targetUsd(target, rate), rate), target);
});

test('IQD products do not depend on the USD rate', () => {
    assert.deepEqual(pricePlan('IQD', 'iqd', '', '16000', null), { usd: null, iqd: 16000n });
    assert.equal(pricePlan('USD', 'usd', '10', '', null), null);
});

test('invalid and oversized prices cannot be previewed as valid', () => {
    const rate = rateUnits('170000');
    for (const value of ['-1', '0', '1.23456', 'abc', '9999999']) assert.equal(pricePlan('USD', 'usd', value, '', rate), null);
    assert.equal(pricePlan('IQD', 'iqd', '', '100000000', rate), null);
    assert.equal(pricePlan('IQD', 'iqd', '', '0', rate), null);
});
