import { describe, it, expect } from 'vitest';
import { classifyMatched } from '../transfer-status.js';

const item = (registrationId, fingerprint, paid = false) => ({
    fingerprint,
    registration: { id: registrationId, name: `Person ${registrationId}`, paid },
});

const classify = (items, { confirmed = [], withTransfer = [] } = {}) =>
    classifyMatched(items, {
        confirmedFingerprints: new Set(confirmed),
        registrationIdsWithTransfer: new Set(withTransfer),
    });

describe('classifyMatched', () => {
    it('marks an unpaid registration as unpaid', () => {
        expect(classify([item(1, 'fp-a')])).toEqual(['unpaid']);
    });

    it('marks a transfer already in the ledger as booked', () => {
        expect(classify([item(1, 'fp-a', true)], { confirmed: ['fp-a'], withTransfer: [1] })).toEqual(['booked']);
    });

    it('marks a paid registration without a booked transfer as paid_unlinked', () => {
        expect(classify([item(1, 'fp-a', true)])).toEqual(['paid_unlinked']);
    });

    it('marks another transfer for a registration with a booked transfer as duplicate', () => {
        expect(classify([item(1, 'fp-b', true)], { confirmed: ['fp-a'], withTransfer: [1] })).toEqual(['duplicate']);
    });

    it('marks the second transfer for the same registration in one file as duplicate', () => {
        expect(classify([item(1, 'fp-a'), item(1, 'fp-b')])).toEqual(['unpaid', 'duplicate']);
    });

    it('treats a booked transfer as the claim even when it appears after a new one', () => {
        expect(classify([item(1, 'fp-new', true), item(1, 'fp-booked', true)], { confirmed: ['fp-booked'], withTransfer: [1] }))
            .toEqual(['duplicate', 'booked']);
    });
});
