echo "Starting postdeploy script..."
php bin/console at:cron:site:redis_cache_reset
echo "Postdeploy script finished."