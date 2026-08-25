#!/usr/bin/env bash
# Confirms your API Login ID and Transaction Key are valid.
# Run this before touching any application code.

set -euo pipefail

# Read .env by hand rather than sourcing it. A space around the = sign is the
# most common typo here, and sourcing turns it into "command not found: <half
# your key>", which tells you nothing about what is actually wrong.
if [ -f .env ]; then
  while IFS= read -r line || [ -n "$line" ]; do
    case "$line" in ''|'#'*) continue ;; esac
    case "$line" in *=*) ;; *) continue ;; esac
    key=$(printf '%s' "${line%%=*}" | tr -d '[:space:]')
    val=$(printf '%s' "${line#*=}" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//; s/^"(.*)"$/\1/; s/^'\''(.*)'\''$/\1/')
    case "$key" in ANET_*) export "$key=$val" ;; esac
  done < .env
fi

if [ -z "${ANET_API_LOGIN_ID:-}" ] || [ -z "${ANET_TRANSACTION_KEY:-}" ]; then
  cat >&2 <<'MSG'
ANET_API_LOGIN_ID or ANET_TRANSACTION_KEY is empty.

Open .env and fill both in. Paste the values with nothing around them, no
spaces either side of the = sign and no quotes:

  ANET_API_LOGIN_ID=yourLoginIdHere
  ANET_TRANSACTION_KEY=yourTransactionKeyHere

Both come from the merchant interface under Account, then API Credentials
& Keys. The Transaction Key is shown once, so copy it when you generate it.
MSG
  exit 1
fi

if [ "${ANET_ENV:-sandbox}" = "production" ]; then
  API="https://api.authorize.net/xml/v1/request.api"
else
  API="https://apitest.authorize.net/xml/v1/request.api"
fi

echo "Endpoint: $API"

curl -s -X POST "$API" \
  -H "Content-Type: application/json" \
  -d "{
    \"authenticateTestRequest\": {
      \"merchantAuthentication\": {
        \"name\": \"${ANET_API_LOGIN_ID}\",
        \"transactionKey\": \"${ANET_TRANSACTION_KEY}\"
      }
    }
  }" | tr -d '\357\273\277'

echo
echo "Looking for resultCode Ok. Anything else means the credentials or the environment is wrong."
