/**
 * Computes a SHA-256 hash for a bank transfer row using the stable identity fields:
 * Buchungstag + Betrag + Verwendungszweck.
 *
 * Used to persist confirmed rows in localStorage across page refreshes.
 *
 * @param {Object} row - A parsed CSV row object
 * @returns {Promise<string>} Hex-encoded SHA-256 hash string (64 chars)
 */
export async function hashRow(row) {
    const input = [
        row['Buchungstag'] ?? '',
        row['Betrag'] ?? '',
        row['Verwendungszweck'] ?? '',
    ].join('|');

    const encoder = new TextEncoder();
    const data = encoder.encode(input);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}
