/**
 * Classifies auto-matched transfers against the confirmation ledger.
 *
 * A registration has at most one booked transfer (see ConfirmPaymentsAction):
 * - booked:        this transfer is in the ledger already
 * - duplicate:     the registration already has a booked transfer, or an earlier
 *                  row in this file claims it — the money needs manual handling
 * - paid_unlinked: the registration is paid without a booked transfer (e.g. marked
 *                  paid by hand); confirming links this transfer as its payment
 * - unpaid:        confirming marks the registration paid
 *
 * @param {Array<{fingerprint: string, registration: {id: number, paid: boolean}}>} matched
 * @param {{confirmedFingerprints: Set<string>, registrationIdsWithTransfer: Set<number>}} ledger
 * @returns {Array<'booked'|'duplicate'|'paid_unlinked'|'unpaid'>} One status per item, in order
 */
export function classifyMatched(matched, { confirmedFingerprints, registrationIdsWithTransfer }) {
    const claimed = new Set(registrationIdsWithTransfer);

    return matched.map(item => {
        if (confirmedFingerprints.has(item.fingerprint)) {
            return 'booked';
        }

        const registrationId = item.registration.id;

        if (claimed.has(registrationId)) {
            return 'duplicate';
        }

        claimed.add(registrationId);

        return item.registration.paid ? 'paid_unlinked' : 'unpaid';
    });
}
