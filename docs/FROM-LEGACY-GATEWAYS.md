# Coming from a legacy gateway

Gateway-specific notes for the three we see most. The three universal breaks (saved cards die, subscriptions fail, old refunds need the old gateway) apply to all of them; below is only what is different per gateway.

Verify current vendor status yourself before quoting a client. These products change, and their owners have been actively pushing users off them, which is exactly why the merchant is migrating.

---

## PayPal Payflow (Payflow Pro / Payflow Link)

**What it is.** A gateway, not a full processor. Some Payflow merchants already have their own merchant account behind it, which means they understand the gateway-vs-account distinction better than most.

**Watch for:**
- Reference transactions and recurring billing profiles configured inside Payflow do not export. Treat every recurring relationship as a re-collection case.
- Older WooCommerce Payflow plugins injected their own checkout fields. After removing the plugin, check the checkout page for orphaned CSS and JS.
- PayPal has been consolidating its legacy products for years. Check the current lifecycle status and put it in your client conversation, because "this product is being sunset by its own vendor" is the cleanest migration justification there is.

**Sales note for the dev:** these merchants are on decade-old pricing and nobody has reviewed their statement in years. This is the best segment on the list.

## Braintree

**What it is.** A full stack owned by PayPal: gateway, vault, and processing together.

**Watch for:**
- The Braintree vault holds the saved cards. Braintree does support card data portability to another provider on merchant request, as PCI-compliant processors generally do, but it is a formal process between processors, it takes time, and Authorize.Net-side import is its own project. For a small store, the honest comparison is: portability request and coordination across two vendors, versus a two-week re-collection campaign. Small stores almost always come out ahead just re-collecting. Price both before promising either.
- Braintree often runs PayPal and Venmo buttons through the same integration. Removing Braintree removes those buttons. The merchant must know this before cutover, not after, and you likely need a separate PayPal integration to preserve them.
- 3D Secure configuration does not carry over. If the store used it, set it up again on the new gateway.

## 2Checkout (Verifone)

**What it is.** Different animal. In its common configuration 2Checkout is the merchant of record: the sale legally runs through them, and they pay the merchant out.

**What that means for migration:**
- This is not a gateway swap, it is a change of who legally sells the product. Payout timing, statement descriptors on customer cards, and tax handling all change. In particular, as merchant of record 2Checkout was handling sales tax / VAT collection; after migration the merchant owns that problem. Flag it explicitly and put it in writing, because it surfaces months later at filing time.
- Subscriptions managed inside 2Checkout's own billing system have no export path into WooCommerce Subscriptions that preserves tokens. Re-collection, plus possibly rebuilding the subscription products in WooCommerce itself.
- Expect customer confusion: the name on their card statement changes. A short notice email prevents a chargeback wave from customers who no longer recognise the charge.

**Quote 2Checkout migrations as projects, never as swaps.** They carry the most hidden work of the three.

---

## The line that applies to all three

The merchant is not just changing a plugin. They are changing where their money lands, who they call when it breaks, and in 2Checkout's case who the legal seller is. Say that in the first conversation. The developer who explains this before being asked is the one who gets the next five clients from that merchant's referrals.
