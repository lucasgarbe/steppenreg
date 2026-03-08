import { describe, it, expect } from 'vitest';
import { hashRow } from '../row-hasher.js';

const makeRow = (overrides = {}) => ({
    'Buchungstag': '01.03.2025',
    'Betrag': '50,00',
    'Verwendungszweck': 'Steppenreg 2025 - Maria Muster',
    ...overrides,
});

describe('hashRow', () => {
    it('returns a non-empty string for a valid row', async () => {
        const hash = await hashRow(makeRow());
        expect(typeof hash).toBe('string');
        expect(hash.length).toBeGreaterThan(0);
    });

    it('same row data produces the same hash', async () => {
        const row = makeRow();
        const hash1 = await hashRow(row);
        const hash2 = await hashRow(row);
        expect(hash1).toBe(hash2);
    });

    it('different Buchungstag produces a different hash', async () => {
        const hash1 = await hashRow(makeRow({ 'Buchungstag': '01.03.2025' }));
        const hash2 = await hashRow(makeRow({ 'Buchungstag': '02.03.2025' }));
        expect(hash1).not.toBe(hash2);
    });

    it('different Betrag produces a different hash', async () => {
        const hash1 = await hashRow(makeRow({ 'Betrag': '50,00' }));
        const hash2 = await hashRow(makeRow({ 'Betrag': '75,00' }));
        expect(hash1).not.toBe(hash2);
    });

    it('different Verwendungszweck produces a different hash', async () => {
        const hash1 = await hashRow(makeRow({ 'Verwendungszweck': 'Steppenreg 2025 - Anna Schmidt' }));
        const hash2 = await hashRow(makeRow({ 'Verwendungszweck': 'Steppenreg 2025 - Klaus Weber' }));
        expect(hash1).not.toBe(hash2);
    });

    it('produces a hex string of expected length (SHA-256 = 64 chars)', async () => {
        const hash = await hashRow(makeRow());
        expect(hash).toMatch(/^[0-9a-f]{64}$/);
    });
});
