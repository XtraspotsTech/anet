#!/usr/bin/env bash
# Confirms your API Login ID and Transaction Key are valid.
# Run this before touching any application code.

set -euo pipefail
[ -f .env ] && set -a && . ./.env && set +a

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
