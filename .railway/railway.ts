import {
  defineRailway,
  github,
  mysql,
  preserve,
  project,
  service,
} from "railway/iac";

export default defineRailway(() => {
  const database = mysql("mysql", {
    region: "europe-west4",
  });

  const databaseEnvironment = {
    DB_HOST: database.env.MYSQLHOST,
    DB_PORT: database.env.MYSQLPORT,
    DB_DATABASE: database.env.MYSQLDATABASE,
    DB_USERNAME: database.env.MYSQLUSER,
    DB_PASSWORD: database.env.MYSQLPASSWORD,
  };

  const web = service("web", {
    source: github("Julboben/laundry-room-booking-system", {
      branch: "main",
    }),
    preDeploy: "php scripts/run_migrations.php",
    healthcheck: "/health.php",
    healthcheckTimeout: 300,
    replicas: {
      "europe-west4": 1,
    },
    deploy: {
      restartPolicyType: "ON_FAILURE",
      restartPolicyMaxRetries: 3,
    },
    env: {
      ...databaseEnvironment,
      APP_ENV: "production",
      APP_DEBUG: "false",
      APP_URL: preserve(),
      APP_TIMEZONE: "Europe/Copenhagen",
      TRUST_PROXY_HEADERS: "true",
      RAILPACK_PHP_ROOT_DIR: "/app/public",
      RESIDENT_PROPERTY_CODE_HASH: preserve(),
      ADMIN_USERNAME: preserve(),
      ADMIN_PASSWORD_HASH: preserve(),
      SESSION_NAME: "laundry_booking_session",
      BOOKING_WEEKS_AHEAD: "8",
      OLD_BOOKING_RETENTION_DAYS: "180",
    },
  });

  const cleanup = service("cleanup", {
    source: github("Julboben/laundry-room-booking-system", {
      branch: "main",
    }),
    start: "php scripts/cleanup_old_bookings.php",
    deploy: {
      cronSchedule: "15 2 * * *",
      restartPolicyType: "NEVER",
    },
    env: {
      ...databaseEnvironment,
      APP_ENV: "production",
      APP_DEBUG: "false",
      APP_TIMEZONE: "Europe/Copenhagen",
      OLD_BOOKING_RETENTION_DAYS: "180",
    },
  });

  return project("laundry-room-booking-system", {
    resources: [database, web, cleanup],
  });
});
