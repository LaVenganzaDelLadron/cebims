# CEBIMS API cURL Commands

These examples use the local Laravel server and assume it is running:

```bash
php artisan serve
```

Set the API URL and token variables once per shell session:

```bash
export API_URL="http://127.0.0.1:8000/api"
export USER_TOKEN="paste-user-token-here"
export ADMIN_TOKEN="paste-admin-token-here"
```

Protected endpoints use Sanctum bearer tokens:

```bash
curl -H "Authorization: Bearer $USER_TOKEN" "$API_URL/me"
```

## Public endpoints

### Register a regular user

The public registration endpoint always creates a regular user. Any submitted `role` value is ignored.

```bash
curl -X POST "$API_URL/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "address": "123 Main Street",
    "phone": "09123456789",
    "email": "juan@example.com",
    "username": "juandelacruz",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Copy the returned `data.token` into `USER_TOKEN`.

### Login with email or username

```bash
curl -X POST "$API_URL/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "juan@example.com",
    "password": "password123"
  }'
```

The `login` value may also be a username:

```bash
curl -X POST "$API_URL/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"login":"juandelacruz","password":"password123"}'
```

### Request a password reset

```bash
curl -X POST "$API_URL/forgot-password" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@example.com"}'
```

### Reset a password

Use the reset token delivered by the configured mail provider.

```bash
curl -X POST "$API_URL/reset-password" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "password-reset-token",
    "email": "juan@example.com",
    "password": "new-password123",
    "password_confirmation": "new-password123"
  }'
```

### Verify an email address

Use the `id` and `hash` from the verification URL sent by Laravel.

```bash
curl -G "$API_URL/email/verify/USER_ID/VERIFICATION_HASH" \
  -H "Accept: application/json"
```

### Browse categories

```bash
curl -H "Accept: application/json" "$API_URL/categories"
curl -H "Accept: application/json" "$API_URL/categories/1"
```

### Browse equipment

```bash
curl -H "Accept: application/json" "$API_URL/home-equipment"
curl -H "Accept: application/json" "$API_URL/equipment"
curl -H "Accept: application/json" "$API_URL/equipment/1"
```

## Authenticated user endpoints

### Get the authenticated user

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/me"
```

### Get and update the profile

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/profile"
```

```bash
curl -X PUT "$API_URL/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "address": "456 Updated Street",
    "phone": "09987654321",
    "email": "juan.updated@example.com",
    "username": "juanupdated"
  }'
```

Change the password through the same profile endpoint. Existing Sanctum tokens are revoked after a password reset.

```bash
curl -X PUT "$API_URL/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "new-password123",
    "password_confirmation": "new-password123"
  }'
```

### Email verification

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/email/verification-notification"
```

```bash
curl -X POST "$API_URL/email/verification-notification" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN"
```

### Logout

This revokes the current Sanctum token.

```bash
curl -X POST "$API_URL/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN"
```

### View personal borrowings

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/my-borrowings"
```

The regular-user list endpoint is also available:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/borrow-requests"
```

### Submit a borrow request

`quantity` must be at least `1`. The requested quantity is checked against available inventory during approval.

```bash
curl -X POST "$API_URL/borrow-requests" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "barangay": "Barangay San Isidro",
    "purpose": "Community sports event",
    "borrow_date": "2026-10-01",
    "expected_return_date": "2026-10-03",
    "items": [
      {
        "equipment_id": 1,
        "quantity": 10,
        "remarks": "Blue chairs preferred"
      },
      {
        "equipment_id": 2,
        "quantity": 2,
        "remarks": "Folding tables"
      }
    ]
  }'
```

### View a borrow request

Users may view only their own requests. Admins may view any request.

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/borrow-requests/BORROW_REQUEST_ID"
```

## Admin endpoints

All admin endpoints require `ADMIN_TOKEN` and return `403 Forbidden` for regular users.

### Dashboard statistics

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/dashboard"
```

### Manage users

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/users"
```

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/users/USER_ID"
```

Update a user profile. Do not use this endpoint to remove the last administrator.

```bash
curl -X PUT "$API_URL/admin/users/USER_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "address": "Updated address",
    "phone": "09111222333"
  }'
```

Delete a user:

```bash
curl -X DELETE "$API_URL/admin/users/USER_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Manage categories

```bash
curl -X POST "$API_URL/admin/categories" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "category_name": "Event Equipment",
    "description": "Equipment used for city events"
  }'
```

```bash
curl -X PUT "$API_URL/admin/categories/CATEGORY_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "category_name": "Updated Event Equipment",
    "description": "Updated description"
  }'
```

```bash
curl -X DELETE "$API_URL/admin/categories/CATEGORY_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Manage equipment

Create equipment without an image:

```bash
curl -X POST "$API_URL/admin/equipment" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "equipment_name": "Folding Chair",
    "description": "Standard event chair",
    "total_quantity": 100,
    "available_quantity": 100,
    "equipment_condition": "Good",
    "storage_location": "Warehouse A",
    "status": "Available"
  }'
```

Create equipment with an image. The server validates the image and generates its stored filename.

```bash
curl -X POST "$API_URL/admin/equipment" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -F "category_id=1" \
  -F "equipment_name=Folding Chair" \
  -F "description=Standard event chair" \
  -F "total_quantity=100" \
  -F "available_quantity=100" \
  -F "equipment_condition=Good" \
  -F "storage_location=Warehouse A" \
  -F "status=Available" \
  -F "image=@/absolute/path/to/chair.jpg"
```

Update equipment data:

```bash
curl -X PUT "$API_URL/admin/equipment/EQUIPMENT_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "available_quantity": 80,
    "equipment_condition": "Excellent",
    "storage_location": "Warehouse B",
    "status": "Available"
  }'
```

Update equipment and replace its image. `POST` with `_method=PUT` is used for multipart method spoofing.

```bash
curl -X POST "$API_URL/admin/equipment/EQUIPMENT_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -F "_method=PUT" \
  -F "equipment_name=Updated Folding Chair" \
  -F "image=@/absolute/path/to/new-chair.webp"
```

Delete equipment:

```bash
curl -X DELETE "$API_URL/admin/equipment/EQUIPMENT_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Manage borrow requests

List all requests:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/borrow-requests"
```

Approve a pending request:

```bash
curl -X PUT "$API_URL/admin/borrow-requests/BORROW_REQUEST_ID/approve" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

Approval locks inventory, decreases `available_quantity`, and creates a transaction. A request cannot be approved twice.

Reject a pending request:

```bash
curl -X PUT "$API_URL/admin/borrow-requests/BORROW_REQUEST_ID/reject" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "admin_notes": "The requested dates are unavailable."
  }'
```

### Manage transactions and returns

List borrowed items:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/transactions"
```

Return equipment:

```bash
curl -X PUT "$API_URL/admin/transactions/TRANSACTION_ID/return" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "equipment_id": 1,
        "quantity_returned": 10,
        "item_condition": "Good",
        "remarks": "Returned in good condition"
      }
    ],
    "remarks": "All items checked by the administrator"
  }'
```

Use `item_condition: "Lost"` for lost items. Lost quantities are logged but are not added back to available inventory. A transaction cannot be returned twice, and returned quantities cannot exceed borrowed quantities.

View transaction history:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/history"
```

### View audit logs

List audit logs:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  "$API_URL/admin/audit-logs"
```

Filter by action and date:

```bash
curl -G "$API_URL/admin/audit-logs" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  --data-urlencode "action=admin.equipment.updated" \
  --data-urlencode "from=2026-09-01" \
  --data-urlencode "to=2026-09-30"
```

## Expected authorization errors

Missing or invalid tokens return `401 Unauthorized`:

```bash
curl -i "$API_URL/profile"
```

Regular users calling an admin endpoint return `403 Forbidden`:

```bash
curl -i -H "Authorization: Bearer $USER_TOKEN" \
  "$API_URL/admin/dashboard"
```

Validation failures return `422 Unprocessable Entity`. Successful creation normally returns `201 Created`; reads and updates normally return `200 OK`.
