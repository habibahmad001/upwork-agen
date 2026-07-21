# Upwork Job Agent

An intelligent job monitoring system that crawls Upwork, evaluates jobs using AI, and sends high-quality matches via WhatsApp notifications.

## Features

- 🤖 **AI-Powered Scoring**: Jobs are evaluated based on your skills profile
- 📱 **WhatsApp Notifications**: Receive instant alerts for high-quality jobs
- 🎯 **Smart Filtering**: Configurable filters for budget, location, and skills
- 📊 **Dashboard UI**: Web interface for monitoring and management
- 🔁 **Continuous Crawling**: Automated job discovery at configurable intervals
- 🛡️ **Duplicate Detection**: Prevents duplicate job notifications
- 📈 **Analytics**: Track crawler performance and job statistics

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.4)
- **Crawler**: Node.js + Playwright
- **Database**: MySQL 8.0
- **Queue**: Redis
- **AI Service**: OpenAI API / Mock Service (development)
- **Messaging**: WhatsApp Cloud API

## Installation

### Prerequisites

- PHP 8.4+
- Node.js 18+
- MySQL 8.0+
- Redis
- Composer

### Setup

1. Clone the repository
```bash
git clone <repository-url>
cd upwork-agen
```

2. Install PHP dependencies
```bash
composer install
```

3. Install Node.js dependencies for crawler
```bash
cd crawler
npm install
```

4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

5. Update `.env` with your credentials
```env
DB_DATABASE=upwork_agent
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations
```bash
php artisan migrate --seed
```

7. Login to Upwork (for crawler)
```bash
cd crawler
node playwright/login.js
```

8. Start queue workers
```bash
php artisan queue:work redis
```

9. Start scheduler
```bash
php artisan schedule:work
```

## Configuration

### AI Service

The application supports multiple AI providers:

- **mock** (default): Keyword-based matching (no API required)
- **openai**: GPT-4o-mini for production
- **groq**: Free alternative for development
- **ollama**: Local models

Configure in `.env`:
```env
AI_PROVIDER=mock
OPENAI_API_KEY=your_key_here
```

### WhatsApp

Setup WhatsApp Cloud API credentials:
```env
WHATSAPP_PHONE_ID=your_phone_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER=+1234567890
```

### Crawler Settings

Configure crawler behavior via the dashboard or `.env`:
```env
CRAWLER_ENABLED=true
CRAWLER_INTERVAL=30
CRAWLER_TIMEOUT=120
```

## Usage

### Manual Crawler Run

```bash
php artisan crawler:run
```

### Manual Cleanup

```bash
php artisan system:cleanup
```

### Import Jobs from File

```bash
php artisan crawler:import /path/to/jobs.json
```

## Dashboard

Access the dashboard at `http://your-domain.com`

- View discovered jobs
- Monitor crawler performance
- Manage settings
- View analytics

## Project Structure

```
upwork-agen/
├── app/                    # Laravel application
│   ├── Contracts/          # Service interfaces
│   ├── DTOs/               # Data transfer objects
│   ├── Jobs/               # Queue jobs
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic
│   └── Http/Controllers/   # Controllers
├── crawler/                # Playwright crawler
│   └── playwright/         # Crawler scripts
├── config/                 # Configuration files
├── database/               # Migrations and seeders
├── public/                 # Public files
├── resources/              # Views and assets
└── routes/                 # Route definitions
```

## Development

### Run Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

## Security

- `.env` contains sensitive credentials - never commit it
- `storage.json` contains session data - keep it private
- API keys are encrypted in database
- All settings are sanitised before use

## License

MIT

## Support

For issues and questions, please open a GitHub issue.
