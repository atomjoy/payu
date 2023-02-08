# Test

## Database and user

mysql -u root

```sql
CREATE DATABASE IF NOT EXISTS laravel_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laravel_testing.* TO testing@localhost IDENTIFIED BY 'toor' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON laravel_testing.* TO testing@127.0.0.1 IDENTIFIED BY 'toor' WITH GRANT OPTION;
FLUSH PRIVILEGES;

# Clear or change password
SET PASSWORD FOR root@localhost=PASSWORD('');

# Change password
ALTER USER 'testing'@'localhost' IDENTIFIED BY 'toor';
FLUSH PRIVILEGES;
```

### Config .env.testing

```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_testing
DB_USERNAME=testing
DB_PASSWORD=toor
```

### Migration, seed

```sh
# Migracja i populacja tabel
php artisan --env=testing migrate
php artisan --env=testing migrate:fresh

# Tylko populacja tabel (przykład)
php artisan --env=testing db:seed --class="\Database\Seeders\PayuDatabaseSeeder"

# Lub dodaj w katalogu databases/seeders/DatabaseSeeder.php aplikacji (przykład)
$this->call([
  PayuDatabaseSeeder::class,
]);
```

### Settings phpunit.xml

```xml
<testsuite name="Payu">
  <directory suffix="Test.php">./vendor/atomjoy/payu/tests/Payu</directory>
</testsuite>

<!-- optional -->
<env name="APP_ENV" value="testing" force="true"/>
<env name="APP_DEBUG" value="true" force="true"/>
```

### Dirs

```sh
sudo chown -R www-data:www-data storage/framework/cache
sudo chmod -R 770 storage/framework/cache
```

### Run tests

```sh
# Tests only for config(['payu.env' => 'sandbox'])
php artisan test --testsuite=Payu --stop-on-failure
```

## Table update (examples)

### Make migration

```sh
php artisan make:migration UpdatePayuTables
```

### Update package migration tables

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePayuTables extends Migration
{
  public function up()
  {
    Schema::table('orders', function (Blueprint $table) {
      // Columns
      if (!Schema::hasColumn('orders', 'user_id')) {
        $table->unsignedBigInteger('user_id')->nullable(true)->after('uid');
      }

      // Indexes
      $table->index('user_id');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
    });

    Schema::table('clients', function (Blueprint $table) {
      $table->string('zip', 10)->nullable(true)->after('country');
    });
  }

  public function down()
  {
    Schema::table('orders', function (Blueprint $table) {
      // Indexes
      $table->dropForeign('orders_user_id_foreign');
      $table->dropIndex('orders_user_id_index');

      // Drop columns
      $table->dropColumn([
        'user_id'
      ]);
    });

    Schema::table('clients', function (Blueprint $table) {
      // Drop columns
      $table->dropColumn([
        'zip'
      ]);
    });
  }
}
```

### Update migrations

```sh
php artisan migrate
php artisan migrate:fresh
```

### Update model sample

```php
<?php
namespace App\Models;

class Order
{
  function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->mergeFillable([
      // 'mobile', 'website'
    ]);

    $this->mergeCasts([
      // 'status' => StatusEnum::class,
      // 'email_verified_at' => 'datetime:Y-m-d H:i:s',
    ]);

    $this->hidden[] = 'secret_column';
  }

  protected $dispatchesEvents = [
    // 'saved' => UserSaved::class,
    // 'deleted' => UserDeleted::class,
  ];
}
```

### Set in .env, .env.testing

```sh
# Default Storage::disk()
FILESYSTEM_DISK=public
```

### Php artisan

```sh
php artisan route:list
php artisan cache:clear
php artisan config:clear
php artisan key:generate
php artisan storage:link
php artisan session:table
php artisan queue:table
```

## Composer

### Local directory

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/atomjoy/payu"
    }
  ],
  "require": {
    "atomjoy/payu": "dev-main"
  }
}
```

### Remote git repo

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/atomjoy/payu"
    }
  ],
  "require": {
    "atomjoy/payu": "*"
  }
}
```

### Require

```sh
# cmd
composer require atomjoy/payu "~1.0.0"

# composer.json
{
  "require": {
    "atomjoy/payu": "~1.0.0"
  }
}
```

## Payment APIs

<https://github.com/PayU-EMEA/openpayu_php>
