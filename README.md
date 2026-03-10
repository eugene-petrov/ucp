# UCP (Universal Commerce Protocol) Module for Magento 2 (MVP)

![Composer](https://github.com/eugene-petrov/ucp/actions/workflows/composer.yml/badge.svg)
![PHPCS](https://github.com/eugene-petrov/ucp/actions/workflows/phpcs.yml/badge.svg)
![Magento 2 Standards](https://github.com/eugene-petrov/ucp/actions/workflows/magento-standards.yml/badge.svg)

## Overview

This module implements the UCP (Universal Commerce Protocol) specification version 2026-01-23 for Magento 2. It provides REST API endpoints to create and manage checkout sessions that can be used by external payment and checkout systems.

**Current Status:** MVP Implementation

## Features (Implemented)

- **UCP Profile Generation** - Generates complete UCP-compliant JSON responses
- **Checkout Session Management** - Create, read, update, delete checkout sessions
- **Quote Integration** - Converts Magento quotes to UCP format
- **Line Items** - Product information with images and pricing
- **Totals Calculation** - Subtotal, shipping, tax, and grand total
- **Buyer Information** - Customer email, name, and phone
- **Payment Handlers** - Delegated payment configuration
- **Fulfillment Options** - Available shipping methods with pricing
- **Fulfillment Address** - Accept shipping/billing address via PUT checkout session, map to Magento quote address, trigger shipping rate recalculation
- **Shipping Method Selection** - `selectedFulfillmentId` in PUT checkout session sets the shipping method; enables `ready_for_complete` status transition
- **Order Creation** - Complete checkout and create Magento order
- **Catalog API** - Product search, get by ID/SKU, category browsing
- **Cart API** - Create cart, add/update/remove items
- **Configurable Products** - Full support for variants and options
- **Manifest Generation** - Console command to generate UCP manifest at `/.well-known/ucp`
- **Admin Configuration** - Configure module settings via Magento admin panel
- **Session Persistence** - Checkout sessions persisted to database with foreign key to quote table
- **Signing Keys** - ECDSA P-256 key generation for webhook signature verification (UCP compliance)
- **Webhook Signer** - Detached JWT (RFC 7797) payload signing with ES256 via `Aeqet\Ucp\Model\Webhook\Signer`
- **OpenAPI Schema Endpoint** - Swagger 2.0 schema for all UCP endpoints at `GET /rest/V1/ucp/openapi.json`
- **Webhook Delivery Processor** - `Aeqet\Ucp\Model\Webhook\DeliveryProcessor` delivers queued webhooks via HTTP POST signed with `X-Webhook-Signature` (ES256 JWT); exponential backoff retry up to 6 attempts (1 min → 5 min → 30 min → 2 h → 8 h → 24 h)
- **Webhook Event Builder** - `Aeqet\Ucp\Model\Webhook\EventBuilder` constructs order event payloads (event ID, type, timestamp, order data, UCP session ID)
- **Order Lifecycle Observers** - Magento events wired to UCP webhook dispatcher:
  - `sales_order_place_after` → `order.created`
  - `sales_order_save_after` → `order.updated`
  - `sales_shipment_save_after` → `order.shipped`
  - `sales_order_cancel_after` → `order.canceled`
  - `sales_creditmemo_save_after` → `order.refunded`
- **Cron Job** - `aeqet_ucp_webhook_deliveries` runs every minute to process pending webhook deliveries
- **Capability Negotiation** - `Aeqet\Ucp\Model\Capability\Negotiator` intersects merchant capabilities with platform-supported capabilities (fail-open: returns all merchant capabilities if platform list is empty)
- **Platform Profile Fetcher** - `Aeqet\Ucp\Model\Capability\PlatformProfileFetcher` fetches remote UCP platform profiles (HTTPS-only, in-memory cache, fail-open on error)
- **UCP-Native Webhook Dispatch** - `Dispatcher::dispatch()` reads `platform_profile_uri` from the checkout session (stored from `UCP-Agent: profile="..."` header at session creation), fetches the platform's `/.well-known/ucp` profile, extracts `config.webhook_url` from the `dev.ucp.shopping.order` capability, and queues a `WebhookDelivery` record for async delivery by `DeliveryProcessor`

---

## Installation

### Via Composer (recommended)

1. Add the GitHub repository to your `composer.json`:

```bash
composer config repositories.aeqet-ucp vcs https://github.com/eugene-petrov/magento2-ucp
```

2. Require the module:

```bash
composer require aeqet/magento2-module-ucp:dev-main
```

3. Enable the module:

```bash
bin/magento module:enable Aeqet_Ucp
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual Installation

1. Clone the repository to `app/code/Aeqet/Ucp`:

```bash
mkdir -p app/code/Aeqet
git clone https://github.com/eugene-petrov/magento2-ucp app/code/Aeqet/Ucp
```

2. Enable the module:

```bash
bin/magento module:enable Aeqet_Ucp
bin/magento setup:upgrade
```

---

## Configuration

Navigate to **Stores > Configuration > Aeqet > UCP Settings** to configure:

### General Settings
- **Enable UCP Module** - Enable/disable the UCP API endpoints

### Manifest Settings
- **Base URL Override** - Override the store base URL in the UCP manifest (leave empty for default)
- **API Endpoint Path** - REST API base path for UCP endpoints (default: `rest/V1/ucp`)

### Capabilities
- **Checkout Capability** - Enable checkout session and cart capabilities
- **Catalog Capability** - Enable product and category catalog capabilities

### Payment Settings
- **Payment Handler Type** - Select the payment handling method (delegated, native, etc.)
- **Payment Handler Name** - Display name for the payment handler in the manifest

---

## Console Commands

### Generate UCP Manifest

Generate the UCP manifest file at `pub/.well-known/ucp`:

```bash
bin/magento ucp:manifest:generate
```

**Options:**
- `--output` - Output file path (default: `pub/.well-known/ucp`)
- `--pretty` - Pretty print JSON output

### Generate Signing Keys

Generate ECDSA P-256 signing keys for webhook authentication:

```bash
bin/magento ucp:keys:generate
```

**Options:**
- `--kid` or `-k` - Custom key ID (auto-generated if not provided)
- `--force` or `-f` - Skip confirmation prompt
- `--expires` or `-e` - Key expiration date (YYYY-MM-DD format)

**Example:**
```bash
# Generate a new key with auto-generated ID
bin/magento ucp:keys:generate --force

# Generate a key with custom ID and expiration
bin/magento ucp:keys:generate --kid=production_2026 --expires=2027-01-01

# Regenerate manifest to include new keys
bin/magento ucp:manifest:generate
```

The public key will be included in the `signing_keys` array of the UCP manifest at `/.well-known/ucp`. Multiple keys can be active simultaneously for key rotation support.

---

## TODO - Roadmap to Full UCP Compliance

This section documents what remains to fully implement the Universal Commerce Protocol for Magento 2.

### High Priority

- [ ] **Payment Status Integration**
  - Track payment status separately from order status
  - Support partial payments and refunds
  - Integrate with Magento payment gateways

### Medium Priority

- [ ] **Identity Linking (OAuth 2.0)**
  - Implement OAuth 2.0 authorization server
  - Support authorization code flow for customer linking
  - Token exchange for accessing customer data
  - Scopes: `profile`, `email`, `orders`, `addresses`

- [ ] **Payment Token Exchange Capability**
  - Accept payment tokens from external providers (Google Pay, Apple Pay)
  - Integrate with Magento Vault for token storage
  - Support tokenized card payments

- [ ] **Discounts Extension**
  - Expose available coupon/promo codes via API
  - Apply discounts to checkout session
  - Return discount breakdown in totals

- [ ] **Fulfillment Extension - Advanced Options**
  - Store pickup locations API
  - Delivery time slot selection
  - Shipping insurance options
  - Gift wrapping/messaging

- [ ] **Multi-Currency Support**
  - Return prices in requested currency
  - Currency conversion at checkout
  - Support for currency in manifest

### Low Priority

- [ ] **MCP Transport Binding**
  - Alternative transport using Model Context Protocol
  - Support for AI agent integration

- [ ] **A2A Transport Binding**
  - Agent-to-Agent protocol support
  - Async message handling

- [ ] **Multi-Website/Store Support**
  - Add `--store=code` option to generate manifest for specific store view
  - Add `--all` option to generate manifests for all stores
  - Use store-specific API endpoints: `/rest/{store_code}/V1/ucp`
  - Support per-store output paths (e.g., `pub/.well-known/ucp/{store_code}`)
  - Read config values with correct store scope
  - Website/store scope configuration in admin

- [ ] **Rate Limiting**
  - Implement API rate limiting
  - Return proper `429 Too Many Requests` responses
  - Configurable limits per endpoint

- [ ] **Caching Layer**
  - Cache catalog responses
  - Cache manifest for performance
  - Invalidation on product/category changes

### Testing & Documentation

- [ ] **Integration Tests**
  - PHPUnit tests for all API endpoints
  - Test checkout flow end-to-end
  - Test webhook delivery end-to-end

- [ ] **API Documentation**
  - Swagger UI integration
  - Request/response examples
  - Error code documentation

# Docs

- [Universal Commerce Protocol (UCP) Java Implementation](https://medium.com/@visrow/universal-commerce-protocol-ucp-java-implementation-building-ai-agent-enabled-checkout-and-1d6d5552084a)
- [Under the Hood: Universal Commerce Protocol (UCP)](https://developers.googleblog.com/under-the-hood-universal-commerce-protocol-ucp/)
- [Order Capability Specification](https://ucp.md/en/specification/order/)
- [How to Implement Universal Commerce Protocol (UCP) in 2026: Complete /.well-known/ucp Setup Guide](https://wearepresta.com/how-to-implement-universal-commerce-protocol-ucp-in-2026-complete-well-known-ucp-setup-guide/)
- [UCP Under The Hood: A Technical Deep Dive into Universal Commerce Protocol Architecture](https://ucphub.ai/ucp-technical-architecture-deep-dive-2026/)
- [Building the Universal Commerce Protocol](https://shopify.engineering/ucp)
