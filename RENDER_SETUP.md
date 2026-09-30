# EcoSwap - Render + Neon Setup

## 1. Render Environment Variable
Add exactly:

`DATABASE_URL` = the full PostgreSQL connection string copied from Neon Console -> Connect.

Example format only:
`postgresql://neondb_owner:YOUR_PASSWORD@ep-example.neon.tech/neondb?sslmode=require`

Do not paste the example. Use your real Neon connection string.

## 2. Photo storage
Item photos are stored as actual binary data in Neon PostgreSQL:
- `items.image_data` = BYTEA photo data
- `items.image_mime` = MIME type

No S3/R2 storage credentials are required for this implementation.
The application has no item-delete endpoint, so uploaded item photos are not deleted by the app.

## 3. Database setup
The PHP database connection automatically creates/migrates:
- users
- items
- swap_requests
- conversations
- messages

## 4. Render runtime
Use Docker runtime. Build Command and Start Command can remain blank because the Dockerfile supplies the start command.

## 5. Health check
After deployment, open:
`https://YOUR-SERVICE.onrender.com/backend/api/health.php`

Expected JSON includes `success: true` and `database: "neon"`.

## 6. Upload limit
The Docker image includes PHP upload settings above 5 MB so the application's own 5 MB image validation is the effective limit.
