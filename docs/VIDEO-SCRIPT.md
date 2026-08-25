# Video script: "Migrating a live WooCommerce store to Authorize.Net, uncut"

**Audience:** freelance web developers and small agencies who maintain WooCommerce stores currently on Payflow, Braintree, or another legacy gateway.

**The job of this video is not to teach the swap.** The swap is fifteen minutes and they can see that. The job is to prove we know where the bodies are buried, because the dev's real fear is not "can I install a plugin," it is "will my client's store break and will I get blamed." Every minute of this video answers that fear.

**Format:** one unbroken screen recording of a real migration on a staging store. Visible timer from second one. No cuts, no music. If something goes sideways on camera, fix it on camera. That recovery is worth more than any polish.

---

## 0:00 — Cold open, 20 seconds

> "This is a WooCommerce store on a legacy gateway. I'm going to migrate it to Authorize.Net, live, uncut, timer in the corner. But the install isn't the point. The point is the three things that break in every gateway migration, because that's what nobody tells you before you quote the job."

---

## 0:30 — The three breaks, on screen as text

> "One. Saved cards die. Tokens from the old gateway mean nothing to the new one.
> Two. Subscriptions fail silently at their next renewal. Your client finds out through churn, weeks later, and blames you.
> Three. Refunds on old orders still need the old gateway, so the old plugin stays installed after cutover.
>
> First job on any migration: find out how exposed this store is."

## 1:00 — The 15-minute audit, live

Screen: phpMyAdmin or terminal. Run the three queries from MIGRATION-CHECKLIST.md: gateways in recent orders, saved card counts, active subscriptions by gateway.

> "Saved cards on the old gateway: zero. Active subscriptions: zero. That makes this store a green light, a straight swap. If that subscription number wasn't zero, I'd stop here, tell the client, and this becomes a two-to-four week project with a card re-collection campaign before anything else. Quoting that correctly is the difference between a happy client and eating three weeks."

This section is the whole video. Take the time it takes.

## 4:00 — Credentials and proof

Screen: sandbox merchant interface → API Credentials & Keys, then terminal: `./scripts/verify-credentials.sh`

> "Two values, and I verify them before touching the site. `resultCode: Ok`. If this fails, nothing downstream works and now I know which layer to look at."

## 5:30 — The swap itself

Screen: WooCommerce → Settings → Payments. Install the Authorize.Net plugin, paste the two keys, test mode ON. Old gateway left exactly as it is.

> "Note what I'm not doing: I'm not touching the old gateway. Both live side by side. The old one keeps handling refunds on historical orders until the refund window closes. I'll put the removal date in the handover doc."

## 7:30 — Prove it end to end

Screen: storefront, place an order with 4111 1111 1111 1111. Then split screen: order marked processing in WooCommerce, transaction visible in the merchant interface.

> "Both sides. Store says paid, gateway shows the transaction. If only one shows it, something's misconfigured, and you want to find that on staging, not from the client's phone call."

## 9:30 — What go-live actually looks like

Screen: scroll GO-LIVE-CHECKLIST.md, do not read it aloud.

> "Cutover to production is this checklist. Highlights: production keys, test mode off, one real dollar transaction, refund it, confirm the money path. And check the AVS settings, because the most common day-one problem isn't code, it's the fraud filter set too strict, declining real customers."

## 11:00 — Close

> "Full checklist, the audit queries, working code samples, and a troubleshooting doc for every error I've mentioned: all in the repo below. And if you've got a client on one of these old gateways and want us on the call for the setup, that's literally what we do. Timer says [X]. Most of it was the audit, and that's the part that keeps you out of trouble."

Stop the timer on screen. No outro.

---

## Notes

- Total target: 12 to 14 minutes. If cutting, cut the swap section, never the audit section.
- Prepare the staging store in advance with a legacy gateway installed and a realistic order history, so the audit queries return real-looking numbers.
- Blur credentials on screen and say you are doing it. The audience judges credential hygiene, and that judgment transfers to whether they trust you near their client's account.
- Same recording yields a 90-second merchant-facing cut: the three breaks, the successful transaction, done.
