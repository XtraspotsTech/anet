# Accept.js

Use this only when the client needs a checkout form on their own domain with their own styling. Otherwise use `accept-hosted/`, it is less work and less liability.

The browser tokenises the card with the public client key. Your server charges the token. Card numbers never hit your infrastructure.

## Run it

```bash
set -a && . ../.env && set +a
npm install express
node server/server.js
# http://localhost:3000
```

Paste your sandbox `apiLoginID` and public client key into `public/index.html`, or inject them server-side.

## Things that bite people

- **The token is single use and expires in about 15 minutes.** If the charge fails you must re-tokenise, not retry the same token.
- **The card inputs have no `name` attributes.** Keep it that way. It is what stops card data being POSTed to your own server by accident.
- **`responseCode: 4` means held for review, not approved.** Do not ship the order. This one shows up as chargebacks months later.
- **The client key is public.** It is meant to be in front-end code. The Transaction Key is not, ever.
