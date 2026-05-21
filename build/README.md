# ATUM Vite build toolchain

## Requirements

- [Bun](https://bun.sh/) (package manager and runtime for scripts)
- Node.js >= 22 (used by Vite when running `bun run build`)
- Composer (PHP dev dependency `mrottow/vite-wordpress` for HMR)

## Setup

```bash
composer install
bun install
bun run build
```

## Development (HMR in wp-admin)

1. Set `WP_ENVIRONMENT_TYPE` to `local` or `development` in `wp-config.php`.
2. Run a production build once so `dist/.vite/manifest.json` exists.
3. Start the dev server:

```bash
bun run dev
```

4. Open the ATUM admin screens on your local URL (e.g. `https://atum.loc`).

Port **5173** is reserved for the base plugin. Addons use their own ports (5174+).

## Scripts

| Command | Description |
|---------|-------------|
| `bun run dev` | Vite dev server + HMR — **the development workflow** |
| `bun run build` | Production build to `dist/` (one IIFE bundle per JS entry, parallelized) |
| `bun run typecheck` | `tsc --noEmit` type check (not run by the build) |
| `bun run lint:scripts` | Lint `assets/js/src` and `build/` |

> There is no build watch mode: use `bun run dev` for iterative work (HMR
> recompiles TS/SCSS on the fly). The production build is a one-shot command.

## Entry discovery

- **JS:** every `assets/js/src/*.ts` → `dist/js/atum-{name}.js` (except `post-type-list-tables.ts` → `atum-post-type-list.js`)
- **CSS:** `assets/scss/atum-*.scss` and `assets/scss/rtl/*.scss` → `dist/css/`

Add a new `.ts` or entry SCSS file; no config change required.

## Addons

Import the shared factory from the base plugin:

```js
import { createAtumViteConfig } from '../atum-stock-manager-for-woocommerce/build/create-atum-vite-config.mjs';

export default createAtumViteConfig({
  pluginSlug: 'atum-export-pro',
  port: 5174,
  jsFilePrefix: 'atum-export-',
  jsRename: { 'export-central': 'central' },
  displayName: 'ATUM Export Pro',
});
```

Each addon has its own `package.json`, `bun.lock`, and `node_modules`.
