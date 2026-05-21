<div align="center">
  <a href="https://vitejs.dev/">
    <img width="200" height="200" hspace="10" src="https://raw.githubusercontent.com/mrOttoW/vite-wordpress/ef6f4b84aa9da549e9908d8c21513d53dfe020bc/vite-logo.svg" alt="vite logo" />
  </a>
  <h1>Vite PHP utilities for WordPress</h1>
  <p>
Backend Utilities for <a href="https://github.com/mrOttoW/vite-wordpress">vite-wordpress</a> to integrate Vite's development server and HMR into WordPress, as well as manage the manifest file.
</p>
  <img src="https://img.shields.io/github/v/release/mrOttoW/vite-wordpress-php" alt="GitHub release" />
   <img alt="Packagist Version" src="https://img.shields.io/packagist/v/mrottow/vite-wordpress">
  <img src="https://img.shields.io/github/last-commit/mrOttoW/vite-wordpress-php" alt="GitHub last commit"/>
  <img alt="Packagist License" src="https://img.shields.io/packagist/l/mrottow/vite-wordpress">
</div>

## Features

- **Manifest Resolver:** Parse and manage Vite's manifest file for registering assets in WordPress for production.
- **Dev Server & HMR Integration:** Integrates Vite's HMR (Hot Module Replacement) and dev server into WordPress during development.
  - Supports frontend block development
  - Supports Elementor editor development
- **Automatic Script Injection:** Automatically injects Vite client scripts into WordPress during development.
- **Automatic Source Files Injection:**
  - Automatically changes src urls from enqueued scripts/styles to source files for HMR during development.
  - Automatically changes render template paths from dynamic blocks in block.json to source files for HMR during development.

---

## Requirements

- PHP 7.4 or higher
- WordPress 6.0 or higher
- A valid ViteJS configuration using <a href="https://github.com/mrOttoW/vite-wordpress">vite-wordpress</a>

---

## Installation

1. Add the library to your project:
   ```bash
   composer require mrottow/vite-wordpress
   ```
2. Include the autoloader in your WordPress theme or plugin:
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

---

## Usage

### Dev Server
The `DevServer` class integrates the Vite development server with WordPress for HMR.

#### Example (uses default settings):
```php
(new ViteWordPress\DevServer())->register();
```

#### With custom settings:
```php
(new ViteWordPress\DevServer())
    ->set_server_port(5173)
    ->set_server_host('localhost')
    ->register();
```

When using the <a href="https://github.com/mrOttoW/vite-wordpress">vite-wordpress</a> ViteJS plugin, if files have been built using `vite build`, are enqueued and the development server is running via the `vite` command:

1. DevServer automatically detects all enqueued scripts & templates from the project through hooks & `vite-wordpress` server configurations.
2. It resolves scripts to source files served by the development server.
3. It resolves templates to source files from the `srcDir` folder.
4. It updates script tags from the project to use as modules and injects Vite's client to enable HMR (Hot Module Replacement).
5. Using Vite's client and module support, it can also include CSS source files that are imported into JavaScript files.

It works for both JS and CSS entries.

It's compatible with a traditional setup with <a href="https://github.com/mrOttoW/vite-wordpress">vite-wordpress</a> without the need for a manifest or hashed files.

However, when using a manifest, the Manifest Resolver will assist in resolving the files through the manifest
offering better precision for a more complex assets folder & file structure.

### Manifest Resolver
The `ManifestResolver` class handles reading and accessing the Vite manifest file and additionally integrates into the dev server.

You can use the `ManifestResolver` instance, which needs to be wrapped in a function or helper class to work within hooks. Alternatively, you can use the `Manifest` facade, which handles this for you.

#### Example using the facade:
```php

use ViteWordPress\DevServer;
use ViteWordPress\Manifest;

$manifest = Manifest::create( 'absolute/path/to/manifest.json' ); // Also works with a PHP manifest file.

// When using the dev server you need to include the manifest.
( new DevServer( $manifest ) )->register();

// Enqueue scripts hook.
add_action( 'wp_enqueue_scripts', function () {
	$file_name = Manifest::get_file( 'app.js' );

	wp_enqueue_script( 'my-app', get_stylesheet_directory() . "build/{$file_name}" );
} );
```

#### Example using the instance:
```php

use ViteWordPress\DevServer;
use ViteWordPress\ManifestResolver;

function manifest() {
	static $manifest;

	if ( ! isset( $manifest ) ) {
		$manifest = new ManifestResolver();
		$manifest->set_manifest( 'absolute/path/manifest.json' ); // Also works with a PHP manifest file.
	}

	return $manifest;
}

// When using the dev server you need to include the manifest.
( new DevServer( manifest() ) )->register();

// Enqueue scripts hook.
add_action( 'wp_enqueue_scripts', function () {
	$file_name = manifest()->get( 'app.js' )['file'];

	wp_enqueue_script( 'my-app', get_stylesheet_directory() . "build/{$file_name}" );
} );
```

The manifest resolver is built off an interface so you're able to create your own implementation to include into the dev server.

---

## Configuration

### ManifestResolver
| Method                | Default | Description                                                  |
|-----------------------|---------|--------------------------------------------------------------|
| `set_manifest($path)` | ``      | Sets the path to the `manifest.json` or `manifest.php` file. |
| `set_src($dir)`       | `'src'` | (optional) Sets the source directory for the assets.         |

### DevServer
| Method                      | Default        | Description                                            |
|-----------------------------|----------------|--------------------------------------------------------|
| `set_server_host($host)`    | `get_site_url()` | (optional) Sets the Vite dev server host.              |
| `set_server_port($port)`    | `5173`         | (optional) Sets the Vite dev server port.              |
| `set_client_hook($priority)`| `5`            | (optional) Sets the priority for the Vite client hook. |

---


## Example
An example within a WordPress plugin can be found here:
https://github.com/mrOttoW/vite-wordpress-plugin-block-example
