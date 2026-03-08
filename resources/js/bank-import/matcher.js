/**
 * Filters CSV rows by event prefix and performs exact name matching
 * against the registration list.
 *
 * Matching rules:
 * - Only rows where Verwendungszweck contains the event prefix (case-insensitive) are processed
 * - The registrant name is extracted from the part after the first " - " separator
 * - Matching is exact (case-insensitive, trimmed) — no fuzzy matching
 * - Rows that pass the filter but have no exact match are returned as unmatched
 *
 * @param {Array<Object>} rows - Parsed CSV rows from csv-parser
 * @param {string} eventPrefix - The event name prefix to filter on (e.g. "Steppenreg 2025")
 * @param {Array<{id: number, name: string, payed: boolean, draw_status: string, track_name: string}>} registrations
 * @returns {{ matched: Array, unmatched: Array, filtered: Array }}
 */
export function filterAndMatch(rows, eventPrefix, registrations) {
    const prefix = eventPrefix.toLowerCase();
    const separator = ' - ';

    const matched = [];
    const unmatched = [];
    const filtered = [];

    for (const row of rows) {
        const reference = row['Verwendungszweck'] ?? '';

        // Step 1: filter by prefix (case-insensitive) AND require the " - " separator.
        // A row must match "Prefix - Name" format to be considered a registration transfer.
        // Rows that merely mention the event name in another context are filtered out.
        const referenceLower = reference.toLowerCase();
        const separatorIndex = reference.indexOf(separator);
        const prefixBeforeSeparator = separatorIndex !== -1
            ? referenceLower.slice(0, separatorIndex)
            : referenceLower;

        if (!prefixBeforeSeparator.includes(prefix) || separatorIndex === -1) {
            filtered.push(row);
            continue;
        }

        // Step 2: extract name — everything after the first " - "
        const extractedName = reference.slice(separatorIndex + separator.length).trim();

        // Step 3: exact match (case-insensitive, trimmed)
        const normalised = extractedName.toLowerCase();
        const registration = registrations.find(
            r => r.name.toLowerCase().trim() === normalised
        ) ?? null;

        if (registration) {
            matched.push({ row, extractedName, registration });
        } else {
            unmatched.push({ row, extractedName, registration: null });
        }
    }

    return { matched, unmatched, filtered };
}
