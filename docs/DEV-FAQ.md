# Developer FAQ

Short answers to what actually gets asked. Nothing here is a sales pitch.

---

### How long does this really take?

Platform site with an existing extension: 20 minutes. Custom site with Accept Hosted: an afternoon including testing. Custom checkout UI with Accept.js: a day or two, most of it your own form work rather than gateway work.

### Do I need the merchant account before I can build?

No. Sign up for a free sandbox at https://developer.authorize.net/hello_world/sandbox.html and build against it today. No underwriting, no bank details, no approval. The merchant account only matters when real money needs somewhere to land.

### Accept Hosted or Accept.js?

Accept Hosted unless the client specifically needs the form on their own domain. Hosted is less code, less liability, and keeps the client in the simplest PCI tier. Accept.js when checkout UI is part of the product.

Do not use AIM or SIM. They are deprecated, and most tutorials you will find online still teach them. Following those puts card data through the client's server for no reason.

### What about PCI?

With Accept Hosted, card data never touches the client's server, and the client typically qualifies for SAQ A, the shortest self-assessment. Accept.js also keeps card data out of your backend since tokenisation happens in the browser. Posting raw card numbers to your own server is what triggers the heavy compliance work, and there is no reason to do it.

### The API returns JSON but rejects my request with a parse error

The API is XML underneath with a JSON translation layer, so element order is enforced even though JSON does not normally care. Match the order in the API reference exactly, or use an official SDK and stop thinking about it.

Second most common cause: the response has a UTF-8 BOM in front of it that breaks strict JSON parsers. Both samples in this repo strip it.

### Sandbox credentials do not work in production

They are entirely separate systems with separate URLs, separate logins, and separate keys. Production credentials come from the live merchant interface after the account is approved.

- Sandbox API: `https://apitest.authorize.net/xml/v1/request.api`
- Production API: `https://api.authorize.net/xml/v1/request.api`
- Sandbox Accept.js: `https://jstest.authorize.net/v1/Accept.js`
- Production Accept.js: `https://js.authorize.net/v1/Accept.js`

There is also a Test Mode toggle inside a live account. That is a different thing again: a live account running in test mode returns fake approvals and stores nothing.

### How do I know an order was actually paid?

Do not rely on the customer arriving at your return URL. They can navigate there directly, and they can also close the tab after a successful payment.

Two reliable options:
- Subscribe to the `net.authorize.payment.authcapture.created` webhook
- Call `getTransactionDetailsRequest` with the transaction ID before marking the order paid

For anything with real volume, do both. Webhooks can be delayed; polling catches the stragglers.

### What does responseCode 4 mean?

Held for review by the fraud filter. It is not an approval. If you fulfil on a 4 you are shipping goods for a payment that may never settle. Treat it as pending and surface it to the merchant.

- 1 approved
- 2 declined
- 3 error
- 4 held for review

### Can I store cards for repeat customers?

Yes, with Customer Profiles (CIM). You store a profile ID, Authorize.Net stores the card. Required for any subscription or one-click reorder flow, and it keeps the card data out of the client's database.

### Refunds and voids?

A void cancels a transaction that has not settled yet, usually same day. A refund reverses one that has settled. Both work from the merchant interface without any code, which matters because the merchant's staff will be doing this, not you.

### Does it support Apple Pay and Google Pay?

Yes, both, through the same gateway account. Worth mentioning to clients who assume they need a separate provider for wallets.

### The client is on Shopify

Shopify adds an extra transaction fee for third-party gateways on top of processing. Run the numbers before recommending it. Sometimes the honest answer is that Shopify Payments is cheaper for them.

### The client is on Squarespace or Wix

Their native stores do not accept arbitrary third-party gateways. The store would have to move platform. Say that early rather than discovering it in week three.

### Where is the real documentation?

- Developer center: https://developer.authorize.net
- Guides by feature: https://developer.authorize.net/api.html
- API reference with a live console you can fire requests from: https://developer.authorize.net/api/reference/index.html
- Official SDKs for .NET, Java, PHP, Ruby, Python: https://github.com/AuthorizeNet

### Something is broken and it is not my code

Contact your rep directly, not the general support line. Include the transaction ID, the timestamp, the API Login ID (never the Transaction Key), and the full raw response.
