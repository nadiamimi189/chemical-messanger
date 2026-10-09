# Chemical Connect

A member-based social platform built for a chemical industry community, with
public sharing and private conversations enforced on the server:

- **Sign up / Log in** with name, email & password. Nothing on the site is
  visible without an account.
- **Admin posts** are visible to **every** member.
- **Comments on Admin's posts are private per member** — each member only
  ever sees their *own* comments and Admin's replies to *them*. Members can
  never see another member's comments.
- **Member posts and uploads** are visible **only to that member and the Admin**
  — never to other members.
- **Admin replies** to a specific member's comment thread are delivered only
  to that member.
- **Direct chat with Admin** — a private 1-to-1 messaging widget. A member
  can only chat with Admin; Admin can chat with any member, and each
  conversation is private to that pair.

Built with plain **PHP 8 + MySQL (PDO)** on the back end and **HTML, CSS and
vanilla JavaScript (fetch/AJAX)** on the front end — no frameworks required.

---

## 1. Requirements

- PHP 8.0+ with the following extensions enabled (all are on by default in
  XAMPP / WAMP / MAMP and virtually every shared host):
  - `pdo_mysql`
  - `mbstring`
  - `fileinfo` (optional, used implicitly by uploads)
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache/Nginx) or simply PHP's built-in server for local use

## 2. Installation

1. **Unzip** this project into your web root, e.g.
   `C:\xampp\htdocs\chemical-connect` (XAMPP) or `/var/www/html/chemical-connect`.

2. **Create the database** — import the included `database.sql` file. It
   creates the `chemical_connect` database, all tables, and seed accounts.

   Using phpMyAdmin: click *Import*, choose `database.sql`, click *Go*.

   Using the command line:
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure the DB connection** in `config/db.php` if your MySQL
   username/password/host differ from the defaults:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'chemical_connect';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

4. **Install PHP dependencies** from the project root:
  ```bash
  composer install
  ```

5. **Configure SMTP** by setting these environment variables for Apache/PHP,
  then restart Apache:
  - `SMTP_HOST`, `SMTP_PORT` (usually `587`), `SMTP_USERNAME`, `SMTP_PASSWORD`
  - `SMTP_ENCRYPTION` (`tls` for STARTTLS or `ssl` for implicit TLS)
  - `SMTP_FROM_EMAIL` (must be authorized by the SMTP provider)
  - `SMTP_FROM_NAME` (optional, defaults to `Chemical Connect`)
  - `APP_URL` (public base URL, defaults to `http://localhost/chemical-messanger`)

  Password reset uses PHPMailer over authenticated SMTP. Existing databases
  must also import `migrations/20261009_create_password_resets.sql`; fresh
  imports of `database.sql` already include the required token table. Reset
  links expire after one hour and can only be used once.

6. **Set folder permissions** so PHP can save uploads:
   ```bash
   chmod -R 755 uploads/
   ```

7. **Open the site** in your browser, e.g. `http://localhost/chemical-connect/`
   You'll land on `login.php`.

### Quick local test (no Apache needed)

```bash
php -S localhost:8000
```
Then visit `http://localhost:8000/login.php`.

## 3. Demo accounts (from database.sql)

| Role  | Email                          | Password  |
|-------|--------------------------------|-----------|
| Admin | admin@chemicalconnect.com      | admin123  |
| Member| demo@chemicalconnect.com       | user1234  |

**Change these passwords (or delete the demo member) before going live.**

## 4. Project structure

```
chemical-connect/
├── admin/                  Admin-only pages
│   ├── dashboard.php        Publish public posts, view/reply to per-member comment threads
│   ├── users.php            List of all registered members
│   ├── user_detail.php      A single member's private uploads (admin view)
│   └── messages.php         Admin's chat inbox (talk to any member privately)
├── api/                    AJAX endpoints (JSON)
│   ├── add_comment.php       Post/reply to a private comment thread
│   ├── toggle_like.php       Like/unlike a public post
│   ├── send_message.php      Send a private chat message
│   ├── get_messages.php      Poll for new chat messages
│   └── delete_post.php       Admin: delete a public post
├── assets/
│   ├── css/style.css         All styling
│   └── js/main.js            AJAX + UI behaviour
├── config/
│   └── db.php                Database credentials (edit this)
├── includes/
│   ├── init.php               Bootstraps session + db + helpers
│   ├── auth.php                Login/role guard functions
│   ├── functions.php           Sanitization, uploads, HTML renderers
│   ├── header.php / footer.php HTML shell
│   └── navbar.php              Top navigation bar
├── uploads/
│   ├── posts/                 Media for Admin's public posts and legacy member posts
│   ├── user_posts/            Media for members' private uploads
│   └── avatars/                (reserved for future use)
├── index.php                Member home feed
├── my_posts.php             Member's own private posts and uploads
├── signup.php / login.php / logout.php
└── database.sql             Full schema + seed data
```

## 5. How the privacy model is enforced

- **`admin_posts`** — public posts authored by Admin. Every member's feed reads
  Admin-authored rows from this table.
- **`user_posts`** — one row per private upload, tagged with `user_id`. Every
  page that lists these always filters `WHERE user_id = <the owner>` (a
  member's own pages) or is only reachable from an admin-only controller
  (`admin/user_detail.php`) — a regular member has no page or endpoint that
  can list another member's rows.
- **`post_comments`** — every comment is tagged with **both** `owner_user_id`
  (whose private thread it belongs to) and `author_id` (who actually wrote
  it — the member or Admin replying). A member's feed always queries
  `WHERE post_id = ? AND owner_user_id = <the logged-in member>`, so they can
  only ever see their own thread and Admin's replies inside it. Admin's
  dashboard is the only place that can query multiple `owner_user_id`s for a
  given post.
- **`messages`** — private 1-to-1 rows between `sender_id` and `receiver_id`.
  `api/send_message.php` enforces that a member may only message the single
  Admin account; Admin may message any member. Conversation loads are always
  scoped to `(sender, receiver)` pairs that include the current session user.

## 6. Notes for production use

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored
  in plain text.
- All SQL uses PDO prepared statements.
- Uploaded file extensions and sizes are validated server-side
  (`includes/functions.php::handleMediaUpload`).
- A CSRF token protects the post-composer forms; consider extending this to
  every state-changing form if you harden the app further.
- For real deployments, put the project behind HTTPS, set a strong DB
  password, and change/remove the demo accounts.
