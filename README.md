# AR Furniture Catalog & AR Product Viewer

A complete, dependency-free **PHP + MySQL** web application for browsing a
furniture catalog, viewing each product as an interactive 3D model, and
placing it in the real world using your phone's camera (Augmented Reality)
via Google `<model-viewer>` (WebXR / Google Scene Viewer / Apple Quick Look).

No Node.js, npm, Composer, or JS framework required. Runs on plain PHP 8.3+,
MySQL/MariaDB, and Apache — works on XAMPP locally and on any standard
cPanel/Apache shared hosting account.

---

## 1. Features

**Storefront**
- Responsive homepage with hero section, category filter, and product grid
- Product detail page with an interactive 3D viewer and an AR launch button
- Dynamic QR code so a desktop visitor can open the exact page on their phone
- Read-only JSON API (`api/products.php`)

**Admin panel**
- Secure login (hashed passwords, session regeneration)
- Product CRUD (name, price, description, dimensions, category, GLB/USDZ
  model, thumbnail, active/inactive, sort order)
- Category CRUD (with safe delete — reassign or block deletion when
  products are still attached)
- Dashboard with quick stats

**Security**
- PDO prepared statements everywhere (no raw SQL concatenation)
- `password_hash()` / `password_verify()`, session ID regeneration on login
- CSRF tokens on all admin POST forms
- Randomized upload filenames, extension + MIME sniffing, `.htaccess`
  execution-blocking in `models/` and `uploads/`
- HTML output escaping via `h()`

---

## 2. Project structure

```
ar-furniture/
├── index.php                 Storefront homepage
├── product.php                Product detail page (3D/AR viewer)
├── .htaccess / .user.ini      Apache + PHP settings
│
├── admin/                     Admin panel (all protected by session auth)
│   ├── login.php / logout.php
│   ├── index.php              Dashboard + product list
│   ├── product_edit.php       Add/Edit product
│   ├── product_delete.php
│   ├── categories.php         Category list
│   ├── category_edit.php      Add/Edit category
│   └── category_delete.php
│
├── api/
│   └── products.php           Public read-only JSON API
│
├── config/
│   ├── config.php              <-- EDIT THIS with your DB credentials
│   └── .htaccess               Blocks direct access
│
├── includes/                   Shared PHP logic (DB, auth, helpers, CRUD)
├── css/styles.css
├── js/app.js, js/qr.js
├── models/                     Uploaded .glb/.gltf/.usdz files go here
├── uploads/thumbs/             Uploaded thumbnail images go here
└── sql/database.sql            Full schema + sample data
```

---

## 3. Installation — XAMPP (local development)

1. Copy the `ar-furniture` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\ar-furniture\`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import
   `sql/database.sql` (this creates the `ar_furniture` database, tables,
   and sample data automatically — no manual SQL needed).
4. Open `config/config.php` and set your database credentials (defaults
   `root` / empty password work for a stock XAMPP install).
5. **Set the default admin password.** The sample SQL ships with a
   placeholder hash — before logging in, generate a real one:
   ```bash
   php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
   ```
   Then run in phpMyAdmin (or any MySQL client):
   ```sql
   UPDATE admins SET password_hash = 'PASTE_THE_HASH_HERE' WHERE username = 'admin';
   ```
6. Open `http://localhost/ar-furniture/` in your browser.
7. Log in at `http://localhost/ar-furniture/admin/login.php` with
   username `admin` and the password you just set.
8. The **Modern Armchair** includes a simple original demo GLB and thumbnail.
   Open that product to check the 3D viewer. Upload `.glb` (and optionally
   `.usdz`) models for the other products through **Admin → Products → Add/Edit**.

**Phone testing on the same Wi-Fi:** `localhost` on a phone refers to the phone,
not your computer. Run `ipconfig` on Windows to find your computer's IPv4
address, then open `http://YOUR-COMPUTER-IP/ar-furniture/` on both devices.
Allow Apache through Windows Firewall on your private network. The QR code
will then contain the LAN IP. A LAN HTTP page may not offer WebXR; for a
reliable AR demonstration, use HTTPS hosting. The demo does not include USDZ,
so upload one for iPhone Quick Look support.

---

## 4. Deployment — cPanel / shared hosting

1. In cPanel, open **MySQL® Databases** and create a new database and a
   database user, then add that user to the database with **All Privileges**.
2. Open **phpMyAdmin**, select your new (empty) database, and import
   `sql/database.sql`.
3. Upload the entire `ar-furniture` folder via **File Manager** or FTP
   (e.g. into `public_html/` or `public_html/ar-furniture/`).
4. Edit `config/config.php` with the database host/name/user/password
   cPanel gave you (host is usually `localhost`).
5. Regenerate and set the admin password hash exactly as in step 5 above.
6. Ensure `models/` and `uploads/thumbs/` are writable (755 is normally
   sufficient; avoid 777).
7. Make sure HTTPS is enabled for your domain (the root `.htaccess`
   automatically forces HTTPS in production while leaving `localhost`
   untouched).
8. Visit your domain, confirm the homepage and a product page load, then
   confirm GLB files are reachable directly (e.g.
   `https://yourdomain.com/ar-furniture/models/yourfile.glb` should
   download/display, not 404 or show PHP source).
9. Test AR on an actual Android and/or iPhone device (see Section 6).
10. Test the QR code on the homepage and a product page from a desktop
    browser by scanning it with your phone.

The application auto-detects its own base URL (domain + subfolder), so
you do not need to hard-code your domain anywhere — the QR code and all
internal links adjust automatically.

---

## 5. Admin panel guide

| Task                       | Where                                   |
|-----------------------------|------------------------------------------|
| Log in / out                | `admin/login.php`, `admin/logout.php`   |
| View stats & manage products| `admin/index.php`                       |
| Add / edit a product         | `admin/product_edit.php`                |
| Delete a product              | Delete button on the product table       |
| Manage categories             | `admin/categories.php`                  |
| Add / edit a category          | `admin/category_edit.php`               |
| Delete a category (with products) | `admin/category_delete.php` — lets you reassign products first |

When adding/editing a product you can upload:
- **3D Model (.glb/.gltf)** — required for the viewer/AR button to appear
- **iOS AR Model (.usdz)** — optional, improves the AR experience on
  iPhone/iPad via Apple Quick Look
- **Thumbnail image** — shown on the catalog grid and as the product page
  poster while the 3D model loads

You can also record the **measured GLB dimensions** (in meters) alongside
the real-world dimensions (in cm) so you can manually verify a model's
scale is correct before publishing — the system does not assume every
uploaded GLB is scaled correctly.

---

## 6. How the AR feature works

The product page renders Google's `<model-viewer>` web component with:

```html
<model-viewer
    src="models/your-model.glb"
    ios-src="models/your-model.usdz"
    ar
    ar-modes="webxr scene-viewer quick-look"
    camera-controls>
</model-viewer>
```

- **Android (Chrome):** tapping the AR button launches either **WebXR**
  (in-browser AR) or hands off to **Google Scene Viewer**, depending on
  device/browser support — `<model-viewer>` picks automatically based on
  `ar-modes`.
- **iPhone/iPad (Safari):** tapping AR hands off to **Apple Quick Look**
  using the `.usdz` file (via `ios-src`). If no `.usdz` is uploaded, the
  AR button will not activate on iOS — only the GLB file is required for
  desktop/Android viewing and rotation.
- **Desktop / unsupported devices:** the AR button is hidden automatically
  by `<model-viewer>`; a text hint below the viewer explains that AR needs
  a compatible mobile device.
- **Recovery after AR:** `js/app.js` listens for the `ar-status` event,
  `visibilitychange`, and `pageshow` (bfcache) to detect when a device
  returns from an AR session, and re-attaches the model's `src` if the
  viewer looks like it needs a refresh — this avoids a frozen/blank
  viewer after backgrounding the browser for AR.

### Testing checklist

**Desktop**
- [ ] Homepage loads, categories filter correctly
- [ ] Product cards link to the correct product page
- [ ] Product page loads, 3D model rotates/zooms with mouse
- [ ] QR code renders and points to the correct current-page URL
- [ ] Admin login works; dashboard stats are correct

**Android**
- [ ] Product page opens, GLB loads
- [ ] AR button appears (device/browser must support ARCore + Chrome)
- [ ] AR launches (WebXR or Scene Viewer) and places the model
- [ ] Placed furniture looks roughly the right real-world size

**iPhone / iPad**
- [ ] Product page opens, GLB loads for on-page viewing
- [ ] AR button launches Apple Quick Look (requires a `.usdz` upload)
- [ ] Furniture can be viewed/placed in the camera view

---

## 7. Configuration reference (`config/config.php`)

| Key | Purpose |
|---|---|
| `app.name` | Site name shown in the header/footer/titles |
| `app.base_url` | Leave `''` to auto-detect; set explicitly only if needed |
| `app.timezone` | PHP timezone for `created_at`/footer year |
| `app.default_currency` | Default currency code for new products |
| `db.*` | MySQL host/name/user/password/charset |
| `uploads.models_dir` / `uploads.thumbs_dir` | Storage folders (relative to project root) |
| `uploads.max_model_mb` / `uploads.max_image_mb` | Upload size limits |
| `uploads.allowed_model_ext` / `allowed_image_ext` | Whitelisted file extensions |

---

## 8. Notes & limitations

- A simple original armchair `.glb` is bundled for demonstration; replace it
  with a detailed model if needed. No sample `.usdz` is included.
- The homepage's QR code image is generated by requesting a PNG from a
  public QR-generation endpoint (`api.qrserver.com`), passing only the
  page's own public URL as the payload — no product or visitor data is
  sent. If you need a fully offline solution, replace the implementation
  in `js/qr.js` with a self-hosted QR image endpoint.
- The default admin credentials in `sql/database.sql` are a placeholder —
  **you must regenerate and set your own password hash** before deploying
  publicly (see Section 3, step 5).
