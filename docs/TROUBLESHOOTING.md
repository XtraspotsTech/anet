# Troubleshooting: the errors you will actually hit

In rough order of how often they happen. Most "Authorize.Net is broken" tickets are one of these.

---

## E00007 — "User authentication failed"

The number one time-waster. Causes, in order of likelihood:

1. **Sandbox credentials against the production URL, or vice versa.** Sandbox and production are entirely separate systems with separate keys. `apitest.authorize.net` for sandbox, `api.authorize.net` for production. Same for Accept.js: `jstest.` vs `js.`.
2. Transaction Key was regenerated (someone clicked the button in the merchant interface) and the old one died. Generate a fresh one and update the config.
3. Whitespace pasted around the key. Trim it.

Run the repo's `scripts/verify-credentials.sh` before debugging anything else. If it returns `resultCode: Ok`, the problem is not authentication and you have just saved an hour.

## Cryptic parse error / E00003 on a request that looks fine

The API is XML under a JSON translation layer, so **element order inside the JSON is enforced**, which no other API you use does. If you built the request object yourself and alphabetised or reshuffled fields, that is the bug. Match the order in the API reference exactly, or use an official SDK.

## JSON.parse fails on the response

The API prefixes responses with a UTF-8 BOM. Strict parsers choke on it. Strip it before parsing; both code samples in this repo do.

## Transactions "succeed" but never settle, or vanish

The live account has **Test Mode switched on** inside the merchant interface (Account → Test Mode). A live account in test mode returns approvals but processes and stores nothing. This is a different thing from the sandbox. Toggle it off for real processing, and check it first whenever "money isn't arriving."

## Everything worked in sandbox, real cards decline at go-live

Usually not the integration. The live account's fraud settings are too strict for real traffic:

- **AVS filter** rejecting mismatched ZIP/address (Account → Settings → Security Settings → Address Verification Service)
- **CVV filter** doing the same for card codes
- Daily velocity or amount limits set during underwriting

Look at the decline reason on the transaction detail before touching code. A spike of "AVS mismatch" declines in week one is a settings conversation, not a bug.

## Orders marked paid that never got paid

Two classic causes:

1. **Trusting the return URL.** A customer can reach the receipt URL without paying, and can close the tab after paying. Confirm via webhook (`net.authorize.payment.authcapture.created`) or `getTransactionDetailsRequest` before marking an order paid.
2. **Treating `responseCode: 4` as approved.** Code 4 is *held for review* by the fraud filter. It may never settle. Handle it as pending. Codes: 1 approved, 2 declined, 3 error, 4 held.

## "A duplicate transaction has been submitted"

The gateway flags identical transactions submitted within a short window, which surfaces during testing when you retry the same amount repeatedly. Vary the amount in cents between test runs, or pass a unique `refId`. In production this protection is saving you from double-charges caused by double-clicks, so do not disable it; block double submission in your UI instead.

## Accept.js token rejected or "invalid"

- The opaqueData token is **single-use and expires in roughly 15 minutes**. A failed charge means re-tokenise in the browser, not retry the same token.
- Public Client Key and API Login ID must both be from the **same environment** as the Accept.js script you loaded. Sandbox key with production script fails in confusing ways.

## Refund fails on an old order after migration

Expected. That transaction lives on the old gateway. Refund it through the old gateway's interface or its still-installed plugin. This is exactly why the old plugin stays installed, disabled for new payments, until the refund window closes. See MIGRATION-CHECKLIST.md, Check 4.

## Webhook never fires

- Endpoint must be HTTPS with a valid certificate and return 200 quickly. Do heavy work async.
- Registered in the same environment you are testing (sandbox webhooks for sandbox transactions).
- Verify the signature header on receipt; also confirms you configured the signature key at all.
- Webhooks can be delayed. For order confirmation, poll `getTransactionDetailsRequest` as a fallback rather than waiting forever.

## Where to look when nothing above fits

The transaction detail in the merchant interface shows the raw response, AVS/CVV results, and which filter acted. Ninety percent of mystery behaviour is explained on that page. Check it before adding log statements.

When escalating, include: transaction ID, timestamp with timezone, API Login ID (**never** the Transaction Key), and the full raw response.
