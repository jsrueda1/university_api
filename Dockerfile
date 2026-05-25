FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysqli \
    libapache2-mod-php8.1 \
    && apt-get clean

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
RUN rm -f /var/www/html/index.html

CMD bash -c "sed -i 's/Listen 80/Listen ${PORT:-80}/g' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/g' /etc/apache2/sites-enabled/000-default.conf && \
    apache2ctl -D FOREGROUND"

EXPOSE 80