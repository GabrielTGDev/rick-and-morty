# Rick and Morty API

[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-2-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)

REST API for synchronizing and querying Rick and Morty characters, locations, and episodes. It includes character filtering, custom Bearer token authentication, and favorite management.

## Prerequisites

- Docker Desktop o Docker Engine.
- Docker Compose.

## Installation

```bash
git clone https://github.com/GabrielTGDev/rick-and-morty.git
cd rick-and-morty
cp .env.example .env
```

> Update the `.env` information (database settings and `WWWUSER` and `WWWGROUP` IDs).

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan app:sync-rick-and-morty
./vendor/bin/sail artisan test
```

The synchronization downloads data from the public Rick and Morty API and persists it locally. Once started, the API is available at `http://localhost:8000`.

## Architectural Decisions

- **PostgreSQL and JSONB:** normalized data is stored in relational tables, while the original external API response is retained in `JSONB`. This supports auditing and mapping evolution without losing information.
- **Decoupled HTTP client and DTOs:** the external client contract, HTTP implementation, and DTOs separate network communication from persistence. This improves type safety, testing with `Http::fake()`, and provider substitution.
- **Custom token authentication:** a Bearer token stored on the user and custom middleware meet the authentication requirement without adding external authentication dependencies.
- **Idempotency and fault tolerance:** `updateOrCreate()` and relationship synchronization prevent duplicates on repeated executions. The client applies timeouts and retries, while each page is processed in a transaction to preserve previously synchronized data if a partial failure occurs.

## Sequence Diagrams

<details>
<summary>Synchronization Flow</summary>

```mermaid
sequenceDiagram
	participant Operator
	participant Command as Artisan Command
	participant Sync as DataSyncService
	participant API as Rick and Morty API
	participant DB as PostgreSQL

	Operator->>Command: app:sync-rick-and-morty
	Command->>Sync: sync locations
	loop Each page
		Sync->>API: GET locations page
		API-->>Sync: DTOs and pagination metadata
		Sync->>DB: transaction and updateOrCreate locations
	end
	Command->>Sync: sync episodes
	loop Each page
		Sync->>API: GET episodes page
		API-->>Sync: DTOs and pagination metadata
		Sync->>DB: transaction and updateOrCreate episodes
	end
	Command->>Sync: sync characters
	loop Each page
		Sync->>API: GET characters page
		API-->>Sync: DTOs and pagination metadata
		Sync->>DB: transaction, upsert characters, sync episodes
	end
	Sync-->>Command: processed counts
	Command-->>Operator: synchronization completed
```
</details>

<details>
<summary><code>POST /api/register</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>API: POST register with name, email, password
	API->>API: validate request and hash password
	API->>DB: create user with generated API token
	DB-->>API: user created
	API-->>Client: 201 Created with token
```
</details>

<details>
<summary><code>POST /api/login</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>API: POST login with email and password
	API->>API: validate request
	API->>DB: find user by email
	DB-->>API: user or no result
	alt Valid credentials
		API->>API: verify password and generate token
		API->>DB: store new API token
		API-->>Client: 200 OK with token
	else Invalid credentials
		API-->>Client: 401 Unauthorized
	end
```
</details>

<details>
<summary><code>POST /api/logout</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant Middleware as Token Middleware
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>Middleware: POST logout with Bearer token
	Middleware->>DB: find user by API token
	alt Valid token
		DB-->>Middleware: authenticated user
		Middleware->>API: set request user
		API->>DB: clear API token
		API-->>Client: 200 OK
	else Missing or invalid token
		Middleware-->>Client: 401 Unauthorized
	end
```
</details>

<details>
<summary><code>GET /api/characters</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>API: GET characters with optional filters and page
	API->>DB: query characters with locations and pagination
	DB-->>API: paginated character collection
	API-->>Client: 200 OK with standard success response
```
</details>

<details>
<summary><code>GET /api/characters/{id}</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>API: GET character by ID
	API->>DB: find character with locations and episodes
	alt Character found
		DB-->>API: character and relationships
		API-->>Client: 200 OK with standard success response
	else Character not found
		API-->>Client: 404 Not Found
	end
```
</details>

<details>
<summary><code>GET /api/user/favorites</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant Middleware as Token Middleware
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>Middleware: GET favorites with Bearer token
	Middleware->>DB: find user by API token
	alt Valid token
		DB-->>Middleware: authenticated user
		Middleware->>API: set request user
		API->>DB: paginate favorite characters
		DB-->>API: paginated favorites
		API-->>Client: 200 OK with standard success response
	else Missing or invalid token
		Middleware-->>Client: 401 Unauthorized
	end
```
</details>

<details>
<summary><code>POST /api/characters/{id}/favorite</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant Middleware as Token Middleware
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>Middleware: POST favorite with Bearer token
	Middleware->>DB: find user by API token
	alt Valid token
		DB-->>Middleware: authenticated user
		Middleware->>API: set request user
		API->>DB: syncWithoutDetaching favorite
		DB-->>API: favorite relation persisted
		API-->>Client: 201 Created
	else Missing or invalid token
		Middleware-->>Client: 401 Unauthorized
	end
```
</details>

<details>
<summary><code>DELETE /api/characters/{id}/favorite</code></summary>

```mermaid
sequenceDiagram
	participant Client
	participant Middleware as Token Middleware
	participant API as Laravel API
	participant DB as PostgreSQL

	Client->>Middleware: DELETE favorite with Bearer token
	Middleware->>DB: find user by API token
	alt Valid token
		DB-->>Middleware: authenticated user
		Middleware->>API: set request user
		API->>DB: detach favorite relation
		DB-->>API: favorite relation removed
		API-->>Client: 200 OK
	else Missing or invalid token
		Middleware-->>Client: 401 Unauthorized
	end
```
</details>

## OpenAPI Documentation

The endpoint, schema, filter, and Bearer security specification is available in [openapi.yaml](openapi.yaml).

## Developer

Created by Gabriel Trujillo.

## License

This project is licensed under the [MIT License](https://opensource.org/license/mit).
