<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## how to start

get the project from <a href="https://github.com/momoathome/project-browsergame" target="_blank">Github</a>

Install dependencies and create Docker container

``` bash
# install composer Dependencies for existing App in sail
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

``` bash
run ./vendor/bin/sail composer install
run npm install
```

``` bash
run ./vendor/bin/sail up
run ./vendor/bin/sail artisan migrate
```

``` bash
# for indexing 
run sail artisan scout:import "App\Models\Asteroid"
run sail artisan scout:index "App\Models\Asteroid"
```
