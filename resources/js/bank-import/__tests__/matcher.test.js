import { describe, it, expect } from 'vitest';
import { filterAndMatch } from '../matcher.js';

const registrations = [
    { id: 1, name: 'Maria Muster', paid: false, draw_status: 'drawn', track_name: 'Track A' },
    { id: 2, name: 'Klaus Weber', paid: true, draw_status: 'drawn', track_name: 'Track B' },
    { id: 3, name: 'Anna Schmidt', paid: false, draw_status: 'not_drawn', track_name: 'Track A' },
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
            paid: true,
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

describe('loose suggestions (never auto-confirmed)', () => {
    it('suggests a match when an exact registration name appears without the separator', () => {
        // prefix present but no " - " convention — e.g. bank writes it in one chunk
        const rows = [makeRow('Teilnahmebeitrag SPX26 Davonte Moore')];
        // Davonte Moore is not registered here, but the suggestion mechanism
        // must find a full registration name inside the reference:
        const regs = [...registrations, { id: 9, name: 'Davonte Moore', paid: false, draw_status: 'drawn', track_name: 'Track A' }];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration.id).toBe(9);
        expect(unmatched[0].suggestionReason).toBe('no_separator');
        expect(filtered).toHaveLength(0);
    });

    it('suggests a match for rows with a prefix typo (prefix mismatch)', () => {
        const rows = [makeRow('Teilnahnebeitrag SPX26 - Ansel Purdy')];
        const regs = [...registrations, { id: 5, name: 'Ansel Purdy', paid: false, draw_status: 'not_drawn', track_name: 'Track B' }];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration.id).toBe(5);
        expect(unmatched[0].suggestionReason).toBe('prefix_mismatch');
    });

    it('keeps exact matches as exact matches, not suggestions', () => {
        const rows = [makeRow('Teilnahmebeitrag SPX26 - Maria Muster')];
        const { matched, unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag', registrations);

        expect(matched).toHaveLength(1);
        expect(unmatched).toHaveLength(0);
    });

    it('routes name-typo rows through the suggestion finder (whole-name substring hit)', () => {
        // registered as 'Mr. Reed Corwin'; reference has the bare name
        const rows = [makeRow('Teilnahmebeitrag SPX26 - Reed Corwin')];
        const regs = [...registrations, { id: 7, name: 'Mr. Reed Corwin', paid: false, draw_status: 'not_drawn', track_name: 'Track A' }];
        const { unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration.id).toBe(7);
        expect(unmatched[0].suggestionReason).toBe('exact_miss');
    });

    it('keeps the name from the reference as extractedName when suggesting a registration', () => {
        const rows = [makeRow('Teilnahmebeitrag SPX26 - Reed Corwin')];
        const regs = [...registrations, { id: 7, name: 'Mr. Reed Corwin', paid: false, draw_status: 'not_drawn', track_name: 'Track A' }];
        const { unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(unmatched[0].extractedName).toBe('Reed Corwin');
    });

    it('has no extractedName for a fallback suggestion without the separator', () => {
        const rows = [makeRow('Teilnahmebeitrag SPX26 Davonte Moore')];
        const regs = [...registrations, { id: 9, name: 'Davonte Moore', paid: false, draw_status: 'drawn', track_name: 'Track A' }];
        const { unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(unmatched[0].extractedName).toBeNull();
    });

    it('marks rows with several contained names as ambiguous without a suggestion', () => {
        const rows = [makeRow('Teilnahmebeitrag fuer Maria Muster und Klaus Weber')];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag', registrations);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration).toBeNull();
        expect(unmatched[0].suggestionReason).toBe('ambiguous');
        expect(unmatched[0].ambiguousCandidates.map(c => c.id)).toEqual([1, 2]);
        expect(filtered).toHaveLength(0);
    });

    it('still filters rows that contain no registration name at all', () => {
        const rows = [
            makeRow('Spende an Steppenreg e.V.'),
            makeRow('REWE Lieferservice Bestellung'),
            makeRow('Miete Vereinsheim Oktober'),
        ];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag', registrations);

        expect(filtered).toHaveLength(3);
        expect(unmatched).toHaveLength(0);
    });

    it('does not lose the row when the extracted name is a substring of a longer registration name', () => {
        // 'Reed Corwin' vs registered 'Mr. Reed Corwin' — handled exactly once:
        // strict path fails, suggestion succeeds, still a manual confirm.
        const rows = [makeRow('Teilnahmebeitrag SPX26 - Reed Corwin')];
        const regs = [...registrations, { id: 7, name: 'Mr. Reed Corwin', paid: false, draw_status: 'not_drawn', track_name: 'Track A' }];
        const { matched, unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag', regs);

        expect(matched).toHaveLength(0);
        expect(unmatched).toHaveLength(1);
    });
});

describe('noise rows that mention a registered name', () => {
    it('does not suggest rows whose reference lacks the event prefix', () => {
        const rows = [makeRow('Mitgliedsbeitrag 2026 Maria Muster')];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag SPX26', registrations);

        expect(unmatched).toHaveLength(0);
        expect(filtered).toHaveLength(1);
    });

    it('does not suggest rows for another edition of the event', () => {
        const rows = [makeRow('Teilnahmebeitrag SPX25 - Maria Muster')];
        const { unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag SPX26', registrations);

        expect(unmatched).toHaveLength(0);
        expect(filtered).toHaveLength(1);
    });

    it('still suggests rows with a small typo in the prefix', () => {
        const rows = [makeRow('Teilnahnebeitrag SPX26 - Maria Muster')];
        const { unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag SPX26', registrations);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration.id).toBe(1);
    });

    it('still suggests rows where the event code carries a suffix', () => {
        const rows = [makeRow('Teilnahmebeitrag SPX26-V1 Maria Muster Hinweis')];
        const { unmatched } = filterAndMatch(rows, 'Teilnahmebeitrag SPX26', registrations);

        expect(unmatched).toHaveLength(1);
        expect(unmatched[0].suggestedRegistration.id).toBe(1);
    });

    it('filters outgoing payments even when they follow the event convention', () => {
        const rows = [
            makeRow('Teilnahmebeitrag SPX26 - Maria Muster', { 'Betrag': '-35,00' }),
            makeRow('Teilnahmebeitrag SPX26 Maria Muster', { 'Betrag': '-35,00' }),
        ];
        const { matched, unmatched, filtered } = filterAndMatch(rows, 'Teilnahmebeitrag SPX26', registrations);

        expect(matched).toHaveLength(0);
        expect(unmatched).toHaveLength(0);
        expect(filtered).toHaveLength(2);
    });
});
