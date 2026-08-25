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

HPOS stores (WooCommerce → Settings → Advanced → Features shows if HPOS is on):

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

Is WooCommerce Subscriptions installed? If no, skip to Check 4, and your life is easy.

If yes:

```sql
-- legacy post storage
SELECT pm.meta_value AS gateway, COUNT(*) AS active_subs
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_payment_method'
WHERE p.post_type = 'shop_subscription'
  AND p.post_status IN ('wc-active', 'wc-on-hold')
GROUP BY pm.meta_value;
```

```sql
-- HPOS
SELECT payment_method, COUNT(*) AS active_subs
FROM wp_wc_orders
WHERE type = 'shop_subscription'
  AND status IN ('wc-active', 'wc-on-hold')
GROUP BY payment_method;
```

Any non-zero count on the old gateway means those subscriptions WILL fail at next renewal after cutover. There is no token migration between these gateways. The fix is a card re-collection campaign: email each subscriber a link to re-enter their card into Authorize.Net (Customer Profiles / CIM) before you cut over. Budget two to four weeks, mostly waiting on customers, and set the merchant's expectation on day one.

Quote accordingly. This is no longer a plugin swap, it is a project.

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
| Active subscriptions on old gateway | RED | 2 to 4 weeks, card re-collection campaign first |

Tell the client the rating and why before quoting. Being the developer who caught the subscription problem up front is worth more than the job itself.
