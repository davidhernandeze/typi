## Running the project
- Run `composer install`
- Copy `.env.example` to `.env` and set your database credentials
- Run `npm install`
- Run `php artisan migrate`
- Run `php db:seed`
- Run `vite build`
- Run `php artisan serve`
- Open `http://localhost:8000`

## Architecture
- The app is built using Laravel 8.
- The app uses Vue.js for the frontend.
- The app uses Tailwind CSS for the styling.
- No external dependencies were installed to keep simplicity.

## Cache and optimization
- Database caching is used for simplicity. Redis should  work as well.
- In order to optimize and prepare the app for massive traffic, the app uses caching to get the top 10 leaderboard entries.
- The leaderboard cache is refreshed each time a new entry make it to the leaderboard.
- This make the most used endpoint to avoid recalculating the leaderboard on each request.
- Indexes were added to `score` table to optimize read performance.

## Security
- Final results are evaluated exclusively by the server using an array of keyboard events.
- Using this approach enables copy paste detection by default.
- Server side validation is performed for each attempt.
- CSRF protection is enabled.
- The app handle unauthenticated users. But it manages to identify them by their session.

## Testing
- Basic feature and unit tests were implemented in Pest.
- Run `php artisan test`
