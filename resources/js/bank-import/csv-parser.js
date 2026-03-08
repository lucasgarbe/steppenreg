/**
 * Parses a semicolon-delimited MT940-style German bank export CSV.
 *
 * Handles:
 * - UTF-8 BOM (\uFEFF)
 * - Windows CRLF line endings
 * - Empty trailing lines
 *
 * @param {string} content - Raw CSV file content as a string
 * @returns {Array<Object>} Array of row objects keyed by header name
 */
export function parseCsv(content) {
    if (!content || content.trim() === '') {
        return [];
    }

    // Strip UTF-8 BOM if present
    const cleaned = content.startsWith('\uFEFF') ? content.slice(1) : content;

    // Normalise line endings
    const lines = cleaned.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');

    if (lines.length < 2) {
        return [];
    }

    const headers = lines[0].split(';');

    const rows = [];

    for (let i = 1; i < lines.length; i++) {
        const line = lines[i];

        // Skip empty lines
        if (line.trim() === '') {
            continue;
        }

        const values = line.split(';');
        const row = {};

        for (let j = 0; j < headers.length; j++) {
            row[headers[j]] = values[j] !== undefined ? values[j] : '';
        }

        rows.push(row);
    }

    return rows;
}
