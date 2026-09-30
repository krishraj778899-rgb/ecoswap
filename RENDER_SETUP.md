# EcoSwap - Render + Neon setup

1. Set the Render service Runtime to **Docker**.
2. Keep Build Command and Start Command empty so the Dockerfile controls the build/start.
3. Add this environment variable:

   `DATABASE_URL=<Neon PostgreSQL connection string>`

4. Deploy the project.
5. The PHP backend automatically creates/migrates these PostgreSQL tables on the first request:
   - users
   - items
   - swap_requests
   - conversations
   - messages

## Main flows

- Register/Login -> `users`
- List Item -> `items` (including phone, location, image_data and image_mime)
- Browse -> `items`
- Request Swap -> `swap_requests` + `conversations`
- Chat -> `messages`
- Offer price -> `messages.offer_price`

Images are stored directly in Neon PostgreSQL as `BYTEA` in `items.image_data`, with MIME type in `items.image_mime`.
