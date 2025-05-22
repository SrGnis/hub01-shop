
export DUID=$UID
export DGID=$(id -g)

# Container Run
#
# Executes a command in a specified Docker container.
# If no command is provided, it opens a bash shell in the container.
# Usage: cr [-r] <service_name> [command]
#
# Parameters:
#   -r: Optional flag to run as root (default is to run as the current user for the 'app' service)
#   <service_name>: Name of the Docker service
#   [command]: Optional command to run in the container
cr() {
  SERVICE_NAME=$1
  COMMAND=${@:2}
  USER_FLAG=""

  if [ -z "$SERVICE_NAME" ]; then
    echo "Usage: cr [-r] <service_name> [command]"
    return 1
  fi

  if [ "$SERVICE_NAME" = "-r" ]; then
    SERVICE_NAME=$2
    COMMAND=${@:3}
  elif [ "$SERVICE_NAME" = "app" ]; then
    USER_FLAG="-u $DUID"
  fi

  CONTAINER_ID=$(docker compose ps -q $SERVICE_NAME)

  if [ -n "$CONTAINER_ID" ]; then
    if [ -n "$COMMAND" ]; then
      docker compose exec $USER_FLAG -it $SERVICE_NAME $COMMAND
    else
      docker compose exec $USER_FLAG -it $SERVICE_NAME bash
    fi
  else
    echo "Container not found for service $SERVICE_NAME"
  fi
}

# Launch Development Composer
#
# Runs docker compose with the development compose file and the given commands as arguments, also sets the DUID environment variable to the current user id.
dcdev() {
  echo "Running docker compose with DUID=$DUID and DGID=$DGID"
  DUID=$DUID DGID=$DGID docker compose -f docker-compose-dev.yml ${@:1}
}
