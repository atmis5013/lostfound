# 🧠 Lost & Found Project Setup (Symfony + Python + Docker)

### ✅ Goal
A small **Lost & Found web app** built with:
- Symfony (backend, API)
- Python (matcher microservice)
- MySQL (database)
- Mailhog (email testing)
- phpMyAdmin (DB viewer)
- Docker Compose (everything containerized)

---

## 1️⃣ Environment setup on Windows

**Prerequisites:**
- Install Docker Desktop
- Enable WSL 2
- Install VS Code (recommended)

Create folder structure:
```
projects/
└── lostfound/
    ├── symfony/
    ├── python/
    ├── docker-compose.yml
```

---

## 2️⃣ Docker Compose file

```yaml
version: '3.8'

services:
  symfony:
    image: php:8.2-apache
    container_name: symfony_app
    volumes:
      - ./symfony:/var/www/html
    ports:
      - "8080:80"
    depends_on:
      - db
      - mailhog

  db:
    image: mysql:8.0
    container_name: symfony_db
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: lostfound
      MYSQL_USER: symfony
      MYSQL_PASSWORD: symfony
    ports:
      - "3307:3306"

  mailhog:
    image: mailhog/mailhog
    container_name: mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: symfony_phpmyadmin
    environment:
      - PMA_HOST=db
      - PMA_USER=symfony
      - PMA_PASSWORD=symfony
    ports:
      - "8081:80"
    depends_on:
      - db
```

---

## 3️⃣ Create Symfony project inside Docker

Run:
```bash
docker exec -it symfony_app bash
composer create-project symfony/skeleton .
```

Then fix permissions and Apache config:
- Installed `git`, `unzip`, `zip`
- Installed `pdo_mysql` for database driver
- Fixed Apache `DocumentRoot` to `/var/www/html/public`
- Added this to `/etc/apache2/sites-available/000-default.conf`:

```apache
<Directory /var/www/html/public>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
DocumentRoot /var/www/html/public
```

Restart Apache:
```bash
a2enmod rewrite
apache2ctl restart
```

✅ Result: Symfony welcome page visible at [http://localhost:8080](http://localhost:8080)

---

## 4️⃣ Fixed installation issues

| Problem | Solution |
|----------|-----------|
| `Forbidden: You don't have permission` | Updated Apache directory config |
| `Too few arguments to function PHPStan\PhpDocParser` | Installed `phpstan/phpdoc-parser:^1.24` |
| `composer command not found` | Installed `composer` inside the container |
| `could not find driver` | Installed PHP extensions: `pdo` and `pdo_mysql` |
| `git/unzip missing` | Installed via `apt-get install -y git unzip zip` |

---

## 5️⃣ Connect Symfony to MySQL

Created `.env.local` in `/symfony`:
```dotenv
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=1234567890abcdef

DATABASE_URL="mysql://symfony:symfony@db:3306/lostfound"
MAILER_DSN="smtp://mailhog:1025"
```

Then:
```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:query:sql "SHOW DATABASES;"
```

✅ Result: Database `lostfound` created and connected.

---

## 6️⃣ View database via phpMyAdmin

Added `phpmyadmin` service and accessed [http://localhost:8081](http://localhost:8081)  
Database visible and auto-login working.

---

## 7️⃣ Current state

✅ Symfony up & running  
✅ Database connected  
✅ phpMyAdmin working  
✅ Mailhog ready  
⏸️ Next step (after break): Step 8 — Create Entity “Item” and test API

---

## 🧱 Permanent “Forbidden” Fix (Final Docker Setup)

### Why
Originally, we used the base `php:8.2-apache` image directly. It worked but didn’t persist Apache configuration or permissions. After restarting containers, the “Forbidden” error came back.

### Permanent Fix Overview
We now build a **custom Docker image** for Symfony with a `Dockerfile` and a custom Apache config. This ensures correct document root, permissions, and PHP extensions every time.

### 1️⃣ Create Dockerfile
Create this in your project root (`lostfound/Dockerfile`):

```Dockerfile
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y     git unzip zip libicu-dev libzip-dev libpng-dev libonig-dev     && docker-php-ext-install pdo pdo_mysql intl zip gd

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy the Apache configuration
COPY ./symfony/vhost.conf /etc/apache2/sites-available/000-default.conf

# Set permissions so Apache can read/write
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
```

### 2️⃣ Add Apache Virtual Host Config
Create this file: `symfony/vhost.conf`

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Options Indexes FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### 3️⃣ Update docker-compose.yml
Replace your Symfony service with:

```yaml
  symfony:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: symfony_app
    volumes:
      - ./symfony:/var/www/html
    ports:
      - "8080:80"
    depends_on:
      - db
      - mailhog
```

If you prefer, you can still keep environment variables:

```yaml
    environment:
      DATABASE_URL: "mysql://symfony:symfony@db:3306/lostfound"
      MAILER_DSN: "smtp://mailhog:1025"
      MATCHER_API_URL: "http://matcher:8000/match"
```

### 4️⃣ Rebuild Everything
Run these commands:

```bash
docker-compose down --volumes --remove-orphans
docker-compose build --no-cache
docker-compose up -d
```

✅ Visit [http://localhost:8080](http://localhost:8080) — Symfony should load perfectly, even after restarting Docker.

---

### 🧠 Summary
| Before | After |
|---------|--------|
| Used default PHP image | Custom image with all configs |
| Manual Apache edits | Config baked into image |
| Lost changes on restart | Persistent, reproducible setup |
| Temporary permission fixes | Automatic during build |
