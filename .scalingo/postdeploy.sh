echo "Starting postdeploy script..."
php bin/console at:cron:site:search_cache_reset
echo "Postdeploy script finished."