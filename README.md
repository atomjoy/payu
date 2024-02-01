# Payu Laravel

Płatności PayU w Laravel. Jak utworzyć link do płatności za zamówienie w payu api.

## Payu dokumentacja, sandbox

<https://developers.payu.com/pl/overview.html#sandbox>

## Instalacja pakietu Laravela

Zainstaluj php composera ze strony <https://getcomposer.org/download>

```sh
composer require atomjoy/payu "^3.0.0"
composer update
composer dump-autoload -o
```

## Konfiguracja Laravel

### Dodaj bazę danych

mysql -u root

```sql
CREATE DATABASE IF NOT EXISTS laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laravel.* TO root@localhost IDENTIFIED BY 'toor' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON laravel.* TO root@127.0.0.1 IDENTIFIED BY 'toor' WITH GRANT OPTION;
FLUSH PRIVILEGES;

# Clear or change password
SET PASSWORD FOR root@localhost=PASSWORD('');

# Change password
ALTER USER 'testing'@'localhost' IDENTIFIED BY 'toor';
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

### Utwórz model Order

```sh
php artisan make:model Order -a
```

### Migracja tabeli klasy Order

Dodaj kolumny w tabeli.

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->decimal('cost', 15, 2)->nullable()->default(0.00);
      $table->enum('payment_method', ['money', 'card', 'online', 'cashback'])->nullable()->default('money');
      $table->enum('payment_gateway', ['payu'])->nullable(true);
      $table->string('firstname');
      $table->string('lastname');
      $table->string('phone');
      $table->string('email');
      $table->timestamps();
      $table->softDeletes();
      $table->unsignedBigInteger('user_id')->nullable(true);
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
    });
  }

  public function down()
  {
    Schema::dropIfExists('orders');
  }
};
```

### Klasa modelu Order

Dodaj interfejs do klasy zamówień i uzupełnij wymagane metody.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Payu\Interfaces\PayuOrderInterface;
use Payu\Models\Payment;

class Order extends Model implements PayuOrderInterface
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  public function payments()
  {
    return $this->hasMany(Payment::class)->withTrashed();
  }

  public function paid_payment()
  {
    return $this->hasOne(Payment::class)->where('status', 'COMPLETED')->withTrashed()->latest();
  }

  // Wymagane metody poniżej
  function orderId()
  {
    return $this->id;
  }

  function orderCost()
  {
    return $this->cost;
  }

  function orderFirstname()
  {
    return $this->firstname;
  }

  function orderLastname()
  {
    return $this->lastname;
  }

  function orderPhone()
  {
    return $this->phone;
  }

  function orderEmail()
  {
    return $this->email;
  }
}
```

### Utwórz tabele w bazie danych

```sh
# Aktualizuj tabelki
php artisan migrate
php artisan migrate --env=testing
```

### Utwórz i edytuj plik konfiguracyjny Payu Api

config/payu.php

```sh
php artisan vendor:publish --tag=payu-config
```

### Aktualizacja cache dir linux (gdy błędy)

```sh
sudo mkdir -p storage/framework/cache/payu
sudo chown -R www-data:www-data storage/framework/cache/payu
sudo chmod -R 770 storage/framework/cache/payu
```

### Edytuj strony potwierdzeń płatności (opcjonalnie)

resources/views/vendor/payu

```sh
php artisan vendor:publish --tag=payu-pages
```

### Dodaj folder logo payu (opcjonalnie)

public/vendor/payu

```sh
php artisan vendor:publish --tag=payu-public --force
```

## Testy

### Dodaj w phpunit.xml

```xml
<testsuite name="Payu">
  <directory suffix="Test.php">./vendor/atomjoy/payu/tests/Payu</directory>
</testsuite>
```

### Tests tylko dla sandbox config(['payu.env' => 'sandbox'])

Utworzy link do płatności w bazie danych w tabeli payments (do przekierowania klienta sklepu).

```sh
php artisan test --testsuite=Payu --stop-on-failure
```

## Laravel PayU Api

Wyłączyć w panelu administracyjnym PayU automatyczny odbiór płatności jeśli chcesz potwierdzać płatności ręcznie dla statusu WAITING_FOR_CONFIRMATION na COMPLETED lub CANCELED.

### Utwórz link do płatności dla zamówienia (sandbox)

Numer zamówienia {orders.id} => 1, 2, 3, ...

```sh
# Utwórz zamowienie i link do płatności
https://{your.domain.here}/web/payment/create

# Lub utwórz link do płatności z id zamówienia
https://{your.domain.here}/web/payment/url/payu/{orders.id}

# Pobierz dane płatności
https://{your.domain.here}/web/payment/retrive/payu/{orders.id}

# Aktualizuj dane płatności
https://{your.domain.here}/web/payment/refresh/payu/{orders.id}

# Przyjmij płatność
https://{your.domain.here}/web/payment/confirm/payu/{orders.id}

# Odrzuć płatność
https://{your.domain.here}/web/payment/cancel/payu/{orders.id}
```

### Lista routes do obsługi płatności (sandbox)

atomjoy/payu/routes/sandbox.php

## Przykłady Payu Api w Laravel

### Utwórz link płatności dla zamówienia (produkcja)

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

} catch (QueryException | PDOException $e) {
  report($e);
  return response('Database Error.', 422);
} catch (Exception $e) {
  report($e);
  return response($e->getMessage(), 422);
}
```

### Potwierdź płatność (waiting_for_confirmation)

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

## Zdarzenia Payu (events)

```php
<?php

use Payu\Events\PayuPaymentCreated;
use Payu\Events\PayuPaymentCanceled;
use Payu\Events\PayuPaymentConfirmed;
use Payu\Events\PayuPaymentRefunded;
use Payu\Events\PayuPaymentNotified;
```

## Przechwytywanie zdarzeń (listeners)

```sh
php artisan make:listener PaymentCreatedNotification --event=PayuPaymentCreated
```

## Tworzenie klas dla modeli

```sh
php artisan make:model Order -a
php artisan make:resource OrderResource
php artisan make:resource OrderCollection
```

## Pobierz listę zamówień (admin panel)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Order;

// Przykład
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

## LICENSE

This project is licensed under the terms of the GNU GPLv3 license.
