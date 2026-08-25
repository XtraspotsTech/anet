# Authorize.Net Starter Kit

Everything you need to add card payments to a client site with an Authorize.Net gateway. Sandbox to live in under an hour.

If you have integrated any gateway before, you can skip to whichever folder matches the client's stack. Nothing here is unusual.

## Pick your path

| Client's site | Use | Time |
|---|---|---|
| WooCommerce, Magento, BigCommerce | `woocommerce/` | ~20 min, no code |
| Custom site, want the simplest possible integration | `accept-hosted/` | ~45 min |
| Custom site, need your own checkout UI | `accept-js/` | ~2 hours |
| Shopify | Read `docs/DEV-FAQ.md` first. Shopify charges an extra fee for third-party gateways. |

## Before you start

1. Get a free sandbox account: https://developer.authorize.net/testaccount/
2. Sign in to the sandbox merchant interface, go to **Account → API Credentials & Keys**
3. Copy the **API Login ID** and generate a **Transaction Key**
4. For Accept.js only, also generate a **Public Client Key** on the same page

```bash
cp .env.example .env
# fill in your sandbox credentials
```

Verify the credentials work before writing anything:

```bash
./scripts/verify-credentials.sh
```

You should get back `resultCode: Ok`. If you do, the hard part is over.

## Reference

- Developer center: https://developer.authorize.net
- API reference with live console: https://developer.authorize.net/api/reference/index.html
- Official SDKs: https://github.com/AuthorizeNet
- Testing guide and test cards: `docs/TEST-CARDS.md`
- Common questions: `docs/DEV-FAQ.md`
- Before you flip to live: `docs/GO-LIVE-CHECKLIST.md`

## Two things that will save you an hour

**The API is XML under the hood.** It accepts JSON, but element order is enforced. If you get a cryptic parse error, you reordered a field. Use an official SDK and this stops being your problem.

**Sandbox and production have separate everything.** Different URLs, different credentials, different logins. Sandbox keys will never work against production and the error message will not make that obvious.
