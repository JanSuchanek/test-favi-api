# favi-api

Simple REST API for receiving partner orders. Implemented with Symfony, Doctrine and API Platform.

Quick start (local - without Docker)
1. Install dependencies:
   - composer install
2. Prepare test DB (SQLite default):
   - mkdir -p var
   - export DATABASE_URL="sqlite:///${PWD}/var/data.db"
   - php bin/console doctrine:database:create --env=dev --if-not-exists
   - php bin/console doctrine:migrations:migrate --no-interaction
3. Run built-in web server:
   - php -S 127.0.0.1:8080 -t public
   - or: symfony server:start

Docker (recommended for reproducible environment)
1. Build and run containers:
   - docker compose up --build -d
   - The app will be available on http://localhost:8080
2. Inside container you can run commands, for example:
   - docker compose exec favi sh
   - composer install
   - php bin/console doctrine:migrations:migrate --no-interaction --env=dev

API
- Base URL: `/api`
- Endpoints added by API Platform (important examples):
  - POST `/api/orders` — create order (returns 201) or 409 if order exists
    - Request body example:
      ```json
      {
        "partnerId": "shop-1",
        "orderId": "ord-123",
        "expectedDeliveryAt": "2025-12-01",
        "products": [{"productId": "p1", "name": "Product 1", "price": 100, "quantity": 2}]
      }
      ```
  - PATCH `/api/orders/{partnerId}/{orderId}/delivery-date` — update expected delivery date
    - Request body example:
      ```json
      { "expectedDeliveryAt": "2025-12-10" }
      ```

Behavior: on duplicate `partnerId+orderId` POST the API returns HTTP 409 with body:
```json
{ "error": "order already exists", "existing": { "partnerId": "...", "orderId": "...", "totalPrice": 123, "location": "/api/orders/.." } }
```

Testing
- Run unit and integration tests locally:
  - composer test
  - or: ./vendor/bin/phpunit -c phpunit.dist.xml

Notes
- The project uses API Platform for resources and OpenAPI generation (`/api` browsable docs).
- Database default in tests is SQLite `var/data.db`.

If you want, I can also add a CI workflow and a small `Makefile` with common commands.


