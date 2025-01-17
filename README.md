# Santa's Little Helper - One to rule them all

:warning: Just having a play with static-php.dev

## Build locally

Install composer packages

```
composer install
```

Check installation

```
composer run doctor
```

Prepare/download static-php (`.cache/`)

```
composer run build:prepare
```

Build phar file - required for static-php

```
composer run build:phar
```

Build static-php standalone binary

```
composer run build:standalone
```

Once this was successful, `composer run build` will rebuild
the entire phar and binary.