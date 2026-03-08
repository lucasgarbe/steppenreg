import { describe, it, expect } from 'vitest';
import { filterAndMatch } from '../matcher.js';

const registrations = [
    { id: 1, name: 'Maria Muster', payed: false, draw_status: 'drawn', track_name: 'Track A' },
    { id: 2, name: 'Klaus Weber', payed: true, draw_status: 'drawn', track_name: 'Track B' },
    { id: 3, name: 'Anna Schmidt', payed: false, draw_status: 'not_drawn', track_name: 'Track A' },
];

const makeRow = (verwendungszweck, overrides = {}) => ({
    'Buchungstag': '01.03.2025',
    'Valutadatum': '01.03.2025',
    'Name Zahlungsbeteiligter': 'Some Sender',
    'Verwendungszweck': verwendungszweck,
    'Betrag': '50,00',
    'Waehrung': 'EUR',
    ...overrides,
});

describe('filterAndMatch', () => {
    it('filters out rows where Verwendungszweck does not contain the event prefix', () => {
        const rows = [
            makeRow('Steppenreg 2025 - Maria Muster'),
            makeRow('Unrelated payment for something else'),
            makeRow('Steppenreg 2025 - Klaus Weber'),
        ];
        const { matched, unmatched, filtered } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(filtered).toHaveLength(1);
        expect(filtered[0]['Verwendungszweck']).toBe('Unrelated payment for something else');
        expect(matched.length + unmatched.length).toBe(2);
    });

    it('extracts the registrant name from "Prefix - Name" format', () => {
        const rows = [makeRow('Steppenreg 2025 - Maria Muster')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched).toHaveLength(1);
        expect(matched[0].extractedName).toBe('Maria Muster');
    });

    it('performs exact match case-insensitively', () => {
        const rows = [makeRow('Steppenreg 2025 - maria muster')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched).toHaveLength(1);
        expect(matched[0].registration.id).toBe(1);
    });

    it('performs exact match with leading/trailing whitespace tolerance', () => {
        const rows = [makeRow('Steppenreg 2025 -  Anna Schmidt ')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched).toHaveLength(1);
        expect(matched[0].registration.id).toBe(3);
    });

    it('returns unmatched when extracted name is not in registration list', () => {
        const rows = [makeRow('Steppenreg 2025 - Unbekannte Person')];
        const { matched, unmatched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched).toHaveLength(0);
        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].extractedName).toBe('Unbekannte Person');
    });

    it('returns unmatched (not fuzzy) when name is close but not exact', () => {
        // 'Marie Muster' vs 'Maria Muster' — one character difference
        const rows = [makeRow('Steppenreg 2025 - Marie Muster')];
        const { matched, unmatched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched).toHaveLength(0);
        expect(unmatched).toHaveLength(1);
    });

    it('attaches the full registration object to matched rows', () => {
        const rows = [makeRow('Steppenreg 2025 - Klaus Weber')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched[0].registration).toMatchObject({
            id: 2,
            name: 'Klaus Weber',
            payed: true,
        });
    });

    it('attaches the original row data to matched and unmatched results', () => {
        const rows = [makeRow('Steppenreg 2025 - Maria Muster')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(matched[0].row['Betrag']).toBe('50,00');
        expect(matched[0].row['Buchungstag']).toBe('01.03.2025');
    });

    it('returns empty results for empty row array', () => {
        const { matched, unmatched, filtered } = filterAndMatch([], 'Steppenreg 2025', registrations);
        expect(matched).toHaveLength(0);
        expect(unmatched).toHaveLength(0);
        expect(filtered).toHaveLength(0);
    });

    it('is case-insensitive for the event prefix filter', () => {
        const rows = [makeRow('steppenreg 2025 - Maria Muster')];
        const { matched } = filterAndMatch(rows, 'Steppenreg 2025', registrations);
        expect(matched).toHaveLength(1);
    });

    it('filters out rows that mention the prefix but lack the " - " separator', () => {
        // e.g. "Spende allgemein Steppenreg e.V." — contains the prefix word but is not a registration transfer
        const rows = [
            makeRow('Spende allgemein Steppenreg e.V.'),
            makeRow('Steppenreg 2025 - Maria Muster'),
        ];
        const { matched, filtered } = filterAndMatch(rows, 'Steppenreg 2025', registrations);

        expect(filtered).toHaveLength(1);
        expect(filtered[0]['Verwendungszweck']).toBe('Spende allgemein Steppenreg e.V.');
        expect(matched).toHaveLength(1);
    });

    it('filters out rows where the prefix appears only after the " - " separator', () => {
        // e.g. "Payment note - see Steppenreg docs" — prefix is after separator, not before
        const rows = [makeRow('Payment note - see Steppenreg docs')];
        const { filtered } = filterAndMatch(rows, 'Steppenreg', registrations);

        expect(filtered).toHaveLength(1);
    });
});
