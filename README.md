# UCP (Universal Commerce Protocol) Module for Magento 2 (MVP)

![Composer](https://github.com/eugene-petrov/ucp/actions/workflows/composer.yml/badge.svg)
![PHPCS](https://github.com/eugene-petrov/ucp/actions/workflows/phpcs.yml/badge.svg)
![Magento 2 Standards](https://github.com/eugene-petrov/ucp/actions/workflows/magento-standards.yml/badge.svg)

## Overview

This module implements the Google UCP (Universal Commerce Protocol) specification version 2026-01-11 for Magento 2. It provides REST API endpoints to create and manage checkout sessions that can be used by external payment and checkout systems.

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
- **Order Creation** - Complete checkout and create Magento order
- **Catalog API** - Product search, get by ID/SKU, category browsing
- **Cart API** - Create cart, add/update/remove items
- **Configurable Products** - Full support for variants and options
- **Manifest Generation** - Console command to generate UCP manifest at `/.well-known/ucp`
- **Admin Configuration** - Configure module settings via Magento admin panel
- **Session Persistence** - Checkout sessions persisted to database with foreign key to quote table

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

---

## TODO - Roadmap to Full UCP Compliance

This section documents what remains to fully implement the Google Universal Commerce Protocol for Magento 2.

### Critical (Required for UCP Compliance)

- [ ] **Signing Keys for Webhook Verification**
  - Generate RSA or ECDSA key pairs for signing webhook payloads
  - Store public keys in manifest `signing_keys` array
  - Implement `Aeqet\Ucp\Model\Webhook\Signer` for payload signing
  - Support key rotation with multiple active keys

- [ ] **OpenAPI Schema Generation**
  - Auto-generate OpenAPI 3.0 schema for REST endpoints
  - Expose at `/rest/V1/ucp/openapi.json`
  - Include in manifest `rest.schema` field

### High Priority

- [ ] **Order Capability (Webhook Lifecycle Events)**
  - Implement webhook endpoints for order lifecycle:
    - `order.created` - Order placed
    - `order.updated` - Order status changed
    - `order.shipped` - Shipment created
    - `order.delivered` - Order delivered
    - `order.canceled` - Order canceled
    - `order.refunded` - Refund processed
  - Create `Aeqet\Ucp\Model\Webhook\Dispatcher` for sending webhooks
  - Implement retry logic with exponential backoff
  - Add webhook registration API

- [ ] **Fulfillment Address Handling**
  - Accept shipping address in checkout session update
  - Validate address and recalculate shipping rates
  - Support address validation services

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
  - Test webhook delivery

- [ ] **API Documentation**
  - Swagger UI integration
  - Request/response examples
  - Error code documentation
