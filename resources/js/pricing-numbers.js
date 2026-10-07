// Decimal arithmetic mirrors ExchangeRate: USD has four decimal places,
// rates have two, and selling prices round half-up to the whole dinar.
export function plainNumber(value) {
    const normalized = String(value).trim()
        .replace(/[\u0660-\u0669]/g, digit => String(digit.charCodeAt(0) - 0x660))
        .replace(/[\u06f0-\u06f9]/g, digit => String(digit.charCodeAt(0) - 0x6f0))
        .replace(/\u066b/g, '.').replace(/\u066c/g, '');
    return /^[+-]?\d{1,3}([, ]\d{3})+(\.\d+)?$/.test(normalized)
        ? normalized.replace(/[, ]/g, '') : normalized;
}

export function scaled(value, places, maxDigits = 10) {
    const match = new RegExp(`^(\\d{1,${maxDigits}})(?:\\.(\\d{1,${Math.max(1, places)}}))?$`).exec(plainNumber(value));
    if (!match || (places === 0 && match[2])) return null;
    return BigInt(match[1]) * 10n ** BigInt(places) + BigInt((match[2] || '').padEnd(places, '0') || '0');
}

export function rateUnits(value) {
    const units = scaled(value, 2, 9);
    return units !== null && units > 0n && units <= 10000000000n ? units : null;
}

export const toIqd = (usd, rate) => (usd * rate + 50000000n) / 100000000n;

export function targetUsd(target, rate) {
    const units = (target * 100000000n + rate / 2n) / rate;
    for (const candidate of [units, units + 1n, units - 1n]) {
        if (candidate >= 0n && toIqd(candidate, rate) === target) return candidate;
    }
    return units;
}

export function usdText(units) {
    const fraction = (units % 10000n).toString().padStart(4, '0').replace(/0{1,2}$/, '');
    return `${units / 10000n}.${fraction}`;
}

export function pricePlan(currency, basis, usdValue, iqdValue, rate) {
    let usd = null;
    let iqd = null;
    if (currency === 'USD' && !rate) return null;
    if (basis === 'iqd' || currency !== 'USD') {
        iqd = scaled(iqdValue, 0);
        if (iqd === null || iqd <= 0n) return null;
        if (currency === 'USD') {
            usd = targetUsd(iqd, rate);
            iqd = toIqd(usd, rate);
        }
    } else {
        usd = scaled(usdValue, 4, 7);
        if (usd === null || usd <= 0n) return null;
        iqd = toIqd(usd, rate);
    }
    return iqd >= 0n && iqd <= 99999999n ? { usd, iqd } : null;
}
