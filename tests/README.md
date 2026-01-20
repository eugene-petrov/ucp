# Aeqet UCP Module

Universal Commerce Protocol (UCP) implementation for Magento 2.

## API Endpoints

### Catalog Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/V1/ucp/products` | Search products |
| GET | `/V1/ucp/products/:productId` | Get product by ID |
| GET | `/V1/ucp/products/sku/:sku` | Get product by SKU |

**Search Parameters:**
- `query` - Search string (searches name, SKU, description)
- `categoryId` - Filter by category ID
- `limit` - Results per page (default: 20)
- `offset` - Pagination offset (default: 0)

### Catalog Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/V1/ucp/categories` | Get category tree |
| GET | `/V1/ucp/categories/:categoryId` | Get category by ID |
| GET | `/V1/ucp/categories/:categoryId/products` | Get products in category |

**Tree Parameters:**
- `rootId` - Root category ID (default: store root)
- `depth` - Tree depth (default: 3)

### Cart

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/V1/ucp/cart` | Create new cart |
| GET | `/V1/ucp/cart/:cartId` | Get cart by ID |
| POST | `/V1/ucp/cart/:cartId/items` | Add item to cart |
| PUT | `/V1/ucp/cart/:cartId/items/:itemId` | Update item quantity |
| DELETE | `/V1/ucp/cart/:cartId/items/:itemId` | Remove item from cart |

**Add Item Body:**
```json
{
  "productId": "product_123",
  "quantity": 2,
  "options": [
    {"code": "color", "value": "Blue"},
    {"code": "size", "value": "M"}
  ]
}
```

### Checkout Session

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/V1/ucp/checkout` | Create session from cart |
| GET | `/V1/ucp/checkout/:sessionId` | Get session |
| PUT | `/V1/ucp/checkout/:sessionId` | Update session |
| POST | `/V1/ucp/checkout/:sessionId/complete` | Complete checkout |
| DELETE | `/V1/ucp/checkout/:sessionId` | Cancel session |

## Postman Collection

### Setup

1. Import `UCP_Checkout_Flow.postman_collection.json` into Postman
2. Configure variables:
   - `base_url`: Magento base URL (e.g., `https://app.249ce.test`)
   - `product_sku`: SKU of a simple product (default: `24-MB01`)
   - `category_id`: Category ID for testing (e.g., `27`)

### Test Folders

#### 1. Preparation
- 1.1 Create Guest Cart
- 1.2 Add Product to Cart
- 1.3 Set Shipping Address
- 1.4 Set Shipping Information

#### 2. UCP Checkout Flow
- 2.1 Create UCP Session
- 2.2 Get UCP Session
- 2.3 Update UCP Session
- 2.4 Complete UCP Session

#### 3. Cancel Flow
- 3.1-3.3 Create new cart and session
- 3.4 Cancel UCP Session
- 3.5 Verify canceled session cannot be updated

#### 4. Error Cases
- 4.1 Get non-existent session
- 4.2 Create session with invalid cart

### Running with Newman

```bash
# Run entire collection
newman run UCP_Checkout_Flow.postman_collection.json \
  --env-var "base_url=https://app.249ce.test"

# Run specific folder
newman run UCP_Checkout_Flow.postman_collection.json \
  --folder "2. UCP Checkout Flow" \
  --env-var "base_url=https://app.249ce.test"
```

## Response Examples

### Product
```json
{
  "id": "product_1234",
  "sku": "24-MB01",
  "name": "Joust Duffle Bag",
  "description": "...",
  "price": 3400,
  "currency": "USD",
  "url": "https://example.com/joust-duffle-bag.html",
  "images": [
    {"url": "https://example.com/media/catalog/product/m/b/mb01-blue-0.jpg", "alt": "Joust Duffle Bag"}
  ],
  "in_stock": true,
  "variants": []
}
```

### Category
```json
{
  "id": "category_27",
  "name": "Pants",
  "url": "https://example.com/women/bottoms-women/pants-women.html",
  "parent_id": "category_22",
  "level": 4,
  "product_count": 12
}
```

### Cart
```json
{
  "id": "cart_abc123",
  "currency": "USD",
  "items": [
    {
      "id": "item_456",
      "quantity": 2,
      "price": 3400,
      "subtotal": 6800,
      "product": {...}
    }
  ],
  "totals": [
    {"type": "subtotal", "amount": 6800, "display_text": "Subtotal"},
    {"type": "total", "amount": 6800, "display_text": "Total"}
  ],
  "item_count": 2
}
```

### Checkout Session
```json
{
  "id": "ucp_abc123",
  "status": "incomplete",
  "currency": "USD",
  "expires_at": "2026-01-20T18:00:00Z",
  "ucp": {
    "version": "2026-01-11",
    "capabilities": [{"name": "dev.ucp.shopping.checkout", "version": "2026-01-11"}]
  },
  "line_items": [...],
  "totals": [...],
  "buyer": {...},
  "payment": {"handlers": [...], "instruments": []},
  "fulfillment_options": [...],
  "links": [...],
  "messages": []
}
```

## Session Statuses

| Status | Description |
|--------|-------------|
| `incomplete` | Session created, awaiting buyer info |
| `ready_for_complete` | Ready to complete checkout |
| `completed` | Order placed successfully |
| `canceled` | Session canceled |
