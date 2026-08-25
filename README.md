# Authorize.Net migration kit

For developers moving a client's store onto Authorize.Net from a gateway it already uses.

Installing a payment gateway is the easy part, and the [official docs](https://developer.authorize.net) already cover it better than we could. What nobody documents is what breaks when a store that is *already taking money* switches gateways. That is what this repo is for.

If the store has never taken a card payment before, none of the migration risk applies. Skip to [Pick your path](#pick-your-path).

## Start with the audit, not the install

Before you quote the client, before you install anything, run the 15 minute read-only audit in [docs/MIGRATION-CHECKLIST.md](docs/MIGRATION-CHECKLIST.md). It needs database access, and it tells you whether this job is one hour or three weeks.

| What the audit finds | Verdict | Realistic time |
|---|---|---|
| No saved cards, no subscriptions | GREEN | 1 to 2 hours plus testing |
| Saved cards, no subscriptions | YELLOW | Half a day plus a customer notice |
| Any live recurring billing on the old gateway | RED | 2 to 4 weeks, card re-collection first |

Quoting a RED store as if it were GREEN is how these projects go wrong, and the client remembers who quoted it.

## The three things that break in every migration

1. **Saved cards die.** Tokens held by Payflow, Braintree, or 2Checkout are meaningless to Authorize.Net. Every customer with a card on file loses it at cutover.
2. **Subscriptions fail silently.** Active subscriptions are bound to old gateway tokens. The next renewal fails, the merchant sees it weeks later as churn, and you get blamed for it.
3. **Old refunds still need the old gateway.** You cannot refund a Payflow transaction through Authorize.Net. The old plugin stays installed, disabled for new payments, until the refund window closes.

Say all three out loud to the client before you start. Gateway-specific notes for the three sources we see most, including the 2Checkout merchant-of-record tax trap, are in [docs/FROM-LEGACY-GATEWAYS.md](docs/FROM-LEGACY-GATEWAYS.md).

## Pick your path

| Client's site | Use | Time |
|---|---|---|
| WooCommerce, Magento, BigCommerce | [woocommerce/SETUP.md](woocommerce/SETUP.md) | ~20 min, no code |
| Custom site, simplest possible integration | [accept-hosted/](accept-hosted/) | ~45 min |
| Custom site, needs its own checkout UI | [accept-js/](accept-js/) | ~2 hours |
| Shopify | Possible, but Shopify charges an extra fee on third-party gateways. Read [docs/DEV-FAQ.md](docs/DEV-FAQ.md) before promising the client a saving |
| Squarespace, Wix | Not possible. They do not support external gateways at all |

Accept Hosted is the default. Reach for Accept.js only when the checkout form has to live on the client's own domain. Do not use AIM or SIM, they are deprecated, and most tutorials still online teach them.

## Before you start

You do not need the merchant account to build. Get a free sandbox and work against it today.

1. Sandbox account: https://developer.authorize.net/testaccount/
2. In the sandbox merchant interface, go to **Account, then API Credentials & Keys**
3. Copy the **API Login ID** and generate a **Transaction Key**
4. For Accept.js only, also generate a **Public Client Key** on the same page

```bash
cp .env.example .env
# fill in your sandbox credentials
```

Verify the credentials before you write a line of code:

```bash
./scripts/verify-credentials.sh
```

You want `resultCode: Ok`. If you get it, the hard part is over. If you do not, it is almost always [E00007](docs/TROUBLESHOOTING.md), and it is almost always a sandbox key pointed at production.

## Cutover

Run [docs/GO-LIVE-CHECKLIST.md](docs/GO-LIVE-CHECKLIST.md) with the client watching. Ten minutes. It covers the parallel run, the $1 live test, the refund path on old orders, and the date the old plugin finally comes out.

## When something breaks

[docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) lists the real errors in rough order of how often they happen: E00007 environment mix-ups, JSON field order, Test Mode left on in production, AVS filter declines that look like gateway failures, and `responseCode 4` held for review.

## Reference

- Developer center: https://developer.authorize.net
- API reference with live console: https://developer.authorize.net/api/reference/index.html
- Official SDKs: https://github.com/AuthorizeNet
- Sandbox test cards: [docs/TEST-CARDS.md](docs/TEST-CARDS.md)
- Honest answers to the usual questions: [docs/DEV-FAQ.md](docs/DEV-FAQ.md)

## Two things that will save you an hour

**The API is XML under the hood.** It accepts JSON, but element order is enforced, which no other API you use does. A cryptic parse error means you reordered a field. Use an official SDK and this stops being your problem.

**Sandbox and production share nothing.** Different URLs, different credentials, different logins. Sandbox keys will never work against production, and the error message will not make that obvious.
