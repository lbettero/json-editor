# 🧩 JSON Menu Editor — PHP + Alpine.js

A lightweight and modular system for **loading, editing and saving multi-level menus** stored in a `menu.json` file.  
Built with **PHP**, **Alpine.js** and **TailwindCSS**, this module provides a **visual menu editor**, JSON normalization and a safe saving workflow.

Ideal for dashboards, CMS-like panels, embedded UIs or any project that requires a dynamic menu system.

---

## ✨ Features

### 📥 1. Load Menu From JSON
- Reads `menu.json` from `/assets/data/`
- Safe parsing with full error reporting
- Automatic fallback if the file is invalid or missing

### 📝 2. Visual Menu Editor (PHP + Alpine.js)
- Create, edit and delete menu items
- Add sibling items or nested children
- Supports up to **3 levels** of depth
- Drag-free, button-driven interaction
- Auto-generated `name` attributes for nested arrays
- Fully accessible HTML structure

### 🔧 3. JSON Normalization
Implemented in `save-menu.php`:

- Converts tag strings into arrays  
- Removes empty or incomplete entries  
- Recursively validates children  
- Produces clean, minimal, consistent JSON  
- Saves using `JSON_PRETTY_PRINT` and UTF-8  

### 💾 4. Save + Download Updated JSON
After saving:
- The user gets a success screen
- A **Download menu.json** button is displayed
- Error messages show technical details when needed

### 🔍 5. Optional Advanced Search (Integration Ready)
If used together with menu.js:
- Accent-insensitive search  
- Tokenized matching  
- Ranking by relevance  
- Tag inheritance  

---

## 📁 Project Structure

```
menu-json-editor/
│
├── assets/
│   ├── data/
│   │   └── menu.json
│   ├── icons/
│   └── css/
│       └── main.css
│
├── src/
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   └── functions/
│       ├── menu.php
│       ├── menu-editor.php
│       └── save-menu.php
│
├── menu-manager.php
└── README.md
```

---

## 🚀 How to Run

Start a PHP local server:

```bash
php -S localhost:8000
```

Open:

```
http://localhost:8000/menu-manager.php
```

---

## 🔄 Workflow

1. Load menu JSON → parsed by `menu.php`  
2. Edit items visually (`menu-editor.php`)  
3. Submit → normalized recursively (`save-menu.php`)  
4. JSON saved to `/assets/data/menu.json`  
5. Optional download of updated file  

---

## 🧪 Validation and Safety

✔ Input sanitized with `htmlspecialchars`  
✔ JSON validated before saving  
✔ Depth limited to 3 levels  
✔ Friendly error reporting  

---

## 📜 Requirements

- PHP 8+  
- No database required  
- Works standalone  

---

## 👩‍💻 Author
Livia Pérez Bettero

## 🤖 Technical Collaboration
ChatGPT (OpenAI)
