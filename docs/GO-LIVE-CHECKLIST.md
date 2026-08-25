# Go-live checklist

Run this with the client watching. It takes ten minutes and it is the difference between a smooth cutover and a 2am phone call.

## Before cutover

- [ ] Merchant account approved and the gateway is linked to the correct MID
- [ ] Production API Login ID and a freshly generated Transaction Key are in the client's environment config, not in the codebase
- [ ] `.env` is gitignored, and no key was ever committed. If one was, rotate it now, not later
- [ ] Endpoints switched from `apitest` to `api`, and Accept.js from `jstest` to `js`
- [ ] Test Mode is OFF in the live merchant interface
- [ ] The old payment method is still enabled and working
- [ ] Someone has confirmed which bank account funds settle into. Ask out loud. It has been wrong before

## Verify with real money

- [ ] Run one real card transaction for a small amount on the live site
- [ ] Confirm it appears in the live merchant interface
- [ ] Void or refund it
- [ ] Confirm the refund also appears

Do not skip this. A sandbox test proves the code works. Only a live transaction proves the account is wired to the right MID.

## Reliability

- [ ] Webhook endpoint registered and returning 200
- [ ] Webhook signature verification implemented, not just accepting any POST
- [ ] Order status only moves to paid on a confirmed transaction, never on the customer hitting the return URL
- [ ] `responseCode: 4` is handled as pending, not as paid
- [ ] Gateway timeouts are logged with enough detail to reconcile manually
- [ ] Duplicate submission is blocked, browser side and server side

## Handover to the merchant

- [ ] They can log in to the merchant interface
- [ ] They have been shown how to issue a refund and a void
- [ ] They know where to find the daily batch report
- [ ] They know who to call, with a name and a number, not a support form
- [ ] Email receipts configured the way they want

## First week

- [ ] Check the batch settled the morning after go-live
- [ ] Confirm funds actually landed in the bank, on the day expected
- [ ] Watch decline rates for the first few days. A sudden spike usually means an AVS or fraud filter is set too tight, not a broken integration

## Only after all of the above

- [ ] Disable the old payment method
