import { describe, it, expect } from 'vitest';
import { buildUnresolvedCsv } from '../exporter.js';

const makeUnresolvedRow = (overrides = {}) => ({
    row: {
        'Buchungstag': '01.03.2025',
        'Name Zahlungsbeteiligter': 'Some Sender',
        'Verwendungszweck': 'Steppenreg 2025 - Unbekannte Person',
        'Betrag': '50,00',
        'Waehrung': 'EUR',
    },
    extractedName: 'Unbekannte Person',
    ...overrides,
});

describe('buildUnresolvedCsv', () => {
    it('returns a string', () => {
        const csv = buildUnresolvedCsv([makeUnresolvedRow()]);
        expect(typeof csv).toBe('string');
    });

    it('includes the correct headers', () => {
        const csv = buildUnresolvedCsv([makeUnresolvedRow()]);
        const firstLine = csv.split('\n')[0];

        expect(firstLine).toContain('Buchungstag');
        expect(firstLine).toContain('Name Zahlungsbeteiligter');
        expect(firstLine).toContain('Verwendungszweck');
        expect(firstLine).toContain('Betrag');
    });

    it('includes one data row per unresolved entry', () => {
        const rows = [makeUnresolvedRow(), makeUnresolvedRow({ extractedName: 'Another Person' })];
        const csv = buildUnresolvedCsv(rows);
        const lines = csv.split('\n').filter(l => l.trim() !== '');

        // 1 header + 2 data rows
        expect(lines).toHaveLength(3);
    });

    it('includes the correct data values in rows', () => {
        const csv = buildUnresolvedCsv([makeUnresolvedRow()]);
        expect(csv).toContain('01.03.2025');
        expect(csv).toContain('Steppenreg 2025 - Unbekannte Person');
        expect(csv).toContain('50,00');
    });

    it('returns only a header line for empty input', () => {
        const csv = buildUnresolvedCsv([]);
        const lines = csv.split('\n').filter(l => l.trim() !== '');
        expect(lines).toHaveLength(1);
    });

    it('uses semicolons as delimiter to match bank CSV format', () => {
        const csv = buildUnresolvedCsv([makeUnresolvedRow()]);
        const firstLine = csv.split('\n')[0];
        expect(firstLine).toContain(';');
    });

    it('escapes semicolons in field values with double quotes', () => {
        const row = makeUnresolvedRow({
            row: {
                'Buchungstag': '01.03.2025',
                'Name Zahlungsbeteiligter': 'Sender; with semicolon',
                'Verwendungszweck': 'Steppenreg 2025 - Test',
                'Betrag': '50,00',
                'Waehrung': 'EUR',
            },
        });
        const csv = buildUnresolvedCsv([row]);
        expect(csv).toContain('"Sender; with semicolon"');
    });
});
