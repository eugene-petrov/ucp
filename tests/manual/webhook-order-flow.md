# Manual Testing: UCP Webhook Order Flow

This guide walks through end-to-end manual testing of the UCP-native webhook dispatch feature.
Webhooks are dispatched for five order lifecycle events: `order.created`, `order.updated`,
`order.shipped`, `order.canceled`, and `order.refunded`.

---

## Prerequisites

- Cron is running, or you will trigger deliveries manually via CLI
- A publicly reachable HTTPS webhook receiver (see step 1)
- A publicly reachable HTTPS platform profile JSON (see step 2)

---

## Step 1 — Set Up a Webhook Receiver

Use [webhook.site](https://webhook.site) to capture incoming webhook requests.

1. Open https://webhook.site in your browser
2. Copy your unique URL — it looks like `https://webhook.site/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
3. Keep the tab open to monitor incoming requests

---

## Step 2 — Host a Platform Profile JSON

The Dispatcher fetches a capability profile from the URI stored in `platform_profile_uri`.
Host a JSON file at any public HTTPS URL (GitHub Gist, S3, or your own server).

**Profile JSON structure:**

```json
{
  "ucp": {
    "capabilities": [
      {
        "name": "dev.ucp.shopping.order",
        "version": "2026-01-11",
        "config": {
          "webhook_url": "https://webhook.site/YOUR-UNIQUE-ID"
        }
      }
    ]
  }
}
```

Replace `YOUR-UNIQUE-ID` with your webhook.site ID from step 1.

> **Important:** The profile URL must be `https://`. HTTP is rejected.
> The `webhook_url` must also be `https://`.

Save the public URL of your profile — e.g. `https://gist.githubusercontent.com/.../profile.json`.

---

## Step 3 — Create a Checkout Session (binds the platform profile to the order)

Use the UCP API to create a checkout session. Pass the platform profile URL in the
`UCP-Agent` header. This is how the module learns which `webhook_url` to deliver to.

```bash
curl -X POST https://magento.test/rest/V1/ucp/checkout \
  -H "Content-Type: application/json" \
  -H "UCP-Agent: my-platform/1.0 profile=\"https://YOUR-PROFILE-URL\"" \
  -d '{"cartId": "YOUR-MASKED-CART-ID"}'
```

**How to get a masked cart ID:**
```bash
curl -X POST https://magento.test/rest/V1/guest-carts \
  -H "Content-Type: application/json"
```

**Expected response:** a checkout session object with a `session_id`.

**Verify the profile URI was stored:**
```sql
SELECT session_id, platform_profile_uri
FROM aeqet_ucp_checkout_session
ORDER BY entity_id DESC
LIMIT 1;
```

---

## Step 4 — Place the Order (`order.created`)

Complete the checkout session to place an order:

```bash
curl -X POST https://magento.test/rest/V1/ucp/checkout/SESSION_ID/complete \
  -H "Content-Type: application/json"
```

**What happens internally:**
1. Observer `SalesOrderPlaceAfterObserver` catches `sales_order_place_after`
2. Dispatcher fetches the platform profile, finds `dev.ucp.shopping.order` capability
3. A row is inserted into `aeqet_ucp_webhook_delivery` with `status = pending`

**Verify the delivery was queued:**
```sql
SELECT delivery_id, event_type, status, attempts, target_url, created_at
FROM aeqet_ucp_webhook_delivery
ORDER BY entity_id DESC
LIMIT 5;
```

---

## Step 5 — Trigger Webhook Delivery (cron or CLI)

Deliveries are processed by a cron job every minute. To trigger immediately:

```bash
bin/magento ucp:webhooks:retry
```

**Verify on webhook.site:** you should see an incoming `POST` request with a JSON body like:

```json
{
  "event_id": "evt_a1b2c3...",
  "event_type": "order.created",
  "timestamp": "2026-03-10T12:00:00Z",
  "order": {
    "id": "000000001",
    "status": "pending",
    "grand_total": 99.99,
    "currency": "USD"
  }
}
```

**Verify headers on webhook.site:**
- `Content-Type: application/json`
- `X-Webhook-Signature: ...`
- `X-Delivery-Id: whdlv_...`

**Verify delivery status in DB:**
```sql
SELECT delivery_id, event_type, status, attempts
FROM aeqet_ucp_webhook_delivery
ORDER BY entity_id DESC
LIMIT 5;
```

`status` should be `delivered`.

---

## Step 6 — Change Order Status (`order.updated`)

The `order.updated` webhook fires only when the order **status field changes**.

In Magento Admin: **Sales → Orders → [your order] → Change status** (e.g. to Processing).

Or via CLI:
```bash
bin/magento sales:order:status:update ORDER_INCREMENT_ID processing
```

Then trigger delivery:
```bash
bin/magento ucp:webhooks:retry
```

Check webhook.site for `event_type: "order.updated"`.

> **Note:** Simply saving the order without a status change does NOT fire the webhook.
> The observer checks `origData('status') !== current status`.

---

## Step 7 — Create a Shipment (`order.shipped`)

In Magento Admin: **Sales → Orders → [your order] → Ship**.

Or via REST API:
```bash
curl -X POST https://magento.test/rest/V1/order/ORDER_ID/ship \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{}'
```

Trigger delivery and check webhook.site for `event_type: "order.shipped"`.

---

## Step 8 — Cancel the Order (`order.canceled`)

In Magento Admin: **Sales → Orders → [your order] → Cancel**.

Or via REST API:
```bash
curl -X POST https://magento.test/rest/V1/orders/ORDER_ID/cancel \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

> Note: a shipped order cannot be canceled. Use a fresh `pending` order for this test.

Trigger delivery and check webhook.site for `event_type: "order.canceled"`.

---

## Step 9 — Issue a Refund (`order.refunded`)

In Magento Admin: **Sales → Orders → [your invoiced order] → Credit Memo → Refund**.

Trigger delivery and check webhook.site for `event_type: "order.refunded"`.

---

## Step 10 — Test Retry Logic

1. Temporarily point `webhook_url` in your profile JSON to an endpoint that returns `500`
   (e.g. use webhook.site's "Custom response" feature, or https://httpstat.us/500)
2. Place a new order and trigger delivery:
   ```bash
   bin/magento ucp:webhooks:retry
   ```
3. Check the delivery record — `status` should remain `pending`, `attempts` should be `1`,
   and `next_retry_at` should be ~60 seconds in the future:
   ```sql
   SELECT delivery_id, status, attempts, next_retry_at, last_error
   FROM aeqet_ucp_webhook_delivery
   ORDER BY entity_id DESC
   LIMIT 3;
   ```
4. Run retry again after the delay:
   ```bash
   bin/magento ucp:webhooks:retry
   ```
5. After 6 failed attempts, `status` becomes `failed`.

**Retry schedule:** 1m → 5m → 30m → 2h → 8h → 24h

---

## Step 11 — Test 4xx Permanent Failure

Point `webhook_url` to an endpoint that returns `404` (e.g. https://httpstat.us/404).

After a single delivery attempt, the record should immediately show `status = failed`
with no `next_retry_at` — no retries are scheduled for client errors.

```sql
SELECT status, attempts, next_retry_at, last_error
FROM aeqet_ucp_webhook_delivery
ORDER BY entity_id DESC
LIMIT 1;
```

Expected: `status = failed`, `attempts = 1`, `next_retry_at = NULL`,
`last_error = "HTTP 404 (client error, no retry)"`.

> **Exception:** HTTP `429 Too Many Requests` is treated as a retriable error.

---

## Checking Logs

All webhook activity is logged under the UCP logger:

```bash
tail -f /var/www/html/var/log/ucp.log
```

Key log messages:

| Level | Message |
|-------|---------|
| `debug` | `UCP dispatch: no session for order` — order has no UCP session |
| `debug` | `UCP dispatch: no platform_profile_uri for session` — session has no profile URL |
| `debug` | `UCP dispatch: failed to fetch platform profile` — profile HTTP request failed |
| `debug` | `UCP dispatch: no order webhook_url in platform profile` — capability not found |
| `debug` | `UCP webhook delivery queued` — delivery row created |
| `debug` | `UCP webhook delivery succeeded` — HTTP 2xx received |
| `info`  | `UCP webhook delivery scheduled for retry` — retryable failure |
| `warning` | `UCP webhook delivery permanently failed` — max attempts or 4xx |

---

## Quick Reference: DB Tables

| Table | Purpose |
|-------|---------|
| `aeqet_ucp_checkout_session` | Stores session → quote mapping and `platform_profile_uri` |
| `aeqet_ucp_webhook_delivery` | Queue of outbound webhook deliveries with retry state |

**Useful queries:**

```sql
-- Check all pending/failed deliveries
SELECT delivery_id, event_type, status, attempts, last_error, next_retry_at
FROM aeqet_ucp_webhook_delivery
WHERE status IN ('pending', 'failed')
ORDER BY entity_id DESC;

-- Reset a failed delivery for manual retry
UPDATE aeqet_ucp_webhook_delivery
SET status = 'pending', next_retry_at = NULL
WHERE delivery_id = 'whdlv_YOUR_ID';
```
