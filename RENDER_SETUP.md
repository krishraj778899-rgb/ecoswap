# EcoSwap - Render + Neon setup

1. Render Web Service Runtime: Docker.
2. Keep Build Command and Start Command empty; Dockerfile supplies them.
3. Add Render Environment Variable:
   - `DATABASE_URL` = the Neon PostgreSQL connection string from Neon Connect.
4. Run `database.sql` once in the Neon SQL Editor.
5. Deploy.

Project structure:
- index.html
- script.js
- style.css
- backend/api/register.php
- backend/api/login.php
- backend/api/logout.php
- backend/api/items.php
- backend/api/add-item.php
- backend/api/swap-request.php
- backend/config/database.php
- backend/uploads/
- Dockerfile

Registration stores a hashed password in Neon `users`. Item details go to `items`; swap requests go to `swap_requests`.
