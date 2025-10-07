# Laravel Docker

This document provides information in the structure, installation, updating and usage of Laravel Docker.

## Structure

Laravel Docker provides a main `docker-compose.yml` for using in production and a `docker-compose-dev.yml` for development.

The main `docker-compose.yml` has 4 different services:

 - `app`: php-apache image where the application is running 
 - `db`: mariadb image where is the database 
 - `redis`: redis image where is the redis cache
 - `adminer`: adminer image where is the database manager

The `docker-compose-dev.yml` has the same services as the main docker-compose.yml but with the addition of the `mailpit` image where is the test mail server. This composer file uses the `extends` directive to use the main `docker-compose.yml` file services.

Some services are configured by setting environment variables in the `.env` file in the root directory. Check the `.env.example` file for more information.

The `docker` directory contains dockerfiles and config files for each service. Those config files will be copied into the images on build or mounted to the container on run.

Finally, there are some helper scripts in the `.terminal_helpers.sh` that can be used to manage containers easily.

To use them you can run:

```bash
source .terminal_startup.sh
```

If you are using VSCode it will be sourced automatically on each terminal startup, this is confitgured in the `.vscode/settings.json` file.

## Installation

To install Laravel Docker you do the following:

Clone the repository:

```bash
git clone https://github.com/SrGnis/laravel_docker_template.git
```

Move into the repository and re initialize the git repository:

```bash
cd laravel_docker_template
rm -rf .git
git init .
```

Create a Laravel project with your prefered method:

```bash
# using the laravel installer
laravel new new_app

# rename the app to the src directory
mv new_app src
```

Copy the `.env.example` file to the `.env` file:

```bash
cp .env.example .env
```

Copy the `vite.config.js` file to the `src` directory:

```bash
cp docker/laravel/vite.config.js src/vite.config.js
```

Done, now you can run the containers in development mode:

```bash
source .terminal_startup.sh
dcdev up -d
```

Or in production mode:

```bash
docker compose up -d
```

## Updating

To update Laravel Docker you do the following:

Add the template repo as a remote:

```bash
git remote add template https://github.com/SrGnis/laravel_docker_template.git
```

Then run git fetch to update the changes

```bash
git fetch --all
```

Merge the changes.

```bash
git merge template/main --allow-unrelated-histories
```

## Services

Here is expanded information about each service that is used in this template.

### app

The `app` service is the main service where the application is running.

Is built using the Dockerfile or Dockerfile.dev in the `docker/laravel/` directory.

The Dockerfile uses the image `php:8.2-apache`. It installs the required dependencies and copies the application files to the container.

It also copies the config files and entrypoints from the `docker/laravel/` directory to the container. When you run the development compose file the files are mounted for no need to rebuild on changes just restart the container.

The .env file is mounted to the container for easy configuration.

The different config files are:

 - `docker/laravel/etc/apache2/sites-available/laravel.conf`: Apache config file.
 - `docker/laravel/usr/local/bin/docker-laravel-entrypoint`: Entry point script.
 - `docker/laravel/usr/etc/php/conf.d/php-overrides.ini`: PHP config file.
 - `docker/laravel/var/spool/cron/crontabs/root`: Cron config file.

When runnig the compose in development mode your `UID` is passed to the container as the `DUID` environment variable when you run the `dcdev` command from the helper scripts. This allows to set the user id of the `www-data` user to the current user id in the entrypoint and preventen file permission errors.

### db

TODO

### redis

TODO

### adminer

TODO

### mailpit

TODO
