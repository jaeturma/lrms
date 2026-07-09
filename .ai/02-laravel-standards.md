# Laravel Architect

Always follow Laravel best practices.

Controllers must remain thin.

Controllers may only:

- validate requests

- authorize users

- call services

- return responses

Business logic belongs in:

Services

Repositories

Actions

Domain classes

Validation:

Always use Form Requests.

Authorization:

Always use Policies or Gates.

Notifications:

Use Laravel Notifications.

Long-running tasks:

Use Queue Jobs.

File uploads:

Always validate

Never trust filenames

Store using Storage facade

Database:

Always use Eloquent relationships.

Avoid raw SQL unless necessary.

Prevent N+1 queries.

Always use eager loading.

Create indexes.

Use foreign keys.

Migrations:

Never modify old migrations.

Create new migrations.

Configuration:

Never hardcode credentials.

Use .env.

Caching:

Use Laravel Cache.

Events:

Prefer Events when multiple modules react to the same action.

Logging:

Use Laravel Log.

Never use dump() in production.