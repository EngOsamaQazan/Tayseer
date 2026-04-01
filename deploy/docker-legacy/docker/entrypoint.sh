#!/bin/bash
set -e

# Install composer dependencies if vendor directory doesn't exist
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Run Yii init if not already initialized (check for main-local.php)
if [ ! -f "/var/www/html/common/config/main-local.php" ]; then
    echo "Initializing Yii2 application (JadalDevelopment)..."
    php init --env=JadalDevelopment --overwrite=All
fi

# Ensure writable directories exist and have correct permissions
mkdir -p /var/www/html/backend/runtime
mkdir -p /var/www/html/backend/web/assets
mkdir -p /var/www/html/backend/web/images/imagemanager
mkdir -p /var/www/html/backend/web/uploads/customers/documents
mkdir -p /var/www/html/backend/web/uploads/customers/photos
mkdir -p /var/www/html/backend/web/uploads/customers/thumbs
mkdir -p /var/www/html/console/runtime
mkdir -p /var/www/html/frontend/runtime
mkdir -p /var/www/html/frontend/web/assets

chmod -R 777 /var/www/html/backend/runtime
chmod -R 777 /var/www/html/backend/web/assets
chmod -R 777 /var/www/html/backend/web/images
chmod -R 777 /var/www/html/backend/web/uploads
chmod -R 777 /var/www/html/console/runtime
chmod -R 777 /var/www/html/frontend/runtime
chmod -R 777 /var/www/html/frontend/web/assets

# Create/update database views and materialized cache for optimized reports
echo "Creating database views and cache..."
for i in $(seq 1 30); do
    if mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/views.sql 2>/dev/null; then
        echo "Database views created."
        # Create materialized cache table
        mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/materialized_view.sql 2>/dev/null && echo "Cache table populated."
        # Create stored procedures
        php /var/www/html/docker/create_sp.php 2>/dev/null && echo "Stored procedures created."
        # Apply action-level permissions migration (INSERT IGNORE = safe to re-run)
        mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/migration_permissions.sql 2>/dev/null && echo "Action-level permissions applied."
        # Create Vision API usage tracking table
        mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/vision_api_table.sql 2>/dev/null && echo "Vision API usage table created."
        # Create OCP (Operational Control Panel) tables
        mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/ocp_tables.sql 2>/dev/null && echo "OCP tables created."
        # Apply HR Module migration (CREATE TABLE IF NOT EXISTS = safe to re-run)
        mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/database/migrations/2026_02_12_hr_module.sql 2>/dev/null && echo "HR module tables created."
        break
    fi
    echo "Waiting for MySQL... attempt $i"
    sleep 3
done

echo "Starting Apache..."
exec apache2-foreground
