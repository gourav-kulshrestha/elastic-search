# Elastic Search

A scalable Products Search built with Laravel, MySQL, Redis, and Elasticsearch. The project provides high-performance product search, filtering, caching, and asynchronous indexing.

## Tech Stack

- Laravel 13
- PHP 8.5
- MySQL 8
- Redis
- Elasticsearch 9.x
- Vite
- Docker (Optional)
- PHPUnit

---

## Features

- Elasticsearch-powered Full Text Search
- Category & Brand Filtering
- Price Range Filtering
- Redis Caching
- Queue-based Background Indexing
- Bulk Product Indexing
- RESTful APIs
- Pagination
- Clean Architecture
- Service Layer
- SOLID Principles

---

## Requirements

- PHP 8.4+
- Composer
- MySQL 8+
- Redis
- Elasticsearch 9.x
- Node.js 20+

---

## Installation

### Clone Repository

```bash
git clone https://github.com/gourav-kulshrestha/elastic-search.git

cd elastic-search
```

### Install Dependencies

```bash
composer install

npm install
```

### Environment Setup

```bash
cp .env.example .env

php artisan key:generate
```

Update your `.env` file with your database, Redis, and Elasticsearch credentials.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elastic_search
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

ELASTICSEARCH_HOSTS=http://127.0.0.1:9200
ELASTICSEARCH_USERNAME=
ELASTICSEARCH_PASSWORD=
ELASTICSEARCH_SSL_VERIFICATION=false
```

---

## Database

```bash
php artisan migrate

php artisan db:seed
```

---

## Elasticsearch

Create/Rebuild Product Index

```bash
php artisan products:reindex
```

---

## Queue Worker

```bash
php artisan queue:work
```

---

## Start Development Server

Backend

```bash
php artisan serve
```

Frontend

```bash
npm run dev
```

---OR---

```bash
npm run build
```

```bash
php artisan optimize

php artisan config:cache

php artisan route:cache
```

---

## Architecture

```
Client
   │
   ▼
Laravel API
   │
   ├── MySQL
   ├── Redis
   └── Elasticsearch
```

---

## License

This project is developed for learning and demonstration purposes.

---

## Author

**Gourav Kulshrestha**


Laravel | PHP | MySQL | Redis | Elasticsearch | REST APIs
