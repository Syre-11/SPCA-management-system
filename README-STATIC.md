# Static demo (GitHub Pages)

This project can run on **XAMPP with PHP/MySQL** or as a **static demo** on GitHub Pages using mock data in the browser.

## Demo logins

| Role | Username | Password | Position (dropdown) |
|------|----------|----------|---------------------|
| Admin | `admin` | `Admin123!` | Administrative Staff |
| Vet | `drsmith` | `Vet123!` | Veterinary Staff |
| Volunteer | `volunteer1` | `Vol123!` | Volunteer Staff |

The login page pre-fills the admin account. Data is seeded from `data/mockdb.json` and stored in `localStorage` (use **Reset mock data** in the yellow banner to restore defaults).

## Build locally

```bash
npm run build:static
```

Open `dist/index.html` via a local static server, or deploy `dist/` to GitHub Pages.

## GitHub Pages setup

1. Push to `main` — the workflow `.github/workflows/deploy-pages.yml` builds and deploys automatically.
2. In the repo **Settings → Pages**, set source to **GitHub Actions**.
3. Site URL: `https://<user>.github.io/SPCA-management-system/`

## Architecture

- `data/mockdb.json` — seed database
- `js/spca-store.js` — in-browser data layer
- `js/spca-auth.js` — session handling
- `js/spca-pages.js` — page renderers and forms
- `scripts/build-static.mjs` — converts `.php` pages to `.html` for static hosting

PHP source files remain for local XAMPP development; production demo uses the `dist/` output only.
