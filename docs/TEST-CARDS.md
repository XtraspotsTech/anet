# Test cards

Sandbox only. These are rejected in production.

| Brand | Number |
|---|---|
| Visa | 4111 1111 1111 1111 |
| Visa (alt) | 4007 0000 0002 7 |
| Mastercard | 5424 0000 0000 0015 |
| Mastercard (2-series) | 2223 0000 1018 1375 |
| American Express | 3700 0000 0000 002 |
| Discover | 6011 0000 0000 0012 |
| JCB | 3088 0000 0000 0017 |
| Diners Club | 3800 0000 0000 06 |

**Expiry:** any date in the future.
**CVV:** any 3 digits, or 4 digits for Amex.

For eCheck testing use routing number `121042882` with any account number.

## Testing declines and errors

The sandbox triggers specific decline and error responses based on the transaction amount and on certain card numbers. The combinations change, so read the current testing guide rather than trusting a list copied from a blog post:

https://developer.authorize.net/hello_world/testing_guide.html

At minimum, before you call an integration done, confirm you handle:

- An approval
- A decline
- A held-for-review response (`responseCode: 4`)
- A network timeout with no response at all

The last one is the one everybody skips, and it is the one that double-charges customers.

## Housekeeping

Void your test transactions in the merchant interface when you are done. It does not matter in sandbox. Build the habit anyway.
