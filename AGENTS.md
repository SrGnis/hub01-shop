# Hub01 Shop — Agent Development Guide

Brief: Hub01 Shop is a centralized platform for Cataclysm community content (mods/assets) with strong versioning, dependency tracking, moderation workflows, and quota enforcement.

## Project Context

- Domain: user-generated game content distribution with structured projects and semantic version releases.
- Current focus: project workflows, dependency handling, search/filter UX, and test coverage expansion.
- Key active decisions:
    - Projects start as drafts and require manual submission/review.
    - Dependency tracking is a core requirement.
    - API dependency payload uses `external` + `project` + `version` fields.
    - MaryUI (DaisyUI-based) is the standard UI layer.

## Architecture

- Stack architecture: Laravel MVC + Livewire for full-stack interactivity.
- Follow these layers strictly:
    - Frontend:
        - Livewire components and API controllers.
        - Handles interaction, validation, authentication, authorization.
        - May use models directly only for trivial reads/writes.
        - Delegate business rules to services.
    - Services:
        - Business logic, API orchestration, data processing, domain validation.
        - Must be stateless and context-agnostic.
        - Must not depend on request/session/current user.
        - Use models for persistence concerns.
    - Models:
        - Data representation, relationships, scopes, and database interaction.

## Core Domain Model (High-Level)

- Project:
    - Owns slug/status/approval state and general metadata.
- ProjectVersion:
    - Represents a release with files/changelog/dependencies.
- ProjectVersionDependency:
    - Encodes links to internal/external dependencies.
- Membership/Teams:
    - Enables collaborative project maintenance.
- Quotas:
    - Enforced at system, project-type, and per-entity override levels.

## Technology Standards

- Backend: PHP 8.2+, Laravel 12.x.
- Frontend: Livewire 3 + AlpineJS + TailwindCSS.
- UI components: MaryUI (DaisyUI-based).
- Auth/API:
    - Fortify for auth flows.
    - Sanctum for API token auth.
- Testing: PHPUnit.

## Code Style & Implementation Rules

- Keep controllers/Livewire thin; move reusable business logic into services.
- Prefer Eloquent scopes for reusable visibility/filtering rules.
- Use enums/DTO-like structures for explicit domain values and safer contracts.
- Keep validation close to entry points (Frontend/API), and enforce critical invariants again in services.
- Follow existing naming and folder conventions in `src/app`.

## Security & Access

- Enforce authorization for project/version operations.
- Never trust client payloads for quota/approval-sensitive actions.
- Validate and sanitize dependency-related inputs consistently.
- Use Sanctum token lifecycle via service-based flows (create/revoke/renew patterns).

## Testing Guidelines

- Add/maintain:
    - Feature tests for end-to-end user flows (search/show/forms/api).
    - Unit tests for service-layer logic and critical components.
- Prioritize coverage for:
    - Dependency management edge cases.
    - Visibility/approval rules.
    - Quota enforcement paths.
    - Tag/filter behavior.

## Development Environment & Commands

- Development is containerized.
- Run in-container commands through `scripts/cr`.
- Example:

```bash
./scripts/cr app php artisan make:model User
```

- Typical tooling:
    - Composer/NPM and artisan commands should be executed via `scripts/cr`.

## UI Conventions

- Use MaryUI components as the default UI building blocks.
- DaisyUI/Tailwind utility usage should align with existing project patterns.
- For component references, inspect:
    - `src/lib/mary/src/View/Components`

## Practical Patterns

- Preferred flow for complex operations:
    1. Validate input at boundary (Livewire/API).
    2. Authorize action.
    3. Call service method for domain workflow.
    4. Persist via models.
    5. Return transformed/resource output for API/UI.

- Example dependency payload direction:

```json
{
    "external": false,
    "project": "core-mod",
    "version": "1.2.0"
}
```

## Documentation Hygiene

- Keep this file concise, specific, and action-oriented.
- Update this guide when architecture decisions or workflow conventions change.
