Testing AI-Agent Checkout with UCP Today: What Works and What's Coming

The Universal Commerce Protocol (UCP) promises a future where AI agents can browse your store, build a cart, and complete a purchase entirely autonomously — no human clicking required. That future isn't fully here yet, but the
interesting part is: most of it already works today.

Where We Are

The UCP specification (version 2026-01-23) defines a layered architecture for agentic commerce. At the top sits the AP2 payment mandate — a cryptographically signed JWT that allows an AI agent to authorize a payment on behalf
of a user without exposing card credentials. Google, Apple Pay, and other platforms are building toward this. The spec is a month old. Production implementations don't exist yet.

But AP2 is only the last step in a much longer flow.

What Already Works End-to-End

The Aeqet_Ucp module for Magento 2 implements everything up to the payment step:

Agent discovers manifest   →  GET /.well-known/ucp
Agent browses catalog      →  GET /V1/ucp/products
Agent creates cart         →  POST /V1/ucp/cart
Agent adds items           →  POST /V1/ucp/cart/:id/items
Agent creates session      →  POST /V1/ucp/checkout
Agent fills buyer + ship   →  PUT /V1/ucp/checkout/:id
Agent completes order      →  POST /V1/ucp/checkout/:id/complete
Merchant receives webhook  →  order.created (signed JWT, RFC 7797)

Every one of these steps is live. An AI agent with HTTP tool access — Claude, GPT-4o, Gemini — can walk this flow against a real Magento store right now.

Try It Today: Check / Money Order

For the complete step, the module uses Magento's Check / Money Order (checkmo) payment method by default. This is an offline payment method: no card processing, no gateway, no tokenization. The order is placed, the merchant
receives it, and payment is handled out-of-band.

This is actually a reasonable stand-in for the delegated payment model UCP describes — the agent handles the product and fulfillment decisions, the merchant handles the money. The difference is that today the merchant emails a
bank account number instead of receiving a cryptographic mandate.

To test with a real agent, you need three things:

1. A UCP manifest at /.well-known/ucp — generated via bin/magento ucp:manifest:generate
2. A signing key for webhook delivery — generated via bin/magento ucp:keys:generate
3. An AI agent with HTTP tools — any LLM that can call REST endpoints, or an MCP server wrapping the UCP API

The agent reads the manifest, discovers the API endpoint, and proceeds from there. No special UCP client library required — it's plain JSON over HTTPS.

The Gap: Fully Autonomous Payment

The one thing that doesn't work yet is a fully autonomous purchase where the agent provides a payment credential. That requires:

- An AI platform (Google, Apple, etc.) that can issue an AP2 mandate — a signed JWT containing merchant ID, amount, currency, and expiry
- The merchant verifying that signature against the platform's published public keys
- Order placement without any human touching a payment page

The infrastructure for this — on both the platform and merchant sides — is being built in 2026. When AP2 mandates become available, the Aeqet_Ucp module will add:

- A payment parameter to the complete endpoint accepting the mandate JWT
- A MandateVerifier service that fetches the platform's signing keys and verifies the token via firebase/php-jwt
- A ucp_agent Magento payment method that stores the verified mandate as order payment data

The webhook infrastructure, signing keys, capability negotiation, and checkout session flow will remain unchanged — they're already built to the spec.

Bottom Line

If you want to see an AI agent place a real order on a Magento store using UCP today, you can. The checkout flow is complete. Point an agent at your manifest, give it a product to buy and an address to ship to, and watch the
order appear in your admin panel.

The autonomous payment piece is coming. The rest is already here.
