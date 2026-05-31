# Static demo (GitHub Pages)

## How it works (simple)

This project has **two modes**:

| Mode | Where | Database |
|------|--------|----------|
| **XAMPP (original)** | `http://localhost/...` | MySQL `mockdb` |
| **Static demo** | GitHub Pages or `dist/` folder | `data/mockdb.json` in the browser |

On the static demo, PHP is converted to HTML. **JavaScript fills each page** from mock data (`js/spca-store.js` + `js/spca-pages.js`). Data is saved in your browser (`localStorage`). Use **Reset mock data** in the yellow banner to restore defaults.

You should see a green bar: *"Demo data loaded: 5 animals, 4 adoption apps..."* — if that is missing, open the site via a local server (`npx serve dist`), not by double-clicking HTML files.

## Demo logins

| Role | Username | Password | Position (dropdown) |
|------|----------|----------|---------------------|
| Admin | `admin` | `Admin123!` | Administrative Staff |
| Vet | `drsmith` | `Vet123!` | Veterinary Staff |
| Volunteer | `volunteer1` | `Vol123!` | Volunteer Staff |

The login page pre-fills the admin account. Data is seeded from `data/mockdb.json` and stored in `localStorage` (use **Reset mock data** in the yellow banner to restore defaults).

## Pages with mock data

| Area | Pages |
|------|--------|
| Login | Login, register |
| Admin | Dashboard, users, cruelty manage, view reports |
| Animals | Intake, display, kennels, update |
| Adoptions | Public adopt form, records, management |
| Medical | Vet dashboard, records list, create record |
| Volunteers | Apply, records, management, dashboard |
| Donations | Donation site, donor list |

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
