# GitHub Actions Workflows

This document describes the GitHub Actions workflows used in the Hub01 Shop project.

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
