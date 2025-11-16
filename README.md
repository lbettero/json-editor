🧩 JSON Menu Editor — PHP + Alpine.js (v2.0.0)

A robust, modular and extensible system for loading, editing, validating and saving multi-level menus stored in a menu.json file.
Built with PHP, Alpine.js and TailwindCSS, this module now includes a visual menu editor, global field system, type-aware metadata propagation, and a fully safe JSON-saving workflow.

Ideal for dashboards, CMS-like control panels, embedded UIs or any environment that requires a structured, metadata-rich dynamic menu.

🗓️ Version History
Version	Date	Description
v1.0.0	Initial release	Basic menu JSON loading and rendering.
v2.0.0	Current	Major update: Global Fields, rigid propagation, field types, updated editor UI, improved saving pipeline, and JSON normalization overhaul.
✨ Highlights of Version 2.0.0
🧬 1. Global Fields System (NEW)

Define fields that automatically apply to all menu items across all levels.

Each field includes:

Field name

Field type (string, number, boolean, json)

Default value

Ability to remove fields dynamically

These fields are then shown inside every menu item of the editor.

✔ Rigid propagation

Every item always contains all global fields.

✔ Guidance message added

Below the Global Fields section:

“After adding or removing global fields, save the menu so that these fields appear in all items.”

📝 2. Updated Visual Menu Editor

Add/edit/remove menu items

Add siblings and nested items

Up to 3 levels

Automatic name rebuilding for nested structures

Global fields appear dynamically inside each item

Enhanced layout and accessibility

💾 3. Improved JSON Saving (save-menu.php)

Now includes:

Conversion of field values according to their type

Application of Global Field schema to all items

Removal of obsolete fields

Normalization of tags

Full recursive processing

Pretty-printed JSON

Success screen with Download menu.json button

🧠 4. Architecture Improvements

Safer parsing

Cleaner PHP structure (functions, includes)

Enhanced frontend integration with Alpine.js

Item editor dynamically reacts to global field changes

📁 Project Structure
menu-json-editor/
│
├── assets/
│   ├── data/
│   │   └── menu.json
│   ├── icons/
│   └── css/
│       └── main.css
│
├── assets/js/
│   ├── menu-editor.js      ← NEW v2.0.0
│   ├── dashboard.js
│   ├── menu.js
│   └── menu-manager.js
│
├── src/
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   └── functions/
│       ├── menu.php
│       ├── menu-editor.php ← UPDATED v2.0.0
│       └── save-menu.php   ← UPDATED v2.0.0
│
├── menu-manager.php
└── README.md

🚀 How to Run

Start a PHP server:

php -S localhost:8000


Open:

http://localhost:8000/menu-manager.php

🔄 Workflow (v2.0.0)

menu.php loads JSON

menu-editor.php displays items + global fields

User edits both

Saving applies rigid schema enforcement

JSON is normalized and written to file

User may download updated menu.json

🧪 Validation & Safety

✔ HTML sanitized
✔ JSON validated
✔ Field type casting
✔ Rigid schema propagation
✔ Error details shown clearly

📜 Requirements

PHP 8+

No database required

Works on any hosting environment

👩‍💻 Author

Livia Pérez Bettero

🤖 Technical Collaboration

ChatGPT (OpenAI)