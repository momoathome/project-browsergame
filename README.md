<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Getting Started

### Step 1: clone the Repository

``` bash
git clone https://github.com/momoathome/project-browsergame.git
```

### Step 2: Install dependencies and create Docker container with sail

``` bash
# install composer Dependencies for existing App in sail
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### Step 3: Install NPM Packages

``` bash
npm install
```

### Step 4: Configure Environment Variables

Create a copy of the .env.example file and rename it to .env.

``` bash
cp .env.example .env
```

### Step 5: Container starten

``` bash
./vendor/bin/sail up -d
```

### Step 6: Generate Application Key

Generate a new application key. This step is crucial for application security.

``` bash
./vendor/bin/sail artisan key:generate   
```

### Step 7: Update Environment Settings

Edit the .env file with the following entries

``` md
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
CACHE_STORE=redis

PUSHER_APP_ID="YOUR ID"
PUSHER_APP_KEY="YOUR KEY"
PUSHER_APP_SECRET="YOUR SECRET"
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME="https"
PUSHER_APP_CLUSTER="mt1"

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_NO_ANALYTICS=false
MEILISEARCH_KEY=masterKey

DEBUGBAR_ENABLED=true
```

### Step 8: Run Database Migrations

Perform database migrations with the following command. This will set up your database schema and seed the Database

``` bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### Step 9: Index your database for faster search

``` bash
./vendor/bin/sail artisan scout:import "Orion\Modules\Asteroid\Models\Asteroid"
./vendor/bin/sail artisan scout:index "Orion\Modules\Asteroid\Models\Asteroid"
./vendor/bin/sail artisan scout:import "Orion\Modules\Station\Models\Station"
./vendor/bin/sail artisan scout:index "Orion\Modules\Station\Models\Station"
./vendor/bin/sail artisan meilisearch:configure
```

### Step 10: dump-autoload

``` bash
./vendor/bin/sail composer dump-autoload
```

## Commands

``` bash
sail artisan db:seed --class=AsteroidSeeder
sail artisan game:generate-test-stations --count=25 --use-regions
sail artisan game:cleanup-test-stations --all
sail artisan universe:reserve-regions 50 --refresh
sail artisan test:station-spawns 50 --refresh --debug --clear-cache --reserve-regions=0 --test-reserved --show-all
sail artisan make:extended ModelNamen --m --c --r --s
```

## Authors

- [@momoathome](https://github.com/momoathome) - Maurice
