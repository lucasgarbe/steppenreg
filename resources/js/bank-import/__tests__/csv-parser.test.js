import { describe, it, expect } from 'vitest';
import { parseCsv } from '../csv-parser.js';

const HEADERS = 'Bezeichnung Auftragskonto;IBAN Auftragskonto;BIC Auftragskonto;Bankname Auftragskonto;Buchungstag;Valutadatum;Name Zahlungsbeteiligter;IBAN Zahlungsbeteiligter;BIC (SWIFT-Code) Zahlungsbeteiligter;Buchungstext;Verwendungszweck;Betrag;Waehrung;Saldo nach Buchung;Bemerkung;Gekennzeichneter Umsatz;Glaeubiger ID;Mandatsreferenz';

const makeRow = (overrides = {}) => {
    const defaults = {
        'Bezeichnung Auftragskonto': 'Mein Konto',
        'IBAN Auftragskonto': 'DE89370400440532013000',
        'BIC Auftragskonto': 'COBADEFFXXX',
        'Bankname Auftragskonto': 'Testbank',
        'Buchungstag': '01.03.2025',
        'Valutadatum': '01.03.2025',
        'Name Zahlungsbeteiligter': 'Maria Muster',
        'IBAN Zahlungsbeteiligter': 'DE12345678901234567890',
        'BIC (SWIFT-Code) Zahlungsbeteiligter': 'TESTDE88',
        'Buchungstext': 'Ueberweisung',
        'Verwendungszweck': 'Steppenreg 2025 - Maria Muster',
        'Betrag': '50,00',
        'Waehrung': 'EUR',
        'Saldo nach Buchung': '1234,56',
        'Bemerkung': '',
        'Gekennzeichneter Umsatz': '',
        'Glaeubiger ID': '',
        'Mandatsreferenz': '',
    };
    const merged = { ...defaults, ...overrides };
    return Object.values(merged).join(';');
};

describe('parseCsv', () => {
    it('parses a semicolon-delimited row into an object with correct keys', () => {
        const csv = `${HEADERS}\n${makeRow()}`;
        const rows = parseCsv(csv);

        expect(rows).toHaveLength(1);
        expect(rows[0]['Buchungstag']).toBe('01.03.2025');
        expect(rows[0]['Name Zahlungsbeteiligter']).toBe('Maria Muster');
        expect(rows[0]['Verwendungszweck']).toBe('Steppenreg 2025 - Maria Muster');
        expect(rows[0]['Betrag']).toBe('50,00');
    });

    it('handles UTF-8 BOM at the start of the file', () => {
        const csv = `\uFEFF${HEADERS}\n${makeRow()}`;
        const rows = parseCsv(csv);

        expect(rows).toHaveLength(1);
        expect(rows[0]['Buchungstag']).toBe('01.03.2025');
    });

    it('handles Windows CRLF line endings', () => {
        const csv = `${HEADERS}\r\n${makeRow()}\r\n`;
        const rows = parseCsv(csv);

        expect(rows).toHaveLength(1);
        expect(rows[0]['Betrag']).toBe('50,00');
    });

    it('skips empty trailing rows', () => {
        const csv = `${HEADERS}\n${makeRow()}\n\n\n`;
        const rows = parseCsv(csv);

        expect(rows).toHaveLength(1);
    });

    it('parses multiple rows correctly', () => {
        const row1 = makeRow({ 'Name Zahlungsbeteiligter': 'Anna Schmidt', 'Verwendungszweck': 'Steppenreg 2025 - Anna Schmidt' });
        const row2 = makeRow({ 'Name Zahlungsbeteiligter': 'Klaus Weber', 'Verwendungszweck': 'Anderer Zweck' });
        const csv = `${HEADERS}\n${row1}\n${row2}`;
        const rows = parseCsv(csv);

        expect(rows).toHaveLength(2);
        expect(rows[0]['Name Zahlungsbeteiligter']).toBe('Anna Schmidt');
        expect(rows[1]['Name Zahlungsbeteiligter']).toBe('Klaus Weber');
    });

    it('returns empty array for header-only input', () => {
        const rows = parseCsv(HEADERS);
        expect(rows).toHaveLength(0);
    });

    it('returns empty array for empty input', () => {
        const rows = parseCsv('');
        expect(rows).toHaveLength(0);
    });
});
