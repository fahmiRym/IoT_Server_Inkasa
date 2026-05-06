# NOC Dashboard Deployment TODO

## Status: [In Progress]

- [x] 1. Confirm repo cloned (https://github.com/fahmiRym/IoT_Server_Inkasa/) ✅
- [ ] 2. Standardize passwords to 'alhamdulillah' in docker-compose.yml and docs
- [ ] 3. Install production dependencies (composer install --no-dev --optimize-autoloader)
- [ ] 4. Frontend build if needed (npm ci --production && npm run build)
- [ ] 5. Copy .env.example to .env and php artisan key:generate
- [ ] 6. Test local Docker: docker-compose up -d && php artisan migrate
- [ ] 7. Rsync to remote server 192.168.11.178:/var/www/noc-dashboard/ (exclude vendor/node_modules/.env)
- [ ] 8. On server: setup .env, docker-compose build && up -d
- [ ] 9. Verify: docker-compose ps, logs, access http://192.168.11.178
- [ ] 10. Cleanup: Update this TODO with completion
