==================================================
Ecommerce Master API - Complete Documentation
Version: 2.1.0
Base URL: https://yoursite.com/wp-json/ecommerce-api/v1
==================================================

OVERVIEW
========
This API provides comprehensive ecommerce functionality including authentication, 
products, cart, orders, reviews, wishlist, and address management.

AUTHENTICATION
==============
All authenticated endpoints require Bearer token authentication.

Headers for Authenticated Endpoints:
- Authorization: Bearer {session_token}
- X-Auth-Token: {session_token} (alternative)
- Content-Type: application/json

Token Retrieval:
- Obtain session_token from /auth/login or /auth/register endpoints
- Tokens expire after 30 days
- Maximum 5 active tokens per user

AUTH ENDPOINTS (/auth)
======================

1. POST /auth/login
   Authenticate user and get session token

   Headers:
   - Content-Type: application/json

   Request Body:
   {
     "username": "user@example.com",
     "password": "userpassword123"
   }

   Response:
   {
     "status": 200,
     "message": "Login successful",
     "data": {
       "id": 123,
       "username": "user@example.com",
       "email": "user@example.com",
       "display_name": "John Doe",
       "first_name": "John",
       "last_name": "Doe",
       "session_token": "a1b2c3d4e5f6...",
       "token_expires": 1735689600
     }
   }

2. POST /auth/register
   Register new user account

   Headers:
   - Content-Type: application/json

   Request Body:
   {
     "username": "newuser",
     "email": "newuser@example.com",
     "password": "newpassword123",
     "first_name": "Jane",
     "last_name": "Smith"
   }

   Response:
   {
     "status": 201,
     "message": "Registration successful",
     "data": {
       "id": 124,
       "username": "newuser",
       "email": "newuser@example.com",
       "display_name": "Jane Smith",
       "first_name": "Jane",
       "last_name": "Smith",
       "session_token": "f6e5d4c3b2a1...",
       "token_expires": 1735689600
     }
   }

3. GET /auth/profile
   Get current user profile

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "status": 200,
     "message": "",
     "data": {
       "id": 123,
       "username": "user@example.com",
       "email": "user@example.com",
       "display_name": "John Doe",
       "first_name": "John",
       "last_name": "Doe",
       "billing": {
         "first_name": "John",
         "last_name": "Doe",
         "company": "",
         "address_1": "123 Main St",
         "address_2": "",
         "city": "New York",
         "state": "NY",
         "postcode": "10001",
         "country": "US",
         "email": "user@example.com",
         "phone": "555-0123"
       },
       "shipping": {
         "first_name": "John",
         "last_name": "Doe",
         "company": "",
         "address_1": "123 Main St",
         "address_2": "",
         "city": "New York",
         "state": "NY",
         "postcode": "10001",
         "country": "US"
       }
     }
   }

4. PUT /auth/profile
   Update user profile

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "first_name": "John",
     "last_name": "Doe",
     "display_name": "John Doe",
     "email": "john.doe@example.com",
     "billing": {
       "first_name": "John",
       "last_name": "Doe",
       "address_1": "123 Main St",
       "city": "New York",
       "state": "NY",
       "postcode": "10001",
       "country": "US",
       "email": "john.doe@example.com",
       "phone": "555-0123"
     }
   }

   Response:
   {
     "status": 200,
     "message": "Profile updated successfully",
     "data": null
   }

5. POST /auth/forgot-password
   Request password reset

   Headers:
   - Content-Type: application/json

   Request Body:
   {
     "email": "user@example.com"
   }

   Response:
   {
     "status": 200,
     "message": "Password reset email sent",
     "data": null
   }

6. POST /auth/logout
   Logout and invalidate token

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "status": 200,
     "message": "Logout successful",
     "data": null
   }

7. GET /auth/validate-token
   Validate current session token

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "status": 200,
     "message": "Token is valid",
     "data": {
       "id": 123,
       "username": "user@example.com",
       "email": "user@example.com",
       "display_name": "John Doe",
       "first_name": "John",
       "last_name": "Doe",
       "token_valid": true
     }
   }

ADDRESS ENDPOINTS (/addresses)
===============================

All address endpoints require authentication.

1. GET /addresses
   Get user's billing and shipping addresses

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "billing": {
         "first_name": "John",
         "last_name": "Doe",
         "company": "",
         "address_1": "123 Main St",
         "address_2": "",
         "city": "New York",
         "state": "NY",
         "postcode": "10001",
         "country": "US",
         "email": "user@example.com",
         "phone": "555-0123",
         "formatted": "John Doe<br/>123 Main St<br/>New York, NY 10001<br/>United States (US)"
       },
       "shipping": {
         "first_name": "John",
         "last_name": "Doe",
         "company": "",
         "address_1": "123 Main St",
         "address_2": "",
         "city": "New York",
         "state": "NY",
         "postcode": "10001",
         "country": "US",
         "formatted": "John Doe<br/>123 Main St<br/>New York, NY 10001<br/>United States (US)"
       },
       "default_billing": "billing",
       "default_shipping": "shipping"
     }
   }

2. POST /addresses/add
   Add new address

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "type": "billing",
     "first_name": "John",
     "last_name": "Doe",
     "company": "ACME Inc",
     "address_1": "456 Oak Avenue",
     "address_2": "Suite 100",
     "city": "Los Angeles",
     "state": "CA",
     "postcode": "90210",
     "country": "US",
     "email": "john.doe@example.com",
     "phone": "555-0124",
     "is_default": true
   }

   Response:
   {
     "success": true,
     "message": "Address added successfully",
     "data": null
   }

3. PUT /addresses/update
   Update existing address

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "type": "billing",
     "first_name": "John",
     "last_name": "Doe",
     "address_1": "789 Pine Street",
     "city": "San Francisco",
     "state": "CA",
     "postcode": "94102",
     "country": "US",
     "is_default": false
   }

   Response:
   {
     "success": true,
     "message": "Address updated successfully",
     "data": null
   }

4. DELETE /addresses/delete
   Delete address

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "type": "billing"
   }

   Response:
   {
     "success": true,
     "message": "Address deleted successfully",
     "data": null
   }

5. POST /addresses/set-default
   Set default address

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "type": "shipping"
   }

   Response:
   {
     "success": true,
     "message": "Default address set successfully",
     "data": null
   }

6. GET /addresses/countries
   Get available countries and states (Public)

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "countries": [
         {
           "code": "US",
           "name": "United States",
           "states": {
             "AL": "Alabama",
             "AK": "Alaska",
             "CA": "California"
           }
         },
         {
           "code": "GB",
           "name": "United Kingdom",
           "states": {}
         }
       ],
       "default_country": "US",
       "default_state": "CA"
     }
   }

PRODUCTS ENDPOINTS (/products)
===============================

1. GET /products
   Get paginated products list (Public)

   Query Parameters:
   - page (integer, default: 1): Page number
   - per_page (integer, default: 12, max: 100): Items per page
   - category (string): Filter by category slug
   - search (string): Search products
   - min_price (number): Minimum price filter
   - max_price (number): Maximum price filter
   - orderby (string): date, price, rating, popularity, title
   - order (string): asc, desc
   - featured (boolean): Only featured products
   - on_sale (boolean): Only products on sale
   - stock_status (string): instock, outofstock, onbackorder

   Request:
   GET /products?page=1&per_page=12&category=electronics&min_price=10&max_price=1000

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "products": [
         {
           "id": 456,
           "name": "Wireless Headphones",
           "slug": "wireless-headphones",
           "type": "simple",
           "price": 99.99,
           "regular_price": 129.99,
           "sale_price": 99.99,
           "on_sale": true,
           "stock_status": "instock",
           "stock_quantity": 50,
           "sku": "WH-001",
           "description": "High-quality wireless headphones...",
           "short_description": "Noise cancelling wireless headphones",
           "thumbnail": "https://yoursite.com/wp-content/uploads/headphones.jpg",
           "gallery_images": ["img1.jpg", "img2.jpg"],
           "permalink": "https://yoursite.com/product/wireless-headphones",
           "average_rating": 4.5,
           "rating_count": 24,
           "categories": [
             {
               "id": 15,
               "name": "Electronics",
               "slug": "electronics"
             }
           ],
           "attributes": [...]
         }
       ],
       "pagination": {
         "total": 150,
         "per_page": 12,
         "current_page": 1,
         "total_pages": 13
       }
     }
   }

2. GET /products/{id}
   Get single product details (Public)

   Request:
   GET /products/456

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "id": 456,
       "name": "Wireless Headphones",
       ... // Full product details including variations, attributes, etc.
     }
   }

3. PUT /products/update/{id}
   Update product (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body (partial updates supported):
   {
     "regular_price": 139.99,
     "sale_price": 109.99,
     "stock_quantity": 25,
     "stock_status": "instock",
     "sku": "WH-001-UPDATED",
     "name": "Updated Product Name",
     "description": "Updated product description",
     "weight": 0.5
   }

   Response:
   {
     "success": true,
     "message": "Product updated successfully",
     "data": {
       "product_id": 456,
       "updated_fields": ["regular_price", "sale_price", "stock_quantity"],
       "changes": {
         "before": {
           "regular_price": "129.99",
           "sale_price": "99.99",
           "stock_quantity": 50
         },
         "after": {
           "regular_price": "139.99",
           "sale_price": "109.99",
           "stock_quantity": 25
         }
       },
       "product": {...}
     }
   }

4. GET /products/category/{category_id}
   Get products by category (Public)

   Query Parameters: Same as /products

   Request:
   GET /products/category/15?per_page=8

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "category": {
         "id": 15,
         "name": "Electronics",
         "slug": "electronics",
         "description": "Electronic devices and accessories",
         "count": 45
       },
       "products": [...],
       "pagination": {...}
     }
   }

5. GET /products/search
   Search products (Public)

   Query Parameters:
   - query (required): Search term
   - page, per_page, etc. (same as /products)

   Request:
   GET /products/search?query=wireless&per_page=10

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "search_query": "wireless",
       "products": [...],
       "pagination": {...}
     }
   }

6. GET /categories
   Get all product categories (Public)

   Query Parameters:
   - hide_empty (boolean, default: true): Hide empty categories

   Request:
   GET /categories

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "categories": [
         {
           "id": 15,
           "name": "Electronics",
           "slug": "electronics",
           "description": "Electronic devices",
           "count": 45,
           "permalink": "https://yoursite.com/product-category/electronics",
           "image": {
             "id": 789,
             "src": "https://yoursite.com/wp-content/uploads/electronics.jpg"
           }
         }
       ],
       "total": 12
     }
   }

7. GET /categories/{id}
   Get single category (Public)

   Request:
   GET /categories/15

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "id": 15,
       "name": "Electronics",
       "slug": "electronics",
       "description": "Electronic devices and accessories",
       "count": 45,
       "permalink": "https://yoursite.com/product-category/electronics",
       "image": {...},
       "display_type": "default",
       "parent": null,
       "children": [...]
     }
   }

CART ENDPOINTS (/cart)
======================

All cart endpoints require authentication.

1. GET /cart
   Get current user's cart

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Cart retrieved successfully",
     "data": {
       "items": [
         {
           "key": "a1b2c3d4e5f6",
           "product_id": 456,
           "variation_id": 0,
           "quantity": 2,
           "name": "Wireless Headphones",
           "sku": "WH-001",
           "price": 99.99,
           "regular_price": 129.99,
           "sale_price": 99.99,
           "on_sale": true,
           "line_subtotal": 199.98,
           "line_total": 199.98,
           "line_tax": 16.00,
           "line_total_with_tax": 215.98,
           "thumbnail": "https://yoursite.com/headphones-thumb.jpg",
           "stock_quantity": 50,
           "stock_status": "instock",
           "attributes": [],
           "categories": [...],
           "tags": [...]
         }
       ],
       "totals": {
         "subtotal": 199.98,
         "subtotal_tax": 16.00,
         "subtotal_with_tax": 215.98,
         "shipping_total": 5.00,
         "shipping_tax": 0.40,
         "shipping_with_tax": 5.40,
         "discount_total": 0.00,
         "discount_tax": 0.00,
         "discount_with_tax": 0.00,
         "total": 221.38,
         "total_tax": 16.40,
         "total_with_tax": 221.38,
         "currency": "USD",
         "currency_symbol": "$",
         "price_format": "%1$s%2$s"
       },
       "summary": {
         "item_count": 1,
         "total_items": 2,
         "needs_shipping": true,
         "is_empty": false,
         "shipping_methods": [
           {
             "id": "flat_rate:1",
             "label": "Flat Rate",
             "cost": 5.00,
             "method_id": "flat_rate",
             "instance_id": 1
           }
         ],
         "applied_coupons": []
       },
       "meta": {
         "timestamp": 1735689600,
         "user_id": 123,
         "cart_hash": "abc123def456"
       }
     }
   }

2. POST /cart/add
   Add product to cart

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_id": 456,
     "quantity": 1,
     "variation_id": 789,  // For variable products
     "variation": {        // For variable products
       "color": "black",
       "size": "large"
     }
   }

   Response:
   {
     "success": true,
     "message": "Product added to cart successfully",
     "data": {
       "cart_item_key": "a1b2c3d4e5f6",
       "product_id": 456,
       "variation_id": 789,
       "quantity": 1,
       "item_count": 3,
       "product_name": "Wireless Headphones",
       "product_price": 99.99
     }
   }

3. PUT /cart/update
   Update cart item quantity

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "cart_item_key": "a1b2c3d4e5f6",
     "quantity": 3
   }

   Response:
   {
     "success": true,
     "message": "Cart updated successfully",
     "data": {
       "cart_item_key": "a1b2c3d4e5f6",
       "quantity": 3,
       "item_count": 3,
       "product_name": "Wireless Headphones"
     }
   }

4. DELETE /cart/remove
   Remove item from cart

   Headers:
   - Authorization: Bearer {session_token}

   Request Body:
   {
     "cart_item_key": "a1b2c3d4e5f6"
   }

   Response:
   {
     "success": true,
     "message": "Item removed from cart successfully",
     "data": {
       "removed_item": {
         "cart_item_key": "a1b2c3d4e5f6",
         "product_id": 456,
         "product_name": "Wireless Headphones",
         "quantity": 3
       },
       "cart_summary": {
         "previous_item_count": 3,
         "current_item_count": 2,
         "total_items_removed": 1,
         "is_empty": false
       },
       "remaining_items": ["x1y2z3..."]
     }
   }

5. DELETE /cart/clear
   Clear entire cart

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Cart cleared successfully",
     "data": {
       "cleared_items": 2,
       "item_count": 0
     }
   }

6. GET /cart/count
   Get cart items count

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "count": 3,
       "total": "$221.38"
     }
   }

7. POST /cart/apply-coupon
   Apply coupon code

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "coupon_code": "SAVE10"
   }

   Response:
   {
     "success": true,
     "message": "Coupon applied successfully",
     "data": null
   }

8. DELETE /cart/remove-coupon
   Remove coupon

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "coupon_code": "SAVE10"
   }

   Response:
   {
     "success": true,
     "message": "Coupon removed successfully",
     "data": null
   }

ORDERS ENDPOINTS (/orders)
==========================

All orders endpoints require authentication.

1. POST /orders/create
   Create new order from cart

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "billing_first_name": "John",
     "billing_last_name": "Doe",
     "billing_email": "john.doe@example.com",
     "billing_phone": "555-0123",
     "billing_address_1": "123 Main St",
     "billing_city": "New York",
     "billing_state": "NY",
     "billing_postcode": "10001",
     "billing_country": "US",
     "shipping_first_name": "John",
     "shipping_last_name": "Doe",
     "shipping_address_1": "123 Main St",
     "shipping_city": "New York",
     "shipping_state": "NY",
     "shipping_postcode": "10001",
     "shipping_country": "US",
     "payment_method": "stripe"
   }

   Response:
   {
     "success": true,
     "message": "Order created successfully",
     "data": {
       "order_id": 7890,
       "order_number": "7890",
       "status": "pending",
       "total": 221.38
     }
   }

2. GET /orders
   Get user's orders with pagination

   Headers:
   - Authorization: Bearer {session_token}

   Query Parameters:
   - page (integer, default: 1): Page number
   - per_page (integer, default: 10): Orders per page

   Request:
   GET /orders?page=1&per_page=5

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "orders": [
         {
           "id": 7890,
           "number": "7890",
           "status": "processing",
           "status_label": "Processing",
           "date_created": "2024-01-15 14:30:00",
           "date_modified": "2024-01-15 14:35:00",
           "total": 221.38,
           "currency": "USD",
           "payment_method": "Credit Card",
           "billing": {
             "first_name": "John",
             "last_name": "Doe",
             "email": "john.doe@example.com",
             "phone": "555-0123",
             "address_1": "123 Main St",
             "city": "New York",
             "state": "NY",
             "postcode": "10001",
             "country": "US"
           },
           "shipping": {
             "first_name": "John",
             "last_name": "Doe",
             "address_1": "123 Main St",
             "city": "New York",
             "state": "NY",
             "postcode": "10001",
             "country": "US"
           }
         }
       ],
       "pagination": {
         "current_page": 1,
         "per_page": 5,
         "total_orders": 12,
         "total_pages": 3
       }
     }
   }

3. GET /orders/{id}
   Get single order details

   Headers:
   - Authorization: Bearer {session_token}

   Request:
   GET /orders/7890

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "id": 7890,
       "number": "7890",
       "status": "processing",
       "status_label": "Processing",
       "date_created": "2024-01-15 14:30:00",
       "date_modified": "2024-01-15 14:35:00",
       "total": 221.38,
       "currency": "USD",
       "payment_method": "Credit Card",
       "billing": {...},
       "shipping": {...},
       "items": [
         {
           "id": 12345,
           "product_id": 456,
           "name": "Wireless Headphones",
           "quantity": 2,
           "price": 99.99,
           "subtotal": 199.98,
           "thumbnail": "https://yoursite.com/headphones-thumb.jpg"
         }
       ],
       "totals": {
         "subtotal": 199.98,
         "shipping_total": 5.00,
         "discount_total": 0.00,
         "tax_total": 16.40,
         "total": 221.38
       }
     }
   }

4. PUT /orders/cancel
   Cancel an order

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "order_id": 7890
   }

   Response:
   {
     "success": true,
     "message": "Order cancelled successfully",
     "data": null
   }

5. GET /orders/tracking/{id}
   Get order tracking information

   Headers:
   - Authorization: Bearer {session_token}

   Request:
   GET /orders/tracking/7890

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "order_id": 7890,
       "status": "processing",
       "status_label": "Processing",
       "date_created": "2024-01-15 14:30:00",
       "date_modified": "2024-01-15 14:35:00",
       "tracking_number": "TRK123456789",
       "tracking_provider": "UPS",
       "tracking_link": "https://ups.com/track?num=TRK123456789",
       "notes": [
         {
           "date": "2024-01-15 14:30:00",
           "content": "Order received and being processed"
         }
       ]
     }
   }

6. GET /orders/statuses
   Get all available order statuses

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Success",
     "data": [
       {
         "key": "wc-pending",
         "label": "Pending payment",
         "slug": "pending"
       },
       {
         "key": "wc-processing",
         "label": "Processing",
         "slug": "processing"
       }
     ]
   }

REVIEWS ENDPOINTS (/reviews)
============================

1. POST /reviews/add
   Add product review (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_id": 456,
     "rating": 5,
     "review": "Excellent product! Great quality and fast delivery.",
     "title": "Amazing Headphones",
     "images": [123, 124]  // Optional: attachment IDs
   }

   Response:
   {
     "success": true,
     "message": "Review submitted and awaiting moderation",
     "data": {
       "review_id": 5678,
       "status": "pending"
     }
   }

2. GET /reviews/product/{id}
   Get product reviews (Public)

   Query Parameters:
   - page (integer, default: 1)
   - per_page (integer, default: 10, max: 100)
   - rating (integer, 1-5): Filter by rating
   - sort (string): newest, oldest, highest_rating, lowest_rating, most_helpful, featured
   - images_only (boolean): Only reviews with images

   Request:
   GET /reviews/product/456?page=1&per_page=5&sort=highest_rating

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "reviews": [
         {
           "id": 5678,
           "author": {
             "name": "John Doe",
             "email": "john.doe@example.com",
             "avatar": "https://gravatar.com/avatar/...",
             "user_id": 123
           },
           "title": "Amazing Headphones",
           "content": "Excellent product! Great quality...",
           "rating": 5,
           "date": "2024-01-15 14:30:00",
           "date_gmt": "2024-01-15 19:30:00",
           "verified": true,
           "helpful_data": {
             "helpful_count": 3,
             "unhelpful_count": 0,
             "total_votes": 3
           },
           "images": [
             {
               "id": 123,
               "url": "https://yoursite.com/review-image1.jpg",
               "thumbnail": "https://yoursite.com/review-image1-150x150.jpg"
             }
           ],
           "product_id": 456
         }
       ],
       "statistics": {
         "total_reviews": 24,
         "average_rating": 4.5,
         "rating_distribution": {
           "1": 1,
           "2": 2,
           "3": 3,
           "4": 8,
           "5": 10
         },
         "reviews_with_images": 5
       },
       "pagination": {
         "current_page": 1,
         "per_page": 5,
         "total_reviews": 24,
         "total_pages": 5
       }
     }
   }

3. GET /reviews/user
   Get user's reviews (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}

   Query Parameters:
   - page, per_page

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "reviews": [
         {
           "id": 5678,
           ... // review data
           "product": {
             "id": 456,
             "name": "Wireless Headphones",
             "permalink": "https://yoursite.com/product/wireless-headphones",
             "image": "https://yoursite.com/headphones-thumb.jpg",
             "price": {...}
           }
         }
       ],
       "pagination": {...}
     }
   }

4. GET /reviews/{id}
   Get single review (Public)

   Request:
   GET /reviews/5678

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "id": 5678,
       ... // detailed review data including user_vote, reports, featured status
     }
   }

5. PUT /reviews/update
   Update review (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678,
     "rating": 4,
     "review": "Updated review text...",
     "title": "Updated Title"
   }

   Response:
   {
     "success": true,
     "message": "Review updated successfully",
     "data": null
   }

6. DELETE /reviews/delete
   Delete review (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678
   }

   Response:
   {
     "success": true,
     "message": "Review deleted successfully",
     "data": null
   }

7. GET /reviews/statistics/product/{id}
   Get review statistics (Public)

   Request:
   GET /reviews/statistics/product/456

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "average_rating": 4.5,
       "review_count": 24,
       "rating_distribution": {...},
       "reviews_with_images": 5,
       "rating_summary": {
         "excellent": 41.7,
         "good": 33.3,
         "average": 12.5,
         "poor": 8.3,
         "terrible": 4.2
       }
     }
   }

8. POST /reviews/vote/helpful
   Vote on review helpfulness (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678,
     "helpful": "yes"  // or "no"
   }

   Response:
   {
     "success": true,
     "message": "Vote recorded successfully",
     "data": {
       "helpful_data": {
         "helpful_count": 4,
         "unhelpful_count": 0,
         "total_votes": 4
       },
       "user_vote": "yes"
     }
   }

9. POST /reviews/report
   Report a review (Authenticated)

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678,
     "reason": "spam",  // spam, inappropriate, false_information, other
     "description": "This appears to be fake"
   }

   Response:
   {
     "success": true,
     "message": "Review reported successfully",
     "data": null
   }

10. GET /reviews/featured/product/{id}
    Get featured reviews for product (Public)

    Request:
    GET /reviews/featured/product/456

    Response:
    {
      "success": true,
      "message": "Success",
      "data": [
        ... // array of featured reviews
      ]
    }

WISHLIST ENDPOINTS (/wishlist)
==============================

All wishlist endpoints require authentication.

1. GET /wishlist
   Get user's wishlist

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "wishlist": [
         {
           "product_id": 456,
           "name": "Wireless Headphones",
           "slug": "wireless-headphones",
           "type": "simple",
           "price": 99.99,
           "regular_price": 129.99,
           "sale_price": 99.99,
           "on_sale": true,
           "stock_status": "instock",
           "thumbnail": "https://yoursite.com/headphones-thumb.jpg",
           "permalink": "https://yoursite.com/product/wireless-headphones",
           "average_rating": 4.5,
           "rating_count": 24,
           "categories": [...],
           "date_added": "2024-01-15 14:30:00"
         }
       ],
       "count": 5,
       "user_id": 123,
       "max_items": 100
     }
   }

2. POST /wishlist/add
   Add product to wishlist

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_id": 456
   }

   Response:
   {
     "success": true,
     "message": "Product added to wishlist successfully",
     "data": {
       "product_id": 456,
       "product_name": "Wireless Headphones",
       "wishlist_count": 6,
       "in_wishlist": true,
       "date_added": "2024-01-15 14:30:00"
     }
   }

3. POST /wishlist/bulk-add
   Add multiple products to wishlist

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_ids": [456, 789, 101]
   }

   Response:
   {
     "success": true,
     "message": "Bulk operation completed. Added: 2, Failed: 1",
     "data": {
       "results": {
         "added": [456, 789],
         "failed": [101],
         "already_exists": [],
         "invalid": []
       },
       "wishlist_count": 7,
       "total_processed": 3
     }
   }

4. DELETE /wishlist/remove
   Remove product from wishlist

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_id": 456
   }

   Response:
   {
     "success": true,
     "message": "Product removed from wishlist successfully",
     "data": {
       "product_id": 456,
       "product_name": "Wireless Headphones",
       "wishlist_count": 6,
       "in_wishlist": false
     }
   }

5. POST /wishlist/bulk-remove
   Remove multiple products from wishlist

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "product_ids": [456, 789]
   }

   Response:
   {
     "success": true,
     "message": "Bulk removal completed. Removed: 2, Not found: 0",
     "data": {
       "results": {
         "removed": [456, 789],
         "not_found": []
       },
       "wishlist_count": 4,
       "total_processed": 2
     }
   }

6. DELETE /wishlist/clear
   Clear entire wishlist

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Wishlist cleared successfully. 4 items removed.",
     "data": {
       "items_cleared": 4,
       "wishlist_count": 0
     }
   }

7. GET /wishlist/count
   Get wishlist items count

   Headers:
   - Authorization: Bearer {session_token}

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "count": 5,
       "user_id": 123,
       "max_items": 100
     }
   }

8. GET /wishlist/check/{product_id}
   Check if product is in wishlist

   Headers:
   - Authorization: Bearer {session_token}

   Request:
   GET /wishlist/check/456

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "product_id": 456,
       "product_name": "Wireless Headphones",
       "in_wishlist": true,
       "user_id": 123,
       "date_added": "2024-01-15 14:30:00"
     }
   }

ADMIN REVIEWS ENDPOINTS (/admin/reviews)
========================================

Admin endpoints require administrator or moderator permissions.

1. GET /admin/reviews/pending
   Get pending reviews for moderation

   Headers:
   - Authorization: Bearer {session_token}

   Query Parameters:
   - page, per_page

   Response:
   {
     "success": true,
     "message": "Success",
     "data": {
       "reviews": [
         {
           "id": 5678,
           ... // review data
           "product": {
             "id": 456,
             "name": "Wireless Headphones"
           },
           "report_count": 2
         }
       ],
       "pagination": {...}
     }
   }

2. POST /admin/reviews/approve
   Approve a pending review

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678,
     "reason": "Review meets guidelines"  // optional
   }

   Response:
   {
     "success": true,
     "message": "Review approved successfully",
     "data": null
   }

3. POST /admin/reviews/reject
   Reject a pending review

   Headers:
   - Authorization: Bearer {session_token}
   - Content-Type: application/json

   Request Body:
   {
     "review_id": 5678,
     "reason": "Contains inappropriate content"  // optional
   }

   Response:
   {
     "success": true,
     "message": "Review rejected successfully",
     "data": null
   }

SYSTEM & COMPATIBILITY
======================

The API includes comprehensive compatibility features:

HPOS (High-Performance Order Storage):
- Full compatibility with WooCommerce HPOS
- Automatic data store compatibility
- Seamless integration with custom order tables

Block Editor Compatibility:
- Product block editor support
- Cart and checkout blocks integration
- Custom payment method registration

System Requirements:
- PHP 7.4 or higher
- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- REST API enabled

CACHE MANAGEMENT
================

The API implements intelligent caching:

Cache Durations:
- Product data: 30 minutes to 2 hours
- Category data: 2 hours
- Cart data: 5 minutes
- User data: 30 minutes
- Order data: 15-30 minutes
- Address data: 30 minutes
- Countries/states: 24 hours

Automatic Cache Clearing:
- Product updates clear product cache
- Order updates clear order cache
- Review updates clear review cache
- User updates clear user cache

UTILITIES
=========

The API provides utility functions for:

Data Validation:
- Email validation
- Phone number validation
- Credit card validation (Luhn algorithm)
- URL validation

Formatting:
- Price formatting
- Date/time formatting
- Text truncation
- Slug generation

Security:
- IP address detection
- Input sanitization
- Random string generation

ERROR RESPONSES
===============

All endpoints return consistent error responses:

1. Authentication Errors (401):
   {
     "code": "rest_forbidden",
     "message": "Authentication required.",
     "data": {"status": 401}
   }

2. Validation Errors (400):
   {
     "success": false,
     "message": "Product ID is required",
     "data": null
   }

3. Not Found Errors (404):
   {
     "success": false,
     "message": "Product not found",
     "data": null
   }

4. Permission Errors (403):
   {
     "success": false,
     "message": "You are not authorized to update this review",
     "data": null
   }

5. Server Errors (500):
   {
     "success": false,
     "message": "An unexpected error occurred",
     "data": null
   }

RATE LIMITING
=============
- Wishlist endpoints: 10 requests per minute per user
- Review endpoints: 10 requests per minute per user
- Address endpoints: 20 requests per minute per user
- Other endpoints: Standard WordPress rate limiting

FEATURES
========
- Token-based authentication with 30-day expiry
- Comprehensive product management with filtering and search
- Full cart functionality with coupons and shipping
- Complete order lifecycle management
- Advanced review system with ratings, images, and helpfulness voting
- Wishlist with bulk operations
- Address management with countries/states support
- Admin moderation for reviews
- HPOS and block editor compatibility
- Intelligent caching system
- Rate limiting and security
- WooCommerce integration
- RESTful API design

VERSIONING
==========
- Current API version: v1
- Namespace: /ecommerce-api/v1/
- Backward compatibility maintained within major versions

SUPPORT
=======
For API support and documentation updates, contact your development team.