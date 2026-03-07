# Senior Backend Engineer - Coding Challenge

## Event-Driven Multi-Country Platform

## Overview

### Challenge Objective

Build a real-time, event-driven backend platform that demonstrates your expertise in:

- Event-driven architecture using RabbitMQ
- Real-time WebSocket communications
- Server-driven UI patterns
- Multi-country data handling
- Intelligent caching strategies
- Laravel best practices

### What Makes This Challenge Unique
Unlike traditional CRUD applications, this challenge tests your ability to:

- Design systems that react to events in real-time
- Handle country-specific business logic elegantly
- Optimize performance through intelligent caching
- Build maintainable, testable code at scale

## What You'll Build
### The Big Picture
You'll create a HubService that acts as the central orchestration layer for a multicountry HR platform. This service:

1. Receives events from a microservice (HR Service) via RabbitMQ
2. Processes and validates data based on country-specific rules
3. Caches intelligently to optimize performance
4. Broadcasts updates to frontend clients in real-time via WebSockets
5. Serves dynamic APIs that configure the UI based on user country

### Visual Flow
```
[HR Service] --events--> [RabbitMQ] --events--> [HubService]
                                                      |
                                        +-------------+-------------+
                                        |                           |
                               [WebSocket Clients]           [RESTful API]
```

## System Architecture
### Components Overview
1. HR Service (Microservice - You Build This)

What it does:
- Manages employee data
- Publishes events when data changes
- Has country-specific employee fields

Your responsibility:
- Create REST endpoints for employee CRUD operations
- Implement event publishing to RabbitMQ when data changes
- Design and implement the database schema
- Handle country-specific employee fields properly

Employee Data Structure by Country:

USA Employees:
```json
{
"id": 1,
"name": "John",
"last_name": "Doe",
"salary": 75000,
"ssn": "123-45-6789",
"address": "123 Main St, New York, NY",
"country": "USA"
}
```

Germany Employees:
```json
{
"id": 2,
"name": "Hans",
"last_name": "Mueller",
"salary": 65000,
"goal": "Increase team productivity by 20%",
"tax_id": "DE123456789",
"country": "Germany"
}
```

Events Published:
- EmployeeCreated
- EmployeeUpdated
- EmployeeDeleted

Event Payload Example:
```json
{
"event_type": "EmployeeUpdated",
"event_id": "uuid-here",
"timestamp": "2024-02-09T10:30:00Z",
"country": "USA",
"data": {
"employee_id": 1,

"changed_fields": ["salary"],
"employee": {
"id": 1,
"name": "John",
"last_name": "Doe",
"salary": 80000,
"ssn": "123-45-6789",
"address": "123 Main St, New York, NY",
"country": "USA"
}
}
}
```

2. HubService (Main Challenge - You Build This)
What it does:
- Consumes events from RabbitMQ
- Maintains cached state of employee data
- Validates data completeness (Checklist System)
- Serves country-specific UI configuration APIs
- Broadcasts real-time updates to clients

Key Responsibilities:
1. Event Processing Pipeline
2. Intelligent Caching Layer
3. Checklist Validation Engine
4. Server-Driven UI APIs
5. Real-Time Broadcast System

## Feature Requirements
### Feature 1: Employee Checklist System
What is the Checklist System?

A data validation engine that continuously monitors employee data completeness and tells
users what information is missing or complete based on country-specific requirements.

Think of it like a progress tracker that shows:

- ✅ What's complete (employee has SSN, salary filled out)
- ❌ What's missing (employee needs address, tax ID)
- 📊 Overall completion percentage

Country-Specific Validation Rules

USA Requirements:
- ssn (required)
- salary (required, must be > 0)
- address (required, non-empty)

Germany Requirements:
- salary (required, must be > 0)
- goal (required, non-empty)
- tax_id (required, format: DE + 9 digits)

#### API Endpoint: GET /api/checklists
Purpose: Return comprehensive checklist data for all employees
Query Parameters:
country (required): Filter by country code (USA, Germany)

Your response should include:
- Overall completion statistics
- Per-employee checklist status
- What fields are complete vs incomplete
- Actionable messages for missing data

#### Caching Strategy Requirements
Why Cache?
The checklist endpoint aggregates data from the HR service and performs validation
calculations. Without caching, every request would:
1. Fetch all employees from HR service
2. Run validation rules for each employee
3. Calculate percentages and statistics

##### Your Task:
Design and implement an effective caching strategy that:
- Caches expensive checklist calculations
- Invalidates cache appropriately when events arrive

##### Consider:
- What should be your cache key structure?
- When should cache be invalidated?
- Which specific cache entries need to be cleared when an employee is created, updated, or deleted?

##### Real-Time Updates
When employee data changes, users should see checklist updates immediately without
refreshing.
Your Task:

- Design appropriate WebSocket channel naming for checklist updates
- Broadcast updates after processing events and updating cache
- Consider both country-level and employee-level update channels

### Feature 2: Server-Driven UI APIs
#### Concept
Instead of hardcoding UI structure in the frontend, the backend controls what the
frontend displays based on the user's country. This allows you to change UI layouts
without deploying frontend code.

#### API 1: Steps Configuration
- Endpoint: GET /api/steps
- Purpose: Return the navigation structure/steps that should be displayed in the UI
- Query Parameters: country (required)
  - User's country code
- Requirements:
  - USA should return: Dashboard, Employees
  - Germany should return: Dashboard, Employees, Documentation
  - Include appropriate metadata (labels, icons, ordering, paths)

#### API 2: Employee List with Real-Time Updates
- Endpoint: GET /api/employees
- Purpose: Return employee data with country-specific columns
- Query Parameters:
  - country (required): Filter by country code
  - page (optional): Pagination
  - per_page (optional): Items per page
- Requirements:
  - USA employees should show: Name, Last Name, Salary, SSN (masked)
  - Germany employees should show: Name, Last Name, Salary, Goal
  - Include column definitions so frontend knows how to render them
  - Support pagination
  - Cache appropriately and invalidate when employee events arrive

#### API 3: Schema Configuration
- Endpoint: GET /api/schema/{step_id}
- Purpose: Return dynamic widget configuration for a specific step
- Query Parameters:
  - country (required): User's country code
- Requirements:
  - Dashboard page should return different widgets based on country
  - USA dashboard: Employee count, Average salary, Completion rate widgets
  - Germany dashboard: Employee count, Goal tracking widgets
  - Widgets should specify their data source and real-time update channels
  - Design the widget configuration structure to be frontend-agnostic

## Technical Requirements
### 1. Event-Driven Architecture with RabbitMQ

#### RabbitMQ Setup Requirements

##### Your Task:
- Include RabbitMQ in your Docker Compose setup
- Ensure RabbitMQ is accessible from both HR Service and HubService

#### Event Publishing (HR Service)

##### Your Task:

Design and implement event publishing when employee data changes in the HR Service.

##### Consider:
- What information should be included in the event payload?
- How do you structure the event to include all necessary data?
- What routing strategy makes sense for country-specific events?
- How do you handle publishing failures?

#### Event Consumption (HubService)
##### Your Task:
Implement a listener/consumer in the HubService that processes events from RabbitMQ.

##### Requirements:

- Consume from the appropriate queue
- Route different event types to appropriate handlers
- Implement proper error handling and retry logic

### 2. Event Listeners and Processors
#### Event Processor Architecture

##### Your Task:
Design and implement event handlers that process incoming events from RabbitMQ.

Each event processor should:
1. Extract relevant data from the event
12. Update cached data appropriately
3. Invalidate related cache entries
4. Broadcast real-time updates to connected clients
5. Log processing for debugging and monitoring

What We're Looking For:
- ✅ Clean separation of concerns

- ✅ Proper error handling with try-catch

- ✅ Logging for debugging

- ✅ Transaction safety where needed

- ✅ Efficient cache operations

### 3. Real-Time WebSocket Implementation
#### Technology Choice

- Option 1: Pusher (Recommended for Simplicity)
  - Free tier available
  - No additional Docker setup
  - Laravel native integration
- Option 2: Soketi (Recommended for Full Control)
  - Self-hosted Pusher alternative
  - Include in Docker Compose
  - No external dependencies

##### Your Task:
Choose one of the above options and set it up in your Docker Compose configuration.

#### Channel Strategy
##### Your Task:

Design an effective channel naming strategy for your WebSocket broadcasts.
Consider:

- How will you organize channels by country?
- Should you have both public and private channels?
- How will you handle employee-specific updates?
- What authorization is needed for private channels?

#### Broadcasting Events

##### Your Task:
Implement event broadcasting to push updates to frontend clients in real-time.

Requirements:
- Broadcast to appropriate channels after processing RabbitMQ events
- Include relevant data in broadcasts (what changed, new values, etc.)
- Consider what data the frontend needs to update its UI
- Implement proper authorization for private channels

#### Frontend Integration Testing
##### Your Task:
Create a simple HTML page to test that WebSocket updates are working.
The test page should:
- Connect to your WebSocket server
- Subscribe to relevant channels (e.g., employee updates, checklist updates)
- Display received events in the console or on the page
- Demonstrate that real-time updates work end-to-end
This doesn't need to be a polished UI—just enough to prove the WebSocket flow works.

### 4. Caching Strategy
#### Technology Options
##### Your Task:
- Choose one caching technology and justify your choice in documentation
- Set it up in your Docker Compose configuration
- Implement caching throughout the HubService where appropriate

Cache Implementation Guidelines
Your Task:
Implement caching throughout the HubService for optimal performance.
Key Areas to Cache:
- Checklist data (expensive to calculate)
- Employee lists (fetched from HR Service)
Implementation Considerations:
- How will you store and retrieve cached data?
- What TTL (time-to-live) makes sense for different types of data?
- How will you implement cache-aside pattern (check cache first, fetch from source if missing)?
- How will you handle cache misses efficiently?

### 5. Docker Setup Requirements

#### Docker Compose Requirements

##### Your Task:
Create a docker-compose.yml file that orchestrates all services.

##### Required Services:
1. HubService (Laravel application - main challenge)
2. HR Service (Laravel application - microservice)
3. PostgreSQL (database)
4. RabbitMQ (message queue with management UI)
5. Your chosen caching solution (Redis, Memcached, etc.)
6. WebSocket server (Soketi, Pusher, or Laravel WebSockets)

Requirements:
- All services must be able to communicate with each other
- Use environment variables for configuration

One-Command Setup Requirement
The system must start with a single command:
`docker-compose up -d`

### Code Quality & Architecture Requirements
1. Clean Architecture Principles
What We're Looking For:
- ✅ Separation of concerns

- Dependency injection

-  Interface-based design

- Single Responsibility Principle

- ✅ Clear naming conventions

2. Testing Requirements
Minimum Test Coverage:
- Unit Tests: Core business logic (checklist validation, event handlers)
- Integration Tests: Event flow from RabbitMQ to cache to broadcast
- Feature Tests: API endpoints return correct responses

3. Laravel Best Practices
Required:
- ✅ Use Laravel's built-in features (Eloquent, Cache, Events)

- ✅ Implement Form Requests for validation

- ✅ Use Resource classes for API responses

- ✅ Environment-based configuration (.env)

- ✅ Proper error handling with try-catch

- ✅ Logging for debugging

What We're Looking For:
- Clean, readable code with meaningful variable names
- Proper use of dependency injection
- Single Responsibility Principle applied
- Code that is easy to test and maintain
- Consistent code style throughout

Deliverables
1. Code Repository
Platform: GitHub (public or private)
2. Documentation (README.md)
Your README must include:
Section 1: Overview

- Brief description of the system
- Technology stack used
- Design decisions and trade-offs

Section 2: Architecture
- System architecture diagram
- Data flow explanation

3. Video Demo (Optional but Highly Recommended)

What to Show:
1. Quick overview of architecture
2. Start system with docker-compose up
3. Make API request to /api/checklists
4. Update employee data via HR Service API
5. Show event in RabbitMQ Management UI
6. Show logs confirming event processing
7. Show updated checklist data
8. Show WebSocket update in browser console
9. Quick look at test results

## FAQ


Q: Can I use Pusher instead of self-hosted WebSockets?
A: Yes! Pusher's free tier is ne.
Q: Do I need to build a frontend?
A: No! A simple HTML page with JavaScript to test WebSockets is enough. We're evaluating
backend skills.
Q: Can I use packages/libraries?
A: Yes! Use Laravel ecosystem packages. Just document why you chose them.
Q: How do I handle multiple countries?
A: Start with the USA and Germany as speci ed. Design the system so that adding support for
additional countries is straightforward. The code should be clean, well-structured, and
extensible, allowing new country-speci c logic to be introduced with minimal changes.
Q: Should I implement authentication?
A: No need for this challenge. Focus on the core event-driven architecture.
Q: What about database optimization?
A: Basic indexes are ne. Document any optimization strategies you'd implement in
production.


## Final Thoughts

This challenge is designed to simulate real-world system design problems you'll face in this
role. We're not looking for perfection—we want to see:
- How you think about complex problems
- How you make architectural decisions
- How you handle trade-offs
- How you communicate technical concepts
- How you write production-quality code
Take your time, ask questions, and show us your best work. We're excited to see what you build!

Good luck! 🚀


