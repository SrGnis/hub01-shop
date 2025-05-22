# Hub01 Shop

A Cataclysm: Dark Days Ahead mod repository and management platform.

## About Hub01 Shop

Hub01 Shop is a web application designed to serve as a centralized repository for Cataclysm: Dark Days Ahead (CDDA) mods. It provides a platform for mod creators to share their work and for players to discover, download, and manage mods for their CDDA game.

### Key Features

- **Mod Repository**: Browse, search, and download mods for Cataclysm: Dark Days Ahead
- **Version Management**: Track different versions of mods with changelog support
- **User Accounts**: Register, login, and manage your profile
- **Project Management**: Create and manage your mod projects
- **Dependency Tracking**: Manage mod dependencies to ensure compatibility
- **Tagging System**: Organize mods with tags for better discoverability
- **Admin Panel**: Comprehensive admin tools for site management

## Installation

### Prerequisites

- Docker and Docker Compose
- Git

### Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/srgnis/hub01-shop.git
   cd hub01-shop
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Start the Docker containers:
   ```bash
   source .terminal_startup.sh # loaded automatically in VSCode
   dcdev up -d
   ```

4. Run migrations and seed the database:
   ```bash
   cr php artisan migrate --seed
   ```

6. Access the application at http://localhost:8000

## Development

The application is built with Laravel and uses Docker for development. Helper scripts are available in `.terminal_helpers.sh` to simplify container management.

### Docker Services

- **app**: PHP-Apache service running the Laravel application
- **db**: MariaDB database
- **redis**: Redis cache server
- **adminer**: Database management tool
- **mailpit**: Development mail server (dev environment only)

### Useful Commands

- Start development environment: `dcdev up -d`
- Run artisan commands: `cr php artisan <command>`
- Run tests: `cr php artisan test`
- Access database: Visit http://localhost:8080 (Adminer)

## Project Structure

The application follows a standard Laravel structure with additional components:

- **Models**: Project, ProjectType, ProjectVersion, ProjectFile, etc.
- **Livewire Components**: For reactive UI components
- **Admin Panel**: Comprehensive admin tools
- **Caching**: Redis-based caching system

## Documentation

Additional documentation can be found in the `docs/` directory:

- [Laravel Docker](docs/LARAVEL_DOCKER.md): Docker setup information
- [Cache Documentation](docs/CACHE.md): Caching strategy details
- [Database Schema](docs/src/uml/hub01_shop.er.md): Entity relationship diagrams

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).