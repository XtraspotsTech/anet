# Migration checklist: run this BEFORE touching anything

Fifteen minutes with database access. This tells you whether the job is one hour or three weeks, and it is the difference between quoting a client correctly and eating the overrun.

All queries are read-only. Adjust the `wp_` prefix to match the site.

---

## The three things that break in every gateway migration

1. **Saved cards die.** Tokens stored by Payflow, Braintree, or 2Checkout are meaningless to Authorize.Net. Every customer with a card on file loses it at cutover.
2. **Subscriptions break silently.** Active subscriptions are bound to old-gateway tokens. The next renewal fails. The merchant notices weeks later as churn, and blames you.
3. **Old refunds need the old gateway.** You cannot refund a Payflow transaction through Authorize.Net. The old plugin stays installed, disabled for new payments, until the refund window closes.

Everything below exists to size these three risks for this specific store.

---

## Check 1 — What gateways are actually in play

WooCommerce admin → Settings → Payments. Note everything enabled. Then check what historical orders actually used, because merchants forget:

```sql
SELECT pm.meta_value AS gateway, COUNT(*) AS orders
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_payment_method'
WHERE p.post_type = 'shop_order'
  AND p.post_date > DATE_SUB(NOW(), INTERVAL 180 DAY)
GROUP BY pm.meta_value ORDER BY orders DESC;
```

Then run the HPOS version as well. Run both on every store, whatever the settings screen says, because compatibility mode keeps the old and new storage populated and synced at the same time. Rows coming back from both is normal. **Take the larger number, never the sum.**

```sql
SELECT payment_method, COUNT(*) AS orders
FROM wp_wc_orders
WHERE type = 'shop_order'
  AND date_created_gmt > DATE_SUB(NOW(), INTERVAL 180 DAY)
GROUP BY payment_method ORDER BY orders DESC;
```

## Check 2 — Saved cards

```sql
SELECT gateway_id, COUNT(*) AS saved_cards
FROM wp_woocommerce_payment_tokens
GROUP BY gateway_id;
```

Zero rows on the old gateway: nothing to worry about. Hundreds: plan customer comms, because those customers will be asked to re-enter cards on their next purchase.

## Check 3 — Subscriptions (the one that decides everything)

This check decides GREEN versus RED, so it is worth being paranoid about.

**The expensive mistake is a false GREEN.** The store has recurring billing, your query did not see it, you quoted two hours, and a month after handover every subscriber's card silently stops working. You will hear about it from the merchant, not from your monitoring.

So do not start by asking which subscription plugin is installed, and never query a plugin's table by name. Ask the database what is actually there.

### 3a — Find every recurring thing, whatever created it

Legacy post storage:

```sql
SELECT post_type, post_status, COUNT(*) AS n
FROM wp_posts
WHERE post_type LIKE '%subscri%'
   OR post_type LIKE '%recurring%'
   OR post_type LIKE '%membership%'
GROUP BY post_type, post_status
ORDER BY n DESC;
```

HPOS:

```sql
SELECT type, status, COUNT(*) AS n
FROM wp_wc_orders
GROUP BY type, status
ORDER BY n DESC;
```

Run both, on every store. Anything that comes back and is not `shop_order` deserves ten minutes of your attention.

**Why not just query `shop_subscription`?** Because that post type belongs to the official WooCommerce Subscriptions extension, and that extension is paid. Stores unwilling to pay for it use a free alternative, and those plugins register their own post types: YITH WooCommerce Subscription uses `ywsbs_subscription`, others use their own. A query hardcoded to `shop_subscription` returns zero on all of them, which looks exactly like a clean store and is not one. A small store running a free subscription plugin on a decade-old gateway is a normal migration candidate, not an exotic one, so this is the common case rather than the edge case.

### 3b — Confirm against the plugin list

Thirty seconds, and it catches whatever 3a missed. **Plugins → Installed Plugins**, filter to Active, and read for anything mentioning subscriptions, recurring, memberships, payment plans, or instalments. Then look at the storefront itself for wording like "per month" or "subscribe and save".

Empty in 3a and nothing in 3b is a real GREEN. Anything else, keep going.

### 3c — Find which gateway the recurring charges run through

For the official extension, legacy storage then HPOS:

```sql
SELECT pm.meta_value AS gateway, COUNT(*) AS active_subs
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_payment_method'
WHERE p.post_type = 'shop_subscription'
  AND p.post_status IN ('wc-active', 'wc-on-hold')
GROUP BY pm.meta_value;
```

```sql
SELECT payment_method, COUNT(*) AS active_subs
FROM wp_wc_orders
WHERE type = 'shop_subscription'
  AND status IN ('wc-active', 'wc-on-hold')
GROUP BY payment_method;
```

For any other plugin, swap in the post type you actually found in 3a. The gateway will be stored under a different meta key, so read one record and find out which:

```sql
SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = <a subscription ID from 3a>;
```

### What the answer means

Any live recurring billing on the old gateway means those charges **will** fail at the next renewal after cutover. There is no token migration between these gateways. The fix is a card re-collection campaign: email each subscriber a link to re-enter their card into Authorize.Net (Customer Profiles / CIM) before you cut over. Budget two to four weeks, most of it waiting on customers, and set the merchant's expectation on day one.

Quote accordingly. This is no longer a plugin swap, it is a project.

If 3a to 3c do not give you a clean answer, call the store RED until something proves otherwise. Being wrong that way costs one awkward conversation. Being wrong the other way costs the merchant real revenue and costs you the client.

## Check 4 — Refund exposure

From Check 1, note the most recent 180 days of order volume on the old gateway. That is roughly how long the old plugin must stay installed and configured after cutover. Write the removal date into your handover doc so it actually happens.

## Check 5 — Checkout surface area

- Block-based checkout or shortcode checkout? (Pages → Checkout, look for the block editor)
- Any plugin with "checkout" in the name? Custom checkout fields, one-page checkout, funnels?
- Child theme overriding any WooCommerce template under `checkout/`?

Each yes adds testing time. None of them block the migration, but each is a place the new gateway's fields can render wrong.

## Check 6 — Boring but decisive

- PHP version, WooCommerce version against the Authorize.Net plugin's stated requirements
- Is there a staging site? If not, making one is step zero, not optional
- Who has the current gateway's account login? You need it for the parallel-run period and for refunds. Chasing credentials mid-migration is how a one-hour job becomes a week

---

## Verdict table

| Findings | Rating | Realistic quote |
|---|---|---|
| No saved cards, no subscriptions | GREEN | 1 to 2 hours plus testing |
| Saved cards, no subscriptions | YELLOW | Half a day plus a customer notice |
| Any live recurring billing on the old gateway, from any plugin | RED | 2 to 4 weeks, card re-collection campaign first |
| Check 3 did not give a clear answer | RED until proven otherwise | Do not quote yet |

Tell the client the rating and why before quoting. Being the developer who caught the subscription problem up front is worth more than the job itself.
