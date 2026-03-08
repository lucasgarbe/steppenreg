/**
 * Filters CSV rows by event prefix and performs exact name matching
 * against the registration list.
 *
 * Matching rules:
 * - Only rows where Verwendungszweck contains the event prefix (case-insensitive) are processed
 * - Primary path: name is extracted from the part after the first " - " separator
 * - Fallback path: if the separator is absent but the reference starts with the prefix,
 *   the name is extracted by stripping the prefix from the start of the reference
 * - Matching is exact (case-insensitive, trimmed) — no fuzzy matching
 * - Rows that pass the filter but have no exact match are returned as unmatched
 * - Each result item carries a matchType: 'exact' | 'fallback' field
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
        const referenceLower = reference.toLowerCase();
        const separatorIndex = reference.indexOf(separator);

        let extractedName;
        let matchType;

        if (separatorIndex !== -1) {
            // Primary path: " - " separator is present.
            // The prefix must appear in the text before the separator.
            const prefixBeforeSeparator = referenceLower.slice(0, separatorIndex);

            if (!prefixBeforeSeparator.includes(prefix)) {
                filtered.push(row);
                continue;
            }

            extractedName = reference.slice(separatorIndex + separator.length).trim();
            matchType = 'exact';
        } else {
            // Fallback path: no separator. Qualify only if the reference starts with the prefix.
            // Requiring a leading position mirrors the "prefix before separator" rule and keeps
            // rows like "Spende allgemein Steppenreg e.V." in filtered.
            if (!referenceLower.startsWith(prefix)) {
                filtered.push(row);
                continue;
            }

            extractedName = reference.slice(prefix.length).trim();
            matchType = 'fallback';
        }

        // Exact match (case-insensitive, trimmed)
        const normalised = extractedName.toLowerCase();
        const registration = registrations.find(
            r => r.name.toLowerCase().trim() === normalised
        ) ?? null;

        if (registration) {
            matched.push({ row, extractedName, registration, matchType });
        } else {
            unmatched.push({ row, extractedName, registration: null, matchType });
        }
    }

    return { matched, unmatched, filtered };
}
