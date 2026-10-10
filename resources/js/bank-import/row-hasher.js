/**
 * Computes a SHA-256 hash for a bank transfer row using the stable identity fields:
 * Buchungstag + Betrag + Verwendungszweck.
 *
 * Identical rows (e.g. someone paying twice on the same day) only differ by their
 * occurrence index within the file. The first occurrence keeps the plain hash so
 * fingerprints recorded before occurrences were counted stay valid.
 *
 * @param {Object} row - A parsed CSV row object
 * @param {number} occurrence - How many identical rows came before this one in the file
 * @returns {Promise<string>} Hex-encoded SHA-256 hash string (64 chars)
 */
export async function hashRow(row, occurrence = 0) {
    const fields = [
        row['Buchungstag'] ?? '',
        row['Betrag'] ?? '',
        row['Verwendungszweck'] ?? '',
    ];

    if (occurrence > 0) {
        fields.push(String(occurrence));
    }

    const encoder = new TextEncoder();
    const data = encoder.encode(fields.join('|'));
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

/**
 * Fingerprints every row of a file, keeping identical rows distinct.
 *
 * @param {Array<Object>} rows - Parsed CSV rows in file order
 * @returns {Promise<Array<string>>} One fingerprint per row, in the same order
 */
export async function fingerprintRows(rows) {
    const seen = new Map();

    return Promise.all(rows.map(row => {
        const identity = [row['Buchungstag'], row['Betrag'], row['Verwendungszweck']].join('|');
        const occurrence = seen.get(identity) ?? 0;
        seen.set(identity, occurrence + 1);
        return hashRow(row, occurrence);
    }));
}
