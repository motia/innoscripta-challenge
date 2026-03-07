# HR Platform — Event-Driven Multi-Country Backend

## Table of Contents
1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Architecture](#architecture)
4. [Data Flow](#data-flow)
5. [Design Decisions](#design-decisions)
6. [Country Validation Strategy](#country-validation-strategy)
7. [Caching Strategy](#caching-strategy)
8. [WebSocket Channel Strategy](#websocket-channel-strategy)
9. [API Reference](#api-reference)
10. [Getting Started](#getting-started)
11. [Running Tests](#running-tests)
12. [Trade-offs & Future Improvements](#trade-offs--future-improvements)

---

## Overview

This platform consists of two Laravel microservices that form a real-time, event-driven HR system supporting multi-country employee management.

- **HR Service** — Manages employee data. Exposes a REST API for CRUD operations and publishes domain events to RabbitMQ whenever employee data changes.
- **HubService** — The central orchestration layer. Consumes events from RabbitMQ, maintains a cached read model, validates data completeness via a country-specific checklist engine, serves dynamic UI configuration APIs, and broadcasts real-time updates to frontend clients via WebSockets.

```
[HR Service] --events--> [RabbitMQ] --events--> [HubService]
                                                      |
                                        +-------------+-------------+
                                        |                           |
                               [WebSocket Clients]           [RESTful API]
```

---

## Technology Stack

| Layer | Technology | Reason |
|---|---|---|
| Backend Framework | Laravel 11 | Ecosystem maturity, built-in queues, broadcasting, caching |
| Database | PostgreSQL | Reliable relational store for HR Service employee data |
| Message Broker | RabbitMQ | Durable event delivery, routing via exchanges |
| Cache | Redis | Fast in-memory store; native Laravel cache driver support |
| WebSockets | Soketi | Self-hosted Pusher-compatible server; no external dependencies |
| Containerization | Docker Compose | One-command setup for all services |

---

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                         Docker Network                           │
│                                                                  │
│  ┌─────────────┐    events     ┌───────────┐    events          │
│  │  HR Service │ ────────────► │ RabbitMQ  │ ──────────►        │
│  │  (Laravel)  │               │           │            │        │
│  │  :8001      │               └───────────┘            │        │
│  └──────┬──────┘                                        ▼        │
│         │                                     ┌─────────────────┐│
│         │ PostgreSQL                          │   HubService    ││
│  ┌──────▼──────┐                              │   (Laravel)     ││
│  │  PostgreSQL │                              │   :8000         ││
│  │  :5432      │                              └────────┬────────┘│
│  └─────────────┘                                       │         │
│                                              ┌─────────┴───────┐ │
│                                              │                 │ │
│                                       ┌──────▼─────┐  ┌───────▼──┐
│                                       │   Redis    │  │  Soketi  │
│                                       │   :6379    │  │  :6001   │
│                                       └────────────┘  └──────────┘
└──────────────────────────────────────────────────────────────────┘
```

### Services

| Service | Port | Responsibility |
|---|---|---|
| HubService | 8000 | Main orchestration: REST API + WebSocket broadcaster + event consumer |
| HR Service | 8001 | Employee CRUD + RabbitMQ event publisher |
| PostgreSQL | 5432 | Employee persistence (HR Service only) |
| RabbitMQ | 5672 / 15672 | Message broker + management UI |
| Redis | 6379 | Cache store for HubService read model |
| Soketi | 6001 | Self-hosted WebSocket server |

---

## Data Flow

### Write Path (Employee Created/Updated/Deleted)

```
1. Client sends POST/PUT/DELETE to HR Service REST API
2. HR Service validates input (Form Request)
3. HR Service persists to PostgreSQL
4. HR Service publishes event to RabbitMQ exchange
   Payload: { event_type, event_id, timestamp, country, data: { employee } }
5. HubService consumer receives event
6. Event processor:
   a. Updates Redis cache (employee record)
   b. Invalidates checklist cache for that country
   c. Broadcasts update via Soketi to WebSocket subscribers
```

### Read Path (HubService APIs)

```
1. Client sends GET request to HubService
2. HubService checks Redis cache
   - Cache HIT  → return cached response immediately
   - Cache MISS → return 404 (data not yet received via events)
3. Response returned with appropriate cache headers
```

> **Note on HubService database:** HubService currently has no own database. Its read model is built entirely from RabbitMQ events stored in Redis. If the cache is cold (e.g. after a Redis flush), data will not be available until new events arrive. A future improvement would be to add a local read-replica database or a cache warm-up mechanism that fetches from HR Service on startup.

---

## Design Decisions

### 1. HubService Is Cache-Only (No Own Database)

HubService maintains state exclusively through Redis, populated by RabbitMQ events. This is a deliberate trade-off:

**Benefits:**
- No database migration complexity in HubService
- Forces clean event-driven design — HubService only knows what it has been told
- Very fast reads (pure Redis)

**Trade-off:**
- Cold cache returns 404 until events arrive
- No historical replay without re-consuming the queue

**Future improvement:** Add a `/warm-up` internal endpoint that fetches all employees from HR Service on HubService startup.

### 2. Country Validation — Strategy Pattern (not database-driven)

See the [Country Validation Strategy](#country-validation-strategy) section below for full detail.

### 3. RabbitMQ Topic Exchange with Country-Based Routing

Events are published to a topic exchange using routing keys in the format `employee.{country}.{event_type}`, e.g.:

```
employee.usa.created
employee.germany.updated
```

This allows future consumers to subscribe only to specific countries or event types without changing the publisher.

### 4. Soketi Over Pusher

Soketi is self-hosted and Pusher-protocol compatible. This means:
- No external API keys needed
- Entire system runs locally with `docker-compose up -d`
- Laravel Broadcasting works without modification

### 5. Redis as Cache (not Memcached)

Redis was chosen over Memcached because:
- Native Laravel cache driver support
- Supports key tagging (`Cache::tags([...])`) for grouped invalidation
- Can be extended for queue/session storage if needed

---

## Country Validation Strategy

Validation rules are encapsulated using the **Strategy Pattern**. Each country implements a shared interface:

```php
interface CountryValidationStrategy
{
    public function rules(): array;
    public function messages(): array;
    public function checklistRules(): array; // for HubService completeness checks
}
```

Concrete implementations:

```
app/
  Validation/
    Strategies/
      USAValidationStrategy.php
      GermanyValidationStrategy.php
    CountryValidationFactory.php
    CountryValidationStrategy.php  ← interface
```

### Adding a New Country

To add support for a new country (e.g. France), you only need to:

1. Create `FranceValidationStrategy.php` implementing `CountryValidationStrategy`
2. Register it in `CountryValidationFactory::make()`

No existing classes are modified. This follows the **Open/Closed Principle**.

### Two Distinct Validation Contexts

| Context | Where | Purpose |
|---|---|---|
| Input Validation | HR Service `FormRequest` | Prevent invalid data entering the system |
| Completeness Validation | HubService `ChecklistService` | Monitor data quality for existing records |

These rules intentionally live in separate codebases and may diverge. For example, HR Service may allow creating an employee without a `goal` field, while HubService marks that employee as incomplete in the checklist.

---

## Caching Strategy

### Cache Key Structure

```
employees:{country}:list          → paginated employee list
employees:{country}:{id}          → single employee record
checklists:{country}              → aggregated checklist for country
schema:{step_id}:{country}        → UI widget config
steps:{country}                   → navigation steps config
```

### Invalidation Rules

| Event | Invalidated Keys |
|---|---|
| `EmployeeCreated` | `employees:{country}:list`, `checklists:{country}` |
| `EmployeeUpdated` | `employees:{country}:{id}`, `employees:{country}:list`, `checklists:{country}` |
| `EmployeeDeleted` | `employees:{country}:{id}`, `employees:{country}:list`, `checklists:{country}` |

### TTL Strategy

| Cache Key | TTL | Reason |
|---|---|---|
| Employee list | 1 hour | Invalidated on events anyway |
| Single employee | 1 hour | Invalidated on events anyway |
| Checklist | 30 minutes | Recalculated on any employee event |
| Steps config | 24 hours | Static — only changes on deploy |
| Schema config | 24 hours | Static — only changes on deploy |

---

## WebSocket Channel Strategy

Channels follow a country-scoped naming convention:

```
public  country.{country}               → broadcast to all clients for a country
public  country.{country}.checklists    → checklist updates for a country
private employee.{country}.{id}         → updates for a specific employee
```

### Events Broadcast

| Laravel Event | Channel | Triggered By |
|---|---|---|
| `EmployeeUpdated` | `country.{country}` | Any employee change |
| `ChecklistUpdated` | `country.{country}.checklists` | Any employee change |
| `EmployeeDeleted` | `country.{country}` | Employee deletion |

---

## API Reference

### HubService (`:8000`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/checklists?country=USA` | Employee completeness checklist |
| GET | `/api/steps?country=USA` | Navigation steps for UI |
| GET | `/api/employees?country=USA` | Paginated employee list with column config |
| GET | `/api/schema/{step_id}?country=USA` | Widget config for a UI step |

### HR Service (`:8001`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/employees?country=USA` | List employees |
| POST | `/api/employees` | Create employee |
| GET | `/api/employees/{id}` | Get employee |
| PUT | `/api/employees/{id}` | Update employee |
| DELETE | `/api/employees/{id}` | Delete employee |

---

## Getting Started

### Prerequisites
- Docker + Docker Compose

### Start All Services

```bash
docker-compose up -d
```

### Services will be available at:

| Service | URL |
|---|---|
| HubService API | http://localhost:8000 |
| HR Service API | http://localhost:8001 |
| RabbitMQ Management | http://localhost:15672 (guest/guest) |
| Soketi | ws://localhost:6001 |

### Seed Test Data

```bash
# Create a USA employee
curl -X POST http://localhost:8001/api/employees \
  -H "Content-Type: application/json" \
  -d '{"name":"John","last_name":"Doe","salary":75000,"ssn":"123-45-6789","address":"123 Main St","country":"USA"}'

# Check checklist
curl http://localhost:8000/api/checklists?country=USA
```

### WebSocket Test Page

Open `public/websocket-test.html` in your browser to verify real-time updates end-to-end.

---

## Running Tests

```bash
# HR Service
docker-compose exec hr-service php artisan test

# HubService
docker-compose exec hub-service php artisan test

# With coverage
docker-compose exec hub-service php artisan test --coverage
```

### Test Coverage

| Layer | Type | What's Tested |
|---|---|---|
| `CountryValidationStrategy` | Unit | Rules, messages, completeness checks per country |
| `ChecklistService` | Unit | Percentage calculation, missing fields, edge cases |
| Event Processors | Unit | Cache updates, invalidation, broadcast triggering |
| RabbitMQ Consumer | Integration | Event → processor → cache → broadcast flow |
| API Endpoints | Feature | Response shape, pagination, 404 on cache miss |

---

## Trade-offs & Future Improvements

### Current Trade-offs

| Decision | Trade-off |
|---|---|
| Cache-only HubService | Simple architecture, but cold cache returns 404 |
| No authentication | Faster to build; private channels use app-level auth only |
| Basic DB indexes | Sufficient for challenge scale; production would add composite indexes |

### Production Improvements

- **Cache warm-up:** On HubService startup, fetch all employees from HR Service to pre-populate Redis
- **Event replay:** Store raw events in an event store (e.g. PostgreSQL) to rebuild cache on demand
- **Authentication:** Add Laravel Sanctum to both services; use private Soketi channels with user-scoped auth
- **Dead Letter Queue:** Route failed RabbitMQ messages to a DLQ for inspection and retry
- **Observability:** Add structured logging (e.g. with Monolog + ELK stack) and health check endpoints
- **Horizontal scaling:** HubService is stateless (Redis-backed), making it trivially horizontally scalable behind a load balancer
