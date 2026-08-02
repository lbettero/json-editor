# JSON Explorer

A browser-based workspace for understanding and managing arbitrary JSON files.

## What it does

- Loads `.json` files locally with the File API.
- Infers and displays one unique structure for repeated objects and array items.
- Shows data types, nested properties, item counts and optional fields.
- Finds editable collections in root arrays or object properties.
- Lists and searches collection items.
- Creates, edits and deletes items without modifying the original file.
- Downloads the updated data as a new JSON file.

The file content is never uploaded to a server. All processing happens in the browser.

## Run locally

```bash
cd basic_website_menu
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000/menu-manager.php`.

## Current editing behavior

- Object properties become typed form fields.
- Nested objects become grouped fieldsets.
- Booleans use a `true`/`false` selector.
- Numbers use numeric inputs.
- Arrays and mixed values use a JSON textarea to preserve their structure.
- An empty collection accepts the first item as a raw JSON object; after that, the inferred schema generates the form.

## Requirements

- PHP 8+ for the local static server.
- A modern browser with File API, `dialog` and Blob download support.
