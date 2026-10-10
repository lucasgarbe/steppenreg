/**
 * Filters CSV rows by event prefix and matches them against registrations.
 *
 * Matching rules:
 * - Primary: rows where Verwendungszweck matches "<eventPrefix> - <name>" format
 *   (prefix case-insensitive anywhere in the part before the first " - " separator)
 *   are matched exactly (case-insensitive, trimmed) against registration names.
 * - Fallback suggestions: rows that fail the primary filter but still mention the
 *   event (see mentionsEvent) are scanned for the exact full name of any
 *   registration appearing anywhere in the reference (case-insensitive, trimmed).
 *   A single hit produces a *suggested* match that must still be confirmed
 *   manually in Phase 2 — loose matching only suggests, it never auto-confirms.
 * - Outgoing payments (negative amounts) are never payments for the event.
 * - Rows with no plausible registration anywhere are filtered out.
 *
 * @param {Array<Object>} rows - Parsed CSV rows from csv-parser
 * @param {string} eventPrefix - The event name prefix to filter on (e.g. "Teilnahmebeitrag")
 * @param {Array<{id: number, name: string, paid: boolean, draw_status: string, track_name: string}>} registrations
 * @returns {{ matched: Array, unmatched: Array, filtered: Array }}
 */
const tokenize = text => text.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter(Boolean);

/**
 * Levenshtein distance capped at 2 — enough to tell "one typo" from "different word".
 */
function editDistanceUpTo2(a, b) {
    if (Math.abs(a.length - b.length) > 1) {
        return 2;
    }
    let previous = Array.from({ length: b.length + 1 }, (_, i) => i);
    for (let i = 1; i <= a.length; i++) {
        const current = [i];
        for (let j = 1; j <= b.length; j++) {
            current[j] = Math.min(
                previous[j] + 1,
                current[j - 1] + 1,
                previous[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1),
            );
        }
        previous = current;
    }
    return Math.min(previous[b.length], 2);
}

/**
 * Whether a reference mentions the event: every word of the prefix appears in it.
 * Words with digits (event codes like "SPX26") must match exactly, so another
 * edition ("SPX25") does not count; longer plain words tolerate one typo.
 */
export function mentionsEvent(reference, eventPrefix) {
    const referenceTokens = tokenize(reference);

    return tokenize(eventPrefix).every(prefixToken => {
        if (/\d/.test(prefixToken) || prefixToken.length < 5) {
            return referenceTokens.includes(prefixToken);
        }
        return referenceTokens.some(token => editDistanceUpTo2(token, prefixToken) <= 1);
    });
}

const isOutgoing = row => (row['Betrag'] ?? '').trim().startsWith('-');

export function filterAndMatch(rows, eventPrefix, registrations) {
    const prefix = eventPrefix.toLowerCase().trim();
    const separator = ' - ';

    const exactRegistry = registrations.map(r => ({
        registration: r,
        normalizedName: r.name.toLowerCase().trim(),
    }));

    // Loose fallback: find registrations whose exact full name appears in the
    // reference (either direction, e.g. bare name vs '/Mr. Reed Corwin/')
    const suggestForReference = (reference, extractedName = null) => {
        const referenceLower = reference.toLowerCase().trim();
        const extractedLower = extractedName?.toLowerCase().trim();
        const hits = exactRegistry.filter(entry =>
            (entry.normalizedName && referenceLower.includes(entry.normalizedName)) ||
            (extractedLower && entry.normalizedName.includes(extractedLower))
        );
        return hits.map(entry => entry.registration);
    };

    const matched = [];
    const unmatched = [];
    const filtered = [];

    const pushUnmatched = (row, extractedName, candidates, reason) => {
        if (candidates.length === 1) {
            unmatched.push({
                row,
                extractedName,
                registration: null,
                suggestedRegistration: candidates[0],
                suggestionReason: reason,
                ambiguousCandidates: [],
            });
        } else if (candidates.length > 1) {
            unmatched.push({
                row,
                extractedName,
                registration: null,
                suggestedRegistration: null,
                suggestionReason: 'ambiguous',
                ambiguousCandidates: candidates,
            });
        } else {
            unmatched.push({
                row,
                extractedName,
                registration: null,
                suggestedRegistration: null,
                suggestionReason: reason,
                ambiguousCandidates: [],
            });
        }
    };

    for (const row of rows) {
        const reference = row['Verwendungszweck'] ?? '';

        if (isOutgoing(row)) {
            filtered.push(row);
            continue;
        }

        // Step 1: primary filter — prefix before the separator AND separator present
        const separatorIndex = reference.indexOf(separator);
        const referenceLower = reference.toLowerCase();
        const prefixBeforeSeparator = separatorIndex !== -1
            ? referenceLower.slice(0, separatorIndex)
            : referenceLower;

        if (prefix && prefixBeforeSeparator.includes(prefix) && separatorIndex !== -1) {
            // Step 2: exact match (case-insensitive, trimmed) on the name after the separator
            const extractedName = reference.slice(separatorIndex + separator.length).trim();
            const registration = registrations.find(
                r => r.name.toLowerCase().trim() === extractedName.toLowerCase()
            ) ?? null;

            if (registration) {
                matched.push({ row, extractedName, registration });
            } else {
                pushUnmatched(row, extractedName, suggestForReference(reference, extractedName),
                    separatorIndex === -1 ? 'no_separator' : 'exact_miss');
            }
            continue;
        }

        // Step 3: loose fallback — only for rows that still mention the event;
        // suggest, never auto-confirm
        const candidates = mentionsEvent(reference, eventPrefix) ? suggestForReference(reference) : [];

        if (candidates.length === 1) {
            unmatched.push({
                row,
                extractedName: null,
                registration: null,
                suggestedRegistration: candidates[0],
                suggestionReason: separatorIndex === -1 ? 'no_separator' : 'prefix_mismatch',
                ambiguousCandidates: [],
            });
        } else if (candidates.length > 1) {
            unmatched.push({
                row,
                extractedName: null,
                registration: null,
                suggestedRegistration: null,
                suggestionReason: 'ambiguous',
                ambiguousCandidates: candidates,
            });
        } else {
            filtered.push(row);
        }
    }

    return { matched, unmatched, filtered };
}
