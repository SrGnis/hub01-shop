# Contributing to Hub01 Shop

Thank you for your interest in contributing to **Hub01 Shop**

Hub01 Shop is a repository and management platform for Cataclysm Games projects, built with Laravel, Livewire, and Docker. Contributions of all kinds are welcome — bug fixes, features, documentation improvements, refactoring, and feedback.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Branching Strategy](#branching-strategy)
- [Code Style Guidelines](#code-style-guidelines)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)
- [Security Vulnerabilities](#security-vulnerabilities)

## Getting Started

1. Fork the repository.
2. Clone your fork:

```bash
git clone --recursive https://github.com/<your-username>/hub01-shop.git
cd hub01-shop
```

3. Add the upstream remote:

```bash
git remote add upstream https://github.com/srgnis/hub01-shop.git
```

## Development Setup

Hub01 Shop uses Docker for development.

### Quick Start (Recommended)

```bash
cp .env.example .env
./scripts/dcdev up -d
./scripts/cr app php artisan migrate:fresh --seed
```

Application will be available at:

```
http://localhost:8000
```

### Services Included

- `app` — Laravel (PHP-Apache)
- `db` — MariaDB
- `redis` — Redis cache
- `adminer` — Database management
- `mailpit` — Mail testing

You may also run it as a standard Laravel application without Docker if preferred.

## Branching Strategy

- `main` → Production-ready code
- `staging` → Pre-production testing
- Feature branches → `feature/<short-description>`
- Bugfix branches → `fix/<short-description>`
- Refactor branches → `refactor/<short-description>`

Example:

```
feature/project-rating-system
fix/version-download-count
```

The dvelopmet branches will be merged to staging branch. After testing, the staging branch will be merged to main branch and will be deployed to production.

Keep branches focused and small when possible.

## Code Style Guidelines

### General

- Write tests for new features and bug fixes.
- Try to follow Laravel and Livewire best practices. [Laravel](https://github.com/alexeymezenin/laravel-best-practices) [Livewire](https://github.com/michael-rubel/livewire-best-practices)
- Business logic belongs in `Services`.
- Keep Livewire components clean and focused.
- Avoid placing heavy logic directly inside components.
- Validate input data.
- Use validation classes when appropriate.

### Formatting

Try to use [Laravel Pint](https://laravel.com/docs/12.x/pint) for formatting.

```bash
./scripts/cr ./vendor/bin/pint
```

## Commit Guidelines

Use clear and descriptive commit messages.

Recommended format: [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)

```
type(scope): short description
```

Examples:

```
feat(project): add project dependency validation
fix(api): correct version endpoint response structure
refactor(service): simplify project version handling
docs(readme): update docker instructions
```

Common types:

- `feat`
- `fix`
- `refactor`
- `docs`
- `chore`
- `test`

## Pull Request Process

1. Ensure your branch is up to date with `staging`.
2. Ensure the application passes all tests.
3. Ensure migrations are included if database changes are made.
4. Submit a Pull Request to `staging`.
5. Clearly describe:
    - What was changed
    - Why it was changed
    - Any breaking changes
    - Screenshots (if UI-related)

Small, focused PRs are preferred over large ones.

## Reporting Issues

Before opening an issue:

- Search existing issues.
- Ensure you're using the latest version.
- Provide clear reproduction steps.
- Include logs if applicable.

Use the provided issue templates when creating new issues.

## Security Vulnerabilities

If you discover a security vulnerability:

- **Do NOT open a public issue.**
- Contact the maintainer privately.

Security issues will be handled with priority.

## Areas Where Contributions Are Welcome

- API improvements
- UI/UX improvements
- Performance optimizations
- Documentation updates
- Test coverage improvements
- Docker optimizations
- CI/CD enhancements

## Questions?

If you're unsure whether something should be implemented, feel free to open a [discussion](https://github.com/SrGnis/hub01-shop/discussions) or draft [PR](https://github.com/SrGnis/hub01-shop/pulls).
