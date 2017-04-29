# Starter Kit for SilverStripe module

This repository is for new modules of SilverStripe as a template before creating.

## Usage

Clone this repo before creating a new SilverStripe module. Change `_config.php` and `_config/config.yml` files within your module names, example:

```php
    define('YOUR_MODULE_NAME_DIR', basename(dirname(__FILE__))); // at _config.php
```

```yaml
    ---
    Name: yourmodulename
    After:
      - 'framework/*'
      - 'cms/*'
    ---
```

## Javascript and SASS

Project are created to work within `gulp` and `babel` transpiler for ES6. All bundles and css files will be transpiled to `assets/*` folders by the configuration at `gulpfile.babel.js` constant of `CONFIG`.

Run `npm install` to download gulp dependencies which are at `package.json`.

### Maintainer

Feel free to write a message to:

Donatas Navidonskis <donatas@navidonskis.com>