# Trustindex

A small Symfony 8 application (built as a company coding test) for submitting and browsing company reviews.

Visitors can leave a star-rated review for a company, browse all reviews, view a single review, and see a list of companies with aggregated rating statistics (review count and average rating), with search by company name.

## Features

- **Submit a review** — company name, 1–5 star rating, review text, author email (validated).
- **Review list** — see all submitted reviews.
- **Review detail** — view a single review by ID.
- **Companies overview** — companies grouped with review count and average rating, sorted by average rating.
- **Company reviews** — all reviews for a single company.
- **Company search** — filter the companies list by name.

## Tech stack

- PHP >= 8.4
- Symfony 8.1
- Doctrine ORM / DBAL (MySQL)
- Doctrine Migrations
- Twig

## Project structure

```
src/
  Controller/   HTTP endpoints (Review, ReviewList, ReviewShow, Company)
  Entity/       Review entity
  Form/         ReviewType form
  Repository/   ReviewRepository (+ interface) — Doctrine queries and statistics
  Services/     ReviewService (+ interface) — application/business logic
  Twig/         StarRatingExtension — renders star ratings in templates
templates/      Twig views for each controller/action
migrations/     Doctrine database migrations
```

The Repository and Service layers are accessed through interfaces
(`IReviewRepository`, `IReviewService`), which are wired via
`config/services.yaml`.

## Setup

### Requirements

- PHP 8.4+
- Composer
- MySQL/MariaDB

### Installation

```bash
composer install
```

### Environment

Database connection is configured via the `DATABASE_*` variables in `.env`:

```
DATABASE_USER=root
DATABASE_PASSWORD=
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=trustindex
```

Copy `.env` to `.env.local` and adjust the values for your local database
instead of editing `.env` directly.

### Database

Create the database, then run the migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Run the app

Using the Symfony local web server:

```bash
symfony server:start
```

Or PHP's built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

## Routes

| Route                        | Path                              | Description                       |
| ----------------------------- | ---------------------------------- | ---------------------------------- |
| `app_review_list`             | `/`                                | List all reviews                   |
| `app_review`                  | `/review`                          | Submit a new review                |
| `task_success`                | `/review/success`                  | Review submitted confirmation      |
| `app_review_show`             | `/review/{id}/show`                | Show a single review               |
| `app_companies`               | `/companies`                       | List companies with statistics     |
| `app_companies_show`          | `/companies/{companyName}/show`    | Show reviews for a company         |
| `app_companies_search`        | `/companies/search/?q=`            | Search companies by name           |
