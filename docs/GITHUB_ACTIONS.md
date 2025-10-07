# GitHub Actions Workflows

This document describes the GitHub Actions workflows used in the Hub01 Shop project.

## Overview

The project uses two main GitHub Actions workflows:

1. **Docker Image Build and Publish**: Builds and publishes the application Docker image to GitHub Container Registry when changes are pushed to the main branch.
2. **Run Tests**: Runs automated tests when pull requests are opened or updated to ensure code quality before merging.

## Docker Image Build and Publish

File: `.github/workflows/docker-publish.yml`

### Overview

This workflow automatically builds and publishes the application Docker image to GitHub Container Registry (GHCR) whenever changes are pushed to the main branch. It can also be triggered manually via the GitHub Actions interface.

### Workflow Details

#### Triggers

- **Push to main branch**: Automatically runs when commits are pushed to the main branch
- **Manual trigger**: Can be run manually using the "workflow_dispatch" event

#### Environment Variables

- `REGISTRY`: Set to `ghcr.io` to use GitHub Container Registry
- `IMAGE_NAME`: Automatically set to the repository name (e.g., `srgnis/hub01-shop`)

#### Jobs

The workflow consists of a single job named `build-and-push` that performs the following steps:

1. **Checkout repository**: Fetches the latest code from the repository
2. **Set up Docker Buildx**: Configures Docker Buildx for multi-platform builds
3. **Log in to Container registry**: Authenticates with GitHub Container Registry using the built-in `GITHUB_TOKEN`
4. **Extract metadata**: Generates appropriate tags and labels for the Docker image
5. **Build and push Docker image**: Builds the image using the Dockerfile and pushes it to GHCR

#### Image Tags

The workflow generates several tags for each image:

- `latest`: Always points to the most recent build from the main branch
- `sha-<commit>`: Tagged with the short SHA of the commit for precise version tracking
- Branch name: When building from a specific branch
- Semantic version tags: If the commit is tagged with a version (e.g., v1.0.0)

#### Caching

The workflow uses GitHub Actions cache to speed up Docker builds:

- `cache-from`: Pulls cached layers from previous builds
- `cache-to`: Stores built layers for future builds

### Usage

#### Accessing the Container Image

You can pull the container image from GitHub Container Registry:

```bash
docker pull ghcr.io/srgnis/hub01-shop:latest
```

To use a specific version:

```bash
docker pull ghcr.io/srgnis/hub01-shop:sha-<commit_hash>
```

#### Running the Container

To run the container:

```bash
docker run -p 80:80 ghcr.io/srgnis/hub01-shop:latest
```

#### Using in Docker Compose

You can use the published image in your Docker Compose file:

```yaml
services:
  app:
    image: ghcr.io/srgnis/hub01-shop:latest
    ports:
      - "80:80"
    # Add other configuration as needed
```

### Troubleshooting

If the workflow fails, check the following:

1. **Repository permissions**: Ensure the workflow has permission to write packages
2. **Dockerfile issues**: Verify that the Dockerfile builds successfully locally
3. **GitHub token**: The `GITHUB_TOKEN` should have the necessary permissions

### Customization

To customize the workflow:

1. Edit the `.github/workflows/docker-publish.yml` file
2. Modify the triggers, tags, or build arguments as needed
3. Commit and push your changes

## Run Tests

File: `.github/workflows/run-tests.yml`

### Overview

This workflow automatically runs tests when pull requests are opened or updated against the main branch. It helps ensure code quality and prevent regressions before merging changes.

### Workflow Details

#### Triggers

- **Pull requests to main branch**: Automatically runs when pull requests are opened, synchronized, or reopened against the main branch
- **Manual trigger**: Can be run manually using the "workflow_dispatch" event

#### Jobs

The workflow consists of a single job named `test` that performs the following steps:

1. **Checkout repository**: Fetches the latest code from the repository
2. **Set up Docker Buildx**: Configures Docker Buildx for building containers
3. **Start Docker Compose services**: Starts the required services (database and Redis)
4. **Build app container**: Builds the application container using docker-compose
5. **Run Laravel tests**: Executes the Laravel test suite using PHPUnit
6. **Run code style checks**: Verifies code style using Laravel Pint
7. **Stop Docker Compose services**: Ensures all services are stopped after tests complete

### Environment Configuration

The workflow sets up a testing environment with:

- SQLite in-memory database for fast testing
- Testing environment configuration

### Troubleshooting

If the tests fail, check the following:

1. **Test failures**: Review the specific test failures in the GitHub Actions logs
2. **Environment issues**: Ensure the testing environment is properly configured
3. **Code style issues**: Fix any code style violations reported by Laravel Pint

### Customization

To customize the test workflow:

1. Edit the `.github/workflows/run-tests.yml` file
2. Modify the test commands or add additional testing steps
3. Commit and push your changes
