FROM php:8.1-fpm

# COMMON ---------------------------------------------------------------------------------------------------------------
# make sure apt is up to date
RUN apt-get update --fix-missing \
    && apt-get install -y \
        curl  \
        build-essential \
        libssl-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libicu-dev \
        libpq-dev \
        libldap2-dev \
        libzip-dev \
        libxml2-dev \
        libicu-dev \
        vim \
        debconf \
        subversion \
        git \
        apt-transport-https \
        apt-utils \
        locales \
        acl \
        mailutils \
        wget \
        zip \
        unzip \
        gnupg \
        gnupg1 \
        gnupg2 \
        wkhtmltopdf \
        xvfb \
    && apt-get clean
# end COMMON -----------------------------------------------------------------------------------------------------------


# LOCALE ---------------------------------------------------------------------------------------------------------------
RUN sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen
ENV LANG fr_FR.UTF-8
ENV LANGUAGE fr_FR:fr
ENV LC_ALL fr_FR.UTF-8
# end LOCALE -----------------------------------------------------------------------------------------------------------

# PDOSQL, IMAGICK, SOAP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && apt-get install -y libwebp-dev \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install soap \
    && docker-php-ext-configure zip --with-zip \
    && docker-php-ext-install zip \
    && docker-php-ext-install sockets

# end PDOSQL, IMAGICK, SOAP



# Imagick
RUN apt-get update && apt-get install -y libmagickwand-6.q16-dev --no-install-recommends \
 && ln -s /usr/lib/x86_64-linux-gnu/ImageMagick-6.8.9/bin-Q16/MagickWand-config /usr/bin \
 && pecl install imagick \
 && echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini
# end Imagick

# POSTGRES
# ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# RUN chmod +x /usr/local/bin/install-php-extensions && \
#     install-php-extensions pdo_pgsql intl
# end POSTGRES

COPY php-fpm-pool.conf /etc/php/8.1/pool.d/www.conf

# PHP INI --------------------------------------------------------------------------------------------------------------
ARG PHP_INI=php.ini
COPY $PHP_INI /usr/local/etc/php/
# end PHP INI ----------------------------------------------------------------------------------------------------------

# Install composer -----------------------------------------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN groupadd dev -g 999
#RUN useradd dev -g dev -d /home/dev -m
# end Install composer -------------------------------------------------------------------------------------------------

# NODE JS, YARN et PYTHON ----------------------------------------------------------------------------------------------
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs
# node js >= 16
RUN corepack enable
# node js < 16
RUN npm i -g corepack
RUN yarn init -2
RUN apt-get update || : && apt-get install python3 -y
# npm rebuild node-sass a lancer dans le bash si besoin après update (docker exec -it sf4_php bash)
# end NODE JS, YARN et PYTHON ------------------------------------------------------------------------------------------

WORKDIR /home/

EXPOSE 9000
CMD ["php-fpm"]
