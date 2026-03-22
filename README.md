# familyCal

Family calendar app for syncing and sharing household schedules, including Google Calendar integration and a public view for read-only access.

## Features

- Connect and sync Google Calendars
- Share a public, read-only calendar view
- Manage household events in one place

## Authentication

You will need a local user account to connect Google Calendar. Use the app's registration flow to create one after setup.

## Requirements

- PHP 8.4+
- Composer
- Node.js + npm
- SQLite (default) or another supported database

## Setup

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Install frontend dependencies:

   ```bash
   npm install
   ```

3. Create your environment file and app key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in `.env` (SQLite is the default).

5. Run migrations:

   ```bash
   php artisan migrate
   ```

6. Start the dev servers:

   ```bash
   npm run dev
   php artisan serve
   ```

The app will be available at http://localhost:8000.

## Configuration

Required `.env` keys:

- `APP_KEY`
- `GOOGLE_CALENDAR_CLIENT_ID`
- `GOOGLE_CALENDAR_CLIENT_SECRET`
- `GOOGLE_CALENDAR_REDIRECT_URI`
- `GOOGLE_CALENDAR_SCOPES`

Optional `.env` keys:

- `PUBLIC_CALENDAR_ACCOUNT_EMAIL`

## Google Calendar API keys

This app uses Google OAuth to connect user calendars. You will need a Google Cloud project with the Calendar API enabled and OAuth credentials.

1. Go to the Google Cloud Console and create (or select) a project.
2. Enable the **Google Calendar API** for that project.
3. Configure the **OAuth consent screen** (External or Internal) and add the required scopes:

   - `https://www.googleapis.com/auth/calendar`
   - `openid`
   - `email`
   - `profile`

4. Create OAuth credentials:

   - Type: **OAuth client ID**
   - Application type: **Web application**
   - Authorized redirect URI: `http://localhost:8000/settings/google-calendar/callback`

5. Copy the client ID and client secret into `.env`:

   ```dotenv
   GOOGLE_CALENDAR_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CALENDAR_CLIENT_SECRET=your-client-secret
   GOOGLE_CALENDAR_REDIRECT_URI=http://localhost:8000/settings/google-calendar/callback
   GOOGLE_CALENDAR_SCOPES=https://www.googleapis.com/auth/calendar,openid,email,profile
   ```

### Public calendar account

If you want a public calendar feed, set the account email used for public lookups:

```dotenv
PUBLIC_CALENDAR_ACCOUNT_EMAIL=calendar-owner@example.com
```

## Background jobs

If you enable queued work (recommended for larger syncs), run a queue worker:

```bash
php artisan queue:work
```

## Screenshots

Add screenshots or a short demo GIF here.

## Tests

Run the test suite:

```bash
php artisan test --compact
```
