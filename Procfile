worker: php bin/console messenger:consume async --memory-limit=1G
postdeploy: php bin/console at:cron:site:redis_cache_reset