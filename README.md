# Payu Laravel

Płatności PayU w Laravel. Jak utworzyć link do płatności za zamówienie w payu api.

## Payu dokumentacja, sandbox

https://developers.payu.com/pl/overview.html#sandbox

## Instalacja pakietu Laravela

Zainstaluj php composera ze strony https://getcomposer.org/download

```sh
composer require atomjoy/payu 2.0.*
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

### Dodaj kolumny w tabeli

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
			$table->enum('payment_method', ['money', 'card', 'online'])->nullable()->default('money');
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
  function order_id()
  {
    return $this->id;
  }

  function order_cost()
  {
    return $this->cost;
  }

  function order_firstname()
  {
    return $this->firstname;
  }

  function order_lastname()
  {
    return $this->lastname;
  }

  function order_phone()
  {
    return $this->phone;
  }

  function order_email()
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

### Aktualizacja cache dir linux (if errors)

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

## Testuj

### Dodaj w phpunit.xml

```xml
<testsuite name="Payu">
	<directory suffix="Test.php">./vendor/atomjoy/payu/tests/Payu</directory>
</testsuite>
```

### Tests only with config(['payu.env' => 'sandbox'])

```sh
php artisan test --testsuite=Payu --stop-on-failure
```

# Laravel PayU Api

Wyłączyć w panelu administracyjnym PayU automatyczny odbiór płatności jeśli chcesz potwierdzać płatności ręcznie dla statusu WAITING_FOR_CONFIRMATION na COMPLETED lub CANCELED.

### Utwórz link do płatności dla zamówienia (sandbox)

Numer zamówienia {orders.id} => 1, 2, 3, ...

```sh
# Utwórz zamowienie a następnie

# Utwórz link do płatności
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

atomjoy/payu/routes/admin.php

# Przykłady Api w Php

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

} catch (\Exception $e) {
  return $e->getMessage();
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

## Eventy Payu

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

## Tworzenie klas modeli

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
