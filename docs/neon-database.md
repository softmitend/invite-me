# Neon Database Setup

InviteMe is configured for Neon PostgreSQL through Laravel's `pgsql` connection.

## Environment

Use the pooled Neon connection string for web requests:

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://USER:PASSWORD@HOST/DBNAME?sslmode=require
DB_SSLMODE=require
```

Keep `DB_URL` out of source control. The checked-in `.env.example` intentionally leaves credentials empty.

If you prefer split variables, leave `DB_URL` empty and fill:

```env
DB_CONNECTION=pgsql
DB_HOST=ep-example-pooler.ap-southeast-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=invite_me
DB_USERNAME=invite_me_owner
DB_PASSWORD=secret
DB_SSLMODE=require
```

## Recommended Neon Layout

- Production branch: production app only.
- Development branch: local development and seeded demo data.
- Test branch: automated tests, disposable data.

Do not run `migrate:fresh --seed` against production.

## Commands

After filling `.env` with Neon credentials:

```bash
php artisan config:clear
php artisan migrate --seed
php artisan test
```

For a new Neon development branch where data can be reset:

```bash
php artisan migrate:fresh --seed
```

For tests, copy `.env.testing.example` to `.env.testing`, fill it with a separate Neon test branch, then run:

```bash
php artisan test
```

## Notes

- PHP must have `pdo_pgsql` enabled.
- Neon requires SSL, so `DB_SSLMODE=require` is set by default.
- Midtrans credentials stay separate in `MIDTRANS_*` environment variables.
