# Platform setup, no code

Most clients are on a platform. That means this is a settings screen, not an integration. Budget 20 minutes.

## WooCommerce

1. Install an Authorize.Net gateway extension. The WooCommerce.com official extension is paid and well maintained; there are free alternatives of varying quality, so check the last-updated date and the WooCommerce version compatibility before installing anything on a client site.
2. **WooCommerce → Settings → Payments → Authorize.Net**
3. Enable it, then set:
   - **API Login ID** and **Transaction Key** from the merchant interface
   - **Transaction type:** Capture (use Authorize Only if the client ships physical goods later)
   - **Environment / Test mode:** ON while you are testing
4. Save, then place a real order on the storefront with a test card.
5. Confirm the order shows Processing in WooCommerce **and** the transaction appears in the merchant interface. Both. If only one shows it, something is misconfigured.

## Magento 2

**Stores → Configuration → Sales → Payment Methods → Authorize.Net**. Same fields. Magento ships an official module, no third-party plugin needed.

## BigCommerce

**Settings → Payments → Authorize.Net**. Same fields.

## Shopify

Tell the client the truth up front: Shopify charges an additional transaction fee on top of your processing when you use a third-party gateway instead of Shopify Payments. Depending on their plan and volume that can wipe out the savings entirely.

Run the numbers before recommending the switch. Saying "this may not be right for you" on the first call buys more credibility than it costs in deals.

## Squarespace / Wix

These do not support arbitrary third-party gateways on their native stores. If the client is on one of these and wants their own merchant account, the honest answer is that the store has to move platform. That is a separate, much bigger conversation.

## After the test transaction

Void the test transaction in the merchant interface so it does not sit in the batch. In sandbox it does not matter. Do it anyway, so the habit is there when you are in production.
