ARG APP_IMAGE=planner-tecom:latest
FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

COPY --from=app /var/www/public /var/www/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
