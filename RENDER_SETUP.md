# EcoSwap - Render + Neon setup

1. Render Web Service Runtime: Docker.
2. Keep Build Command and Start Command empty.
3. Add:
   - `DATABASE_URL` = Neon PostgreSQL connection string from Neon Connect.
4. Deploy.

The PHP backend automatically creates the required PostgreSQL tables on first database connection, so running `database.sql` manually is optional for a fresh database.

Tables:
- `users` — registration/login accounts
- `items` — listed items and image filename/path
- `swap_requests` — swap requests and their status

Important:
- Uploaded images are stored in `/backend/uploads/` inside the container. For durable production image storage, use a persistent disk or object storage; database rows store the image filename/path.
