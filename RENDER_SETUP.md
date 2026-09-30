# EcoSwap — Render deployment

## Why the old deployment failed

Render was treating the repository as a Node service, so it used Node.js 24 and then ran:
`php -S 0.0.0.0:$PORT`

That failed because PHP was not installed in that runtime.

The project is now configured as a Docker deployment. The file is named exactly `Dockerfile` (capital D), PHP 8.2 CLI is installed, and `pdo_pgsql` is enabled.

## Render settings

Create the service as **Web Service → Docker**.

Environment variable:
- `DATABASE_URL` = Render PostgreSQL **Internal Database URL**
- Optional: `APP_DEBUG=1` temporarily while testing. Remove/disable it after testing.

The Dockerfile automatically listens on Render's `$PORT`.

## Database

1. Create a PostgreSQL database in Render.
2. Open the database's connection details.
3. Copy the **Internal Database URL** into the web service as `DATABASE_URL`.
4. Run `database.sql` against that PostgreSQL database once.

## Important project structure

- `index.html`
- `script.js`
- `style.css`
- `backend/api/*.php`
- `backend/config/database.php`
- `backend/uploads/`
- `Dockerfile`

The frontend calls `backend/api/`, matching this structure.

## After deployment

Open the Render service URL and test:
1. Register a new user.
2. Log in.
3. Add an item.
4. Browse items.
5. Send a swap request from a different account.
6. Check the PostgreSQL tables: `users`, `items`, `swap_requests`.

Do not use the old Render Node start command `php -S ...` on a Node runtime. The Dockerfile now provides the PHP runtime.
