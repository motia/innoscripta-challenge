# Video Commands

Here's a ready-to-run sequence of commands (plus one browser step) that match each video beat:

## Architecture overview (quick recap command)
```bash
sed -n '19,116p' README.md
```

(Shows the diagram + service descriptions from the README for narration.)

This platform is built from two Laravel services that keep employee data synchronized in real time. The HR Service on port 8001 owns the PostgreSQL database and exposes CRUD APIs. Any change it stores is published as an event into RabbitMQ. HubService, running on port 8000, consumes those events, materializes a read model in Redis, and broadcasts updates to WebSocket clients through Soketi. You can think of the flow as HR Service → RabbitMQ → HubService → REST and WebSocket consumers, all wired together inside the same Docker network, so docker-compose up brings the full stack online in one shot.

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

## Start everything / show health
```bash
docker compose up -d
docker compose ps
```

## Checklist API (initial state)
```bash
curl -s http://localhost:8000/api/checklists?country=USA | jq
```

## Update employee via HR Service
```bash
curl -s -X PUT http://localhost:8001/api/employees/18 \
  -H "Content-Type: application/json" \
  -d '{"name":"John","last_name":"Doe","salary":88000,"ssn":"123-45-6789","address":"123 Main St","country":"USA"}' | jq
```

## RabbitMQ UI (manual)
Open `http://localhost:15672` (guest/guest) → Exchanges → `employee_events` to show routed message.

## HubService logs (event processing)
```bash
docker compose logs -f hub-service
```
(Start this before issuing the PUT so the log shows the event.)

## Re-run checklist
```bash
curl -s http://localhost:8000/api/checklists?country=USA | jq
```

## WebSocket demo
In a browser: `http://localhost:8000/websocket-test.html`

(The page subscribes to country.USA and country.USA.checklists; keep console open to show payloads.)

## Tests (both services)
```bash
docker compose exec hr-service php artisan test
docker compose exec hub-service php artisan test
```
