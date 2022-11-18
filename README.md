# Payu Laravel

Płatności PayU w Laravel. Jak utworzyć link do płatności za zamówienie w payu api.

## Payu dokumentacja, sandbox

https://developers.payu.com/pl/overview.html#sandbox

## Instalacja pakietu Laravela

Zainstaluj php composera ze strony https://getcomposer.org/download

```sh
composer require atomjoy/payu 1.0.*
composer update
composer dump-autoload -o
```

## Konfiguracja Laravel

### Dodaj klasy modeli

```sh
php artisan make:model Order
php artisan make:model Client
```

### Edytuj Order model aplikacji

```php
<?php
namespace App\Models;

use Payu\Models\Order as PaymentOrder;

class Order extends PaymentOrder
{
  protected $guarded = [];
}
```

### Edytuj Client model aplikacji

```php
<?php
namespace App\Models;

use Payu\Models\Client as PaymentClient;

class Client extends PaymentClient
{
  protected $guarded = [];
}
```

### Dodaj bazę danych

mysql -u root

```sql
CREATE DATABASE IF NOT EXISTS laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laravel.* TO root@localhost IDENTIFIED BY 'toor' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON laravel.* TO root@127.0.0.1 IDENTIFIED BY 'toor' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

### Konfiguracja .env

```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=toor
```

### Utwórz tabele

```sh
php artisan migrate

# Dodaj przykładowe zamówienia (sandbox, testy)
php artisan db:seed --class="\Database\Seeders\PayuDatabaseSeeder"
```

### Utwórz i edytuj plik konfiguracyjny Payu w Laravel

config/payu.php

```sh
php artisan vendor:publish --tag=payu-config
```

### Edytuj logo payu (optional)

public/vendor/payu

```sh
php artisan vendor:publish --tag=payu-public --force
```

### Edytuj strony potwierdzeń płatności (optional)

resources/views/vendor/payu

```sh
php artisan vendor:publish --tag=payu-pages
```

### Cache dir (optional)

```sh
sudo mkdir -p storage/framework/cache/payu
sudo chown -R www-data:www-data storage/framework/cache/payu
sudo chmod -R 770 storage/framework/cache/payu
```

# Przykłady

Wyłączyć w panelu administracyjnym PayU automatyczny odbiór płatności jeśli chcesz potwierdzać płatności ręcznie dla statusu WAITING_FOR_CONFIRMATION na COMPLETED lub CANCELED.

### Utwóz link płatności dla zamowienia

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  // Create order here or get from db with id
  $id = 'orders.id';

  // Create payment url
  $url = Payu::pay(Order::findOrFail($id));

  // Redirect client to payment page
  return redirect($url);

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Przyjmij płatność (waiting_for_confirmation)

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $status = Payu::confirm(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Odrzuć płatność (waiting_for_confirmation)

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $status = Payu::cancel(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Aktualizuj płatność

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $status = Payu::refresh(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Szczegóły płatności

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $payment = Payu::retrive(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Szczegóły transakcji

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $transaction = Payu::transaction(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Zwrot płatności w całości

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $status = Payu::refund(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Szczegóły zwrotu płatności

```php
<?php
use App\Models\Order;
use Payu\Facades\Payu;

try {
  $id = 'orders.id';
  $status = Payu::refunds(Order::findOrFail($id));

} catch (\Exception $e) {
  return $e->getMessage();
}
```

### Metody płatności lista

```php
<?php
use Payu\Facades\Payu;

try {
  $pay_methods = Payu::payments('pl');

} catch (\Exception $e) {
  return $e->getMessage();
}
```

## Eventy Payu w Laravel

```php
<?php

use Payu\Events\PayuPaymentCreated;
use Payu\Events\PayuPaymentNotCreated;
use Payu\Events\PayuPaymentCanceled;
use Payu\Events\PayuPaymentConfirmed;
use Payu\Events\PayuPaymentRefunded;
use Payu\Events\PayuPaymentNotified;
```

## Listenery

```sh
php artisan make:listener PaymentNotCreatedNotification --event=PayuPaymentNotCreated
php artisan make:listener PaymentCreatedNotification --event=PayuPaymentCreated
php artisan make:listener PaymentCanceledNotification --event=PayuPaymentCanceled
php artisan make:listener PaymentConfirmedNotification --event=PayuPaymentConfirmed
```

## Przyklady routes do obsługi płatności (sandbox, admin panel)

atomjoy/payu/routes/admin.php

## Pobierz listę zamówień (admin panel)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Order;

Route::get('/orders', function () {
  // Zamówienia z płatnościami
  return Order::with('payment')->orderBy('created_at', 'desc')->get();

  // Zamówienia z płatnościami i danymi klienta
  return Order::with('payment','client')->orderBy('created_at', 'desc')->get();

  // Filtruj kolumny
  return Order::with(['payment' => function($query) {
    $query->select('id','id','total','status','status_refund','created_at')->orderBy('created_at', 'desc');
  }])->orderBy('created_at', 'desc')->get();
});
```