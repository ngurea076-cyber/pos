# ShopICT — Laravel 12

The former React/Supabase app has been restructured as a Laravel 12 monolith using MySQL, Blade, Tailwind CSS 4, and Livewire 3. Serialized inventory updates and checkout run in database transactions.

## Setup

1. Install PHP 8.2+, Composer, Node.js, and MySQL 8+.
2. Run `composer install` and `npm install`.
3. Copy `.env.example` to `.env` and enter the MySQL credentials.
4. Run `php artisan key:generate` and `php artisan migrate`.
5. Run `composer run dev`.

The first registered account becomes an administrator. Later accounts are attendants. Existing Supabase data must be exported and imported separately because UUID user identities cannot be inferred safely from source migrations alone.
