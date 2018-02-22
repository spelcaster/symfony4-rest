## Installing

```
composer install
```

## Database

```
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate
bin/console hautelook:fixtures:load

```

## Generate private and public keys to generate JWT

```
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# check private key
openssl rsa -in config/jwt/private.pem -check

# check public key syntax, throw error if invalid
openssl rsa -inform PEM -pubin -in config/jwt/public.pem -noout
```

## Run tests

```
bin/phpunit
```

*Important*: Don't forget to adjust your environment variables defined in
phpunit.xml.dist.

## Todo

- [ ] Fix resources scss
- [ ] Fix resources font-awesome
- [ ] Implement API
- [ ] Fix unit test for different environments (dev and test should differ)
