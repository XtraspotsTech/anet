# Accept Hosted

The default recommendation. Card data never reaches your server, so the client stays in the simplest PCI tier (SAQ A).

## Run it

```bash
cd accept-hosted/php
set -a && . ../../.env && set +a
php -S localhost:8000
open http://localhost:8000/checkout.php
```

You will be redirected to the Authorize.Net sandbox payment page. Pay with `4111 1111 1111 1111`, any future expiry, CVV `123`.

Then check **Transactions → Search** in the sandbox merchant interface. If your transaction is there, the integration works and everything after this is styling and business logic.

## Two things to fix before this is production code

1. **Never take the amount from the browser.** Compute the cart total server-side. The sample hardcodes it to keep the flow readable.
2. **Do not trust the return URL as proof of payment.** A customer can navigate there directly. Confirm the transaction either with a webhook (`net.authorize.payment.authcapture.created`) or by calling `getTransactionDetailsRequest` before you mark an order paid.

## Iframe instead of redirect

Add the `hostedPaymentIFrameCommunicatorUrl` setting and host a small communicator page. Keeps the customer on your domain visually. Same PCI benefit. See the Accept Hosted guide in the official docs.
