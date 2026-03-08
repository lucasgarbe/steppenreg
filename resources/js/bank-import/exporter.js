/**
 * Columns included in the unresolved rows export.
 * Matches the bank CSV format for easy comparison.
 */
const EXPORT_COLUMNS = [
    'Buchungstag',
    'Name Zahlungsbeteiligter',
    'Verwendungszweck',
    'Betrag',
    'Waehrung',
];

/**
 * Escapes a single CSV field value.
 * Wraps the value in double quotes if it contains the delimiter or double quotes.
 *
 * @param {string} value
 * @returns {string}
 */
function escapeField(value) {
    const str = value ?? '';
    if (str.includes(';') || str.includes('"') || str.includes('\n')) {
        return '"' + str.replace(/"/g, '""') + '"';
    }
    return str;
}

/**
 * Builds a semicolon-delimited CSV string for the unresolved rows.
 *
 * @param {Array<{row: Object, extractedName: string}>} unresolvedItems
 * @returns {string} CSV content as a string
 */
export function buildUnresolvedCsv(unresolvedItems) {
    const header = EXPORT_COLUMNS.join(';');

    const dataLines = unresolvedItems.map(item => {
        return EXPORT_COLUMNS.map(col => escapeField(item.row[col] ?? '')).join(';');
    });

    return [header, ...dataLines].join('\n');
}

/**
 * Triggers a client-side download of the unresolved rows as a CSV file.
 *
 * @param {Array<{row: Object, extractedName: string}>} unresolvedItems
 * @param {string} [filename='unresolved-transfers.csv']
 */
export function downloadUnresolvedCsv(unresolvedItems, filename = 'unresolved-transfers.csv') {
    const csv = buildUnresolvedCsv(unresolvedItems);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.style.display = 'none';
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();

    setTimeout(() => {
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }, 100);
}
