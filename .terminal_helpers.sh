
export DUID=$UID
export DGID=$(id -g)

# Container Run
#
# Executes a command in a specified Docker container.
# If no command is provided, it opens a bash shell in the container.
# Usage: cr [-r] [-u uid] <service_name> [command]
#
# Parameters:
#   -r: Optional flag to run as root (default is to run as the current user for the 'app' service)
#   -u uid: Optional user ID to run as (overrides default behavior)
#   <service_name>: Name of the Docker service
#   [command]: Optional command to run in the container
cr() {
  USER_FLAG=""
  CUSTOM_UID=""
  RUN_AS_ROOT=false
  
  # Parse options first
  while (( "$#" )); do
    case "$1" in
      -r)
        RUN_AS_ROOT=true
        USER_FLAG="--user root"
        shift
        ;;
      -u)
        if [ -n "$2" ] && [ ${2:0:1} != "-" ]; then
          CUSTOM_UID=$2
          USER_FLAG="--user $CUSTOM_UID"
          shift 2
        else
          echo "Error: Argument for $1 is missing" >&2
          return 1
        fi
        ;;
      -*) # unsupported flags
        echo "Error: Unsupported flag $1" >&2
        return 1
        ;;
      *) # preserve positional arguments
        break
        ;;
    esac
  done
  
  # Now $1 should be the service name
  SERVICE_NAME=$1
  if [ -z "$SERVICE_NAME" ]; then
    echo "Usage: cr [-r] [-u uid] <service_name> [command]"
    return 1
  fi
  shift
  
  # Set default user for app service if not specified
  if [ -z "$USER_FLAG" ] && [ "$SERVICE_NAME" = "app" ]; then
    USER_FLAG="--user $DUID"
  fi

  CONTAINER_ID=$(docker compose ps -q $SERVICE_NAME)

  if [ -n "$CONTAINER_ID" ]; then
    if [ $# -gt 0 ]; then
      # Pass all remaining arguments properly quoted
      docker compose exec $USER_FLAG -it $SERVICE_NAME "$@"
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
