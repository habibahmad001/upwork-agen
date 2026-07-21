# 🎯 Upwork Job Monitor - Implementation Plan

## Project Overview

**System Name**: Upwork Job Agent  
**Version**: 1.0.0  
**Target Deployment**: Ubuntu 24 + Nginx + Supervisor  
**Development Approach**: Milestone-based (8 milestones)  
**Created**: 2025-01-01

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Complete Project Structure](#complete-project-structure)
3. [Database Schema](#database-schema)
4. [Service Layer Architecture](#service-layer-architecture)
5. [Queue Jobs Structure](#queue-jobs-structure)
6. [Docker Configuration](#docker-configuration)
7. [8-Milestone Implementation Plan](#8-milestone-implementation-plan)
8. [System Requirements & Decisions](#system-requirements--decisions)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           UPWORK JOB MONITOR SYSTEM                          │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐         ┌─────────────────────┐         ┌─────────────────────┐
│   PLAYWRIGHT        │         │   LARAVEL BACKEND   │         │   WHATSAPP          │
│   CRAWLER (Node.js) │────────▶│   (PHP 8.4)         │────────▶│   CLOUD API         │
│                     │ JSON    │                     │ Queue   │                     │
│ - Storage.json      │         │ - Jobs Service      │         │ - Notifications     │
│ - Headless Browser  │         │ - AI Scoring        │         │ - Rate limited      │
│ - Data Extraction   │         │ - Duplicate Check   │         │                     │
└─────────────────────┘         └─────────────────────┘         └─────────────────────┘
                                        │
                                        │
                    ┌───────────────────┼───────────────────┐
                    ▼                   ▼                   ▼
            ┌───────────────┐   ┌───────────────┐   ┌───────────────┐
            │  MySQL DB     │   │  Redis Queue  │   │  OpenAI API   │
            │               │   │               │   │               │
            │ - Jobs        │   │ - Jobs Queue  │   │ - GPT-4o-mini │
            │ - AI Scores   │   │ - Notif Queue│   │ - Scoring     │
            │ - Settings    │   │               │   │               │
            └───────────────┘   └───────────────┘   └───────────────┘
                                        │
                                        ▼
                              ┌─────────────────────┐
                              │   DASHBOARD UI      │
                              │   (Blade/Livewire)  │
                              │                     │
                              │ - Jobs list         │
                              │ - Analytics         │
                              │ - Settings          │
                              │ - Logs              │
                              └─────────────────────┘
```

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Backend Framework | Laravel | 12 |
| PHP | PHP | 8.4 |
| Crawler | Node.js + Playwright | Latest |
| Database | MySQL | 8.0 |
| Queue/Cache | Redis | Latest |
| AI Service | OpenAI API | GPT-4o-mini |
| Messaging | WhatsApp Cloud API | Latest |
| Process Manager | Supervisor | Latest |
| Web Server | Nginx | Latest |
| OS | Ubuntu | 24.04 |

### Data Flow

```
Scheduler (Every Minute)
    │
    ▼
┌─────────────────────┐
│  1. RunCrawlerJob   │  Playwright crawler → jobs.json
└─────────────────────┘
    │
    ▼
┌─────────────────────┐
│  2. ImportJobsJob   │  Parse → Deduplicate → Store
└─────────────────────┘
    │
    ▼
┌─────────────────────┐
│ 3. EvaluateJobsJob  │  AI scoring → Filter
└─────────────────────┘
    │
    ▼
┌─────────────────────┐
│4. SendNotificationJob│ WhatsApp Cloud API
└─────────────────────┘
    │
    ▼
┌─────────────────────┐
│   5. CleanupJob    │  Delete old data
└─────────────────────┘
```

---

## Complete Project Structure

```
upwork-agent/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   ├── CrawlerRunCommand.php
│   │   │   ├── CleanupCommand.php
│   │   │   └── ImportJobsCommand.php
│   │   └── Kernel.php
│   │
│   ├── Contracts/
│   │   ├── CrawlerServiceInterface.php
│   │   ├── AIEvaluationServiceInterface.php
│   │   ├── NotificationServiceInterface.php
│   │   └── ParserServiceInterface.php
│   │
│   ├── DTOs/
│   │   ├── JobDTO.php
│   │   ├── AIScoreDTO.php
│   │   └── NotificationDTO.php
│   │
│   ├── Exceptions/
│   │   ├── CrawlerException.php
│   │   ├── AIServiceException.php
│   │   ├── NotificationException.php
│   │   └── AuthenticationException.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthenticatedSessionController.php
│   │   │   ├── Dashboard/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── JobController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   ├── LogController.php
│   │   │   │   └── AnalyticsController.php
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   │   ├── Authenticate.php
│   │   │   ├── RedirectIfAuthenticated.php
│   │   │   ├── ThrottleRequests.php
│   │   │   └── SecurityHeaders.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   └── LoginRequest.php
│   │   │   └── Settings/
│   │   │       └── UpdateSettingsRequest.php
│   │   └── Kernel.php
│   │
│   ├── Jobs/
│   │   ├── RunCrawlerJob.php
│   │   ├── ImportJobsJob.php
│   │   ├── EvaluateJobsJob.php
│   │   ├── SendNotificationJob.php
│   │   └── CleanupJob.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Job.php
│   │   ├── JobSkill.php
│   │   ├── JobAiScore.php
│   │   ├── Notification.php
│   │   ├── CrawlerLog.php
│   │   ├── CrawlerSession.php
│   │   ├── Setting.php
│   │   └── SystemLog.php
│   │
│   ├── Repositories/
│   │   ├── JobRepository.php
│   │   ├── JobRepositoryInterface.php
│   │   ├── NotificationRepository.php
│   │   └── SettingRepository.php
│   │
│   ├── Services/
│   │   ├── Upwork/
│   │   │   ├── UpworkCrawlerService.php
│   │   │   └── UpworkAuthService.php
│   │   ├── AI/
│   │   │   ├── AIEvaluationService.php
│   │   │   ├── OpenAIClient.php
│   │   │   └── PromptBuilderService.php
│   │   ├── WhatsApp/
│   │   │   ├── WhatsAppService.php
│   │   │   └── WhatsAppClient.php
│   │   ├── Notification/
│   │   │   └── NotificationService.php
│   │   ├── Parser/
│   │   │   └── JobParserService.php
│   │   ├── Storage/
│   │   │   └── JobStorageService.php
│   │   ├── Monitoring/
│   │   │   └── PerformanceMonitor.php
│   │   ├── DuplicateCheckerService.php
│   │   ├── SettingsService.php
│   │   ├── LoggingService.php
│   │   └── FilterService.php
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       └── EventServiceProvider.php
│
├── bootstrap/
│   ├── app.php
│   └── cache/
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── queue.php
│   ├── services.php
│   ├── crawler.php
│   ├── openai.php
│   ├── whatsapp.php
│   └── logging.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_jobs_table.php
│   │   ├── 2024_01_01_000003_create_job_skills_table.php
│   │   ├── 2024_01_01_000004_create_job_ai_scores_table.php
│   │   ├── 2024_01_01_000005_create_notifications_table.php
│   │   ├── 2024_01_01_000006_create_crawler_logs_table.php
│   │   ├── 2024_01_01_000007_create_crawler_sessions_table.php
│   │   ├── 2024_01_01_000008_create_settings_table.php
│   │   └── 2024_01_01_000009_create_system_logs_table.php
│   │
│   └── seeders/
│       ├── AdminUserSeeder.php
│       ├── SettingsSeeder.php
│       └── SkillsProfileSeeder.php
│
├── crawler/
│   ├── playwright/
│   │   ├── crawler.js
│   │   ├── login.js
│   │   ├── parser.js
│   │   ├── storage.json
│   │   └── package.json
│   └── config.json
│
├── docker/
│   ├── docker-compose.yml
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── nginx/
│   │   └── default.conf
│   └── supervisor/
│       └── supervisord.conf
│
├── public/
│   ├── index.php
│   ├── mix-manifest.json
│   └── build/
│       ├── assets/
│       └── .gitkeep
│
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── register.blade.php
│       ├── dashboard/
│       │   ├── index.blade.php
│       │   ├── jobs.blade.php
│       │   ├── notifications.blade.php
│       │   ├── settings.blade.php
│       │   ├── logs.blade.php
│       │   └── analytics.blade.php
│       └── components/
│           ├── button.blade.php
│           ├── card.blade.php
│           └── table.blade.php
│
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
│
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── JobParserServiceTest.php
│   │   │   ├── DuplicateCheckerServiceTest.php
│   │   │   ├── AIEvaluationServiceTest.php
│   │   │   └── NotificationServiceTest.php
│   │   ├── Models/
│   │   │   └── JobTest.php
│   │   └── Repositories/
│   │       └── JobRepositoryTest.php
│   │
│   ├── Feature/
│   │   ├── Auth/
│   │   │   └── LoginTest.php
│   │   ├── Dashboard/
│   │   │   ├── DashboardAccessTest.php
│   │   │   ├── SettingsUpdateTest.php
│   │   │   └── JobsFilterTest.php
│   │   └── Crawler/
│   │       └── CrawlerIntegrationTest.php
│   │
│   └── Integration/
│       ├── WhatsAppServiceTest.php
│       └── OpenAIServiceTest.php
│
├── .env.example
├── .gitignore
├ ├── artisan
├ ├── composer.json
├ ├── package.json
├ ├── phpunit.xml
├ ├── README.md
└── INSTALLATION.md
```

---

## Database Schema

### ER Diagram

```
┌─────────────┐         ┌──────────────────┐         ┌─────────────────┐
│   users     │         │      jobs        │         │ job_ai_scores   │
├─────────────┤         ├──────────────────┤         ├─────────────────┤
│ id (PK)     │         │ id (PK)          │         │ id (PK)         │
│ name        │         │ job_id (unique)  │◀────────│ job_id (FK)     │
│ email       │         │ title            │         │ score           │
│ password    │         │ description      │         │ reasoning       │
│ created_at  │         │ budget           │         │ technologies    │
└─────────────┘         │ hourly_min       │         │ red_flags       │
                        │ hourly_max       │         │ estimated_hours │
         ┌─────────────│ client_country   │         │ model_version   │
         │             │ payment_verified │         │ created_at      │
         │             │ spent            │         └─────────────────┘
         │             │ hire_rate        │                  │
         │             │ client_rating    │                  │
         │             │ proposals        │                  │
         │             │ experience_level │                  │
         │             │ project_length   │                  │
         │             │ url              │                  │
         │             │ fingerprint      │                  │
         │             │ status           │                  │
         │             │ created_at      │                  │
         │             └──────────────────┘                  │
         │                      │                            │
         │                      │                            │
         │             ┌─────────┴──────────┐                │
         │             │                    │                │
         │    ┌────────▼────────┐   ┌───────▼───────────┐    │
         │    │  job_skills     │   │   notifications   │    │
         │    ├────────────────┤   ├───────────────────┤    │
         │    │ id (PK)         │   │ id (PK)           │    │
         │    │ job_id (FK)     │   │ job_id (FK)       │◀───┘
         │    │ skill           │   │ ai_score_id (FK)  │
         │    └────────────────┘   │ phone_number      │
         │                         │ message_content    │
         │                         │ message_id         │
         │                         │ status             │
         │                         │ error_message      │
         │                         │ sent_at            │
         │                         │ created_at         │
         │                         └───────────────────┘
         │
         │    ┌────────────────────┐   ┌──────────────┐
         └────│ crawler_sessions   │   │ crawler_logs │
              ├────────────────────┤   ├──────────────┤
              │ id (PK)            │   │ id (PK)       │
              │ session_id (uniq) │   │ session_id    │───┐
              │ started_at        │   │ jobs_found    │   │
              │ ended_at          │   │ jobs_new      │   │
              │ status            │   │ status        │   │
              │ last_activity     │   │ duration_ms   │   │
              └────────────────────┘   │ error_message│   │
                                       └──────────────┘   │
               ┌────────────────────┐        │           │
               │     settings      │        │           │
               ├────────────────────┤        │           │
               │ id (PK)            │        │           │
               │ key (unique)       │        │           │
               │ value              │        │           │
               │ type               │        │           │
               │ category           │        │           │
               │ description        │        │           │
               └────────────────────┘        │           │
                                            │           │
               ┌────────────────────┐        │           │
               │   system_logs      │        │           │
               ├────────────────────┤        │           │
               │ id (PK)            │        │           │
               │ type               │        │           │
               │ message            │        │           │
               │ context (json)     │        │           │
               │ source             │        │           │
               │ created_at         │        │           │
               └────────────────────┘        │           │
                                                │           │
                                                └───────────┘
```

### Table Details

#### 1. Users Table
Authentication and user management.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2. Jobs Table
Core job storage with deduplication support.

```sql
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(100) UNIQUE NULL,
    fingerprint VARCHAR(32) NULL,
    
    -- Job details
    title VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    
    -- Budget
    budget DECIMAL(10,2) NULL,
    hourly_min DECIMAL(10,2) NULL,
    hourly_max DECIMAL(10,2) NULL,
    
    -- Client info
    client_country VARCHAR(100) NULL,
    payment_verified BOOLEAN DEFAULT FALSE,
    spent DECIMAL(12,2) NULL,
    hire_rate VARCHAR(20) NULL,
    client_rating DECIMAL(3,2) NULL,
    
    -- Job requirements
    proposals INT NULL,
    experience_level VARCHAR(50) NULL,
    project_length VARCHAR(100) NULL,
    time_posted VARCHAR(100) NULL,
    
    -- URL
    url VARCHAR(500) NULL,
    
    -- Status
    status ENUM('new', 'scoring', 'scored', 'notified', 'skipped', 'archived') DEFAULT 'new',
    
    -- Timestamps
    job_posted_at TIMESTAMP NULL,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_job_id (job_id),
    INDEX idx_fingerprint (fingerprint),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3. Job Skills Table
Many-to-many relationship for job skills.

```sql
CREATE TABLE job_skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    skill VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_job_skill (job_id, skill),
    INDEX idx_skill (skill),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4. Job AI Scores Table
AI evaluation results with detailed analysis.

```sql
CREATE TABLE job_ai_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    
    -- Score
    score DECIMAL(5,2) NOT NULL,
    
    -- AI response details
    reasoning TEXT NULL,
    technologies JSON NULL,
    red_flags JSON NULL,
    estimated_hours VARCHAR(50) NULL,
    estimated_price VARCHAR(50) NULL,
    recommendation TEXT NULL,
    
    -- Metadata
    model_version VARCHAR(50) DEFAULT 'gpt-4o-mini',
    threshold_used DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_job_id (job_id),
    INDEX idx_score (score),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 5. Notifications Table
WhatsApp notification tracking with delivery status.

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    ai_score_id BIGINT UNSIGNED NULL,
    
    -- Message details
    phone_number VARCHAR(20) NOT NULL,
    message_content TEXT NOT NULL,
    whatsapp_message_id VARCHAR(100) NULL,
    
    -- Status
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    last_retry_at TIMESTAMP NULL,
    
    -- Timestamps
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_status_created (status, created_at),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (ai_score_id) REFERENCES job_ai_scores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 6. Crawler Logs Table
Crawler execution tracking and performance metrics.

```sql
CREATE TABLE crawler_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NULL,
    
    -- Results
    jobs_found INT DEFAULT 0,
    jobs_new INT DEFAULT 0,
    jobs_duplicate INT DEFAULT 0,
    
    -- Status
    status ENUM('running', 'success', 'failure', 'partial') DEFAULT 'running',
    error_message TEXT NULL,
    
    -- Performance
    duration_ms INT NULL,
    memory_mb DECIMAL(8,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 7. Crawler Sessions Table
Session management for crawler instances.

```sql
CREATE TABLE crawler_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    
    -- Timing
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    
    -- Status
    status ENUM('running', 'completed', 'failed', 'stopped') DEFAULT 'running',
    
    -- Recovery
    recovery_count INT DEFAULT 0,
    last_recovery_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 8. Settings Table
Configuration storage with type-safe values.

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NULL,
    
    -- Type for casting
    type ENUM('string', 'number', 'boolean', 'json', 'encrypted', 'text') DEFAULT 'string',
    category ENUM('crawler', 'ai', 'notification', 'filter', 'system') DEFAULT 'system',
    
    description VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 9. System Logs Table
Comprehensive system event logging.

```sql
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Log entry
    type ENUM('info', 'warning', 'error', 'debug') DEFAULT 'info',
    message TEXT NOT NULL,
    context JSON NULL,
    source VARCHAR(100) DEFAULT 'system',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (type),
    INDEX idx_source (source),
    INDEX idx_created_at (created_at),
    INDEX idx_created_type (created_at, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Service Layer Architecture

### Design Principles

1. **SOLID Principles**: Single responsibility, open/closed, dependency inversion
2. **Interface Segregation**: Service contracts for external dependencies
3. **Dependency Injection**: Constructor injection for all services
4. **DTO Pattern**: Data transfer objects for type safety
5. **Repository Pattern**: Data access abstraction

### Service Contracts

```php
<?php

namespace App\Contracts;

interface CrawlerServiceInterface
{
    /**
     * Execute the crawler and return raw job data
     */
    public function crawl(): array;
    
    /**
     * Check if authenticated with Upwork
     */
    public function isAuthenticated(): bool;
    
    /**
     * Perform login and save session
     */
    public function login(): bool;
}

interface AIEvaluationServiceInterface
{
    /**
     * Evaluate a single job
     */
    public function evaluate(Job $job): AIScoreDTO;
    
    /**
     * Batch evaluate multiple jobs
     */
    public function batchEvaluate(array $jobs): Collection;
}

interface NotificationServiceInterface
{
    /**
     * Send notification immediately
     */
    public function send(Job $job, AIScoreDTO $score): bool;
    
    /**
     * Queue notification for background sending
     */
    public function queue(Job $job, AIScoreDTO $score): void;
}

interface ParserServiceInterface
{
    /**
     * Parse raw crawler data into JobDTO
     */
    public function parse(array $rawData): JobDTO;
    
    /**
     * Normalize job data
     */
    public function normalize(JobDTO $dto): JobDTO;
}
```

### Data Transfer Objects

```php
<?php

namespace App\DTOs;

class JobDTO
{
    public function __construct(
        public readonly ?string $jobId,
        public readonly string $title,
        public readonly string $description,
        public readonly ?float $budget,
        public readonly ?float $hourlyMin,
        public readonly ?float $hourlyMax,
        public readonly ?string $clientCountry,
        public readonly bool $paymentVerified,
        public readonly ?float $spent,
        public readonly ?string $hireRate,
        public readonly ?float $clientRating,
        public readonly ?int $proposals,
        public readonly ?string $experienceLevel,
        public readonly ?string $projectLength,
        public readonly ?string $timePosted,
        public readonly ?string $url,
        public readonly array $skills
    ) {}
    
    public function fingerprint(): string
    {
        return md5($this->title . $this->clientCountry . $this->timePosted);
    }
}

class AIScoreDTO
{
    public function __construct(
        public readonly float $score,
        public readonly string $reason,
        public readonly array $technologies,
        public readonly array $redFlags,
        public readonly ?string $estimatedHours,
        public readonly ?string $estimatedPrice,
        public readonly ?string $recommendation
    ) {}
    
    public function meetsThreshold(float $threshold): bool
    {
        return $this->score >= $threshold;
    }
}

class NotificationDTO
{
    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
        public readonly int $jobId,
        public readonly int $aiScoreId
    ) {}
}
```

### Service Layer Structure

```
Services/
├── Upwork/
│   ├── UpworkCrawlerService.php      // Coordinates Playwright
│   └── UpworkAuthService.php          // Handles authentication
│
├── AI/
│   ├── AIEvaluationService.php       // Main scoring logic
│   ├── OpenAIClient.php              // API communication
│   └── PromptBuilderService.php      // Prompt construction
│
├── WhatsApp/
│   ├── WhatsAppService.php            // Main notification logic
│   └── WhatsAppClient.php            // API communication
│
├── Notification/
│   └── NotificationService.php        // Message formatting
│
├── Parser/
│   └── JobParserService.php          // Data normalization
│
├── Storage/
│   └── JobStorageService.php         // Database operations
│
├── Monitoring/
│   └── PerformanceMonitor.php       // Metrics tracking
│
├── DuplicateCheckerService.php      // Deduplication logic
├── SettingsService.php               // Configuration management
├── LoggingService.php                // Centralized logging
└── FilterService.php                // Job filtering
```

---

## Queue Jobs Structure

### Job Pipeline

```
Scheduler (Cron - Every Minute)
    │
    ▼
┌──────────────────────────────────────┐
│  RunCrawlerJob                       │
│  - Execute Playwright crawler        │
│  - Save to jobs.json                 │
│  - Dispatch ImportJobsJob            │
└──────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────┐
│  ImportJobsJob                        │
│  - Parse job data                     │
│  - Check duplicates                   │
│  - Store to database                  │
│  - Dispatch EvaluateJobsJob per job   │
└──────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────┐
│  EvaluateJobsJob                      │
│  - Get AI score                       │
│  - Store result                       │
│  - Check threshold                    │
│  - Queue notification if qualified    │
└──────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────┐
│  SendNotificationJob                 │
│  - Format message                    │
│  - Send via WhatsApp                 │
│  - Update status                     │
└──────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────┐
│  CleanupJob (Every 10 minutes)       │
│  - Delete old jobs (2h+)             │
│  - Delete old logs (7d+)             │
│  - Delete old notifications (7d+)     │
└──────────────────────────────────────┘
```

### Job Classes

#### RunCrawlerJob
```php
class RunCrawlerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [30, 60, 120];
    public $timeout = 120;
    
    public function handle(UpworkCrawlerService $crawler, LoggingService $logger): void
    {
        $session = CrawlerSession::start();
        
        try {
            $logger->crawlerStarted($session->session_id);
            
            $jobs = $crawler->crawl();
            
            dispatch(new ImportJobsJob($jobs, $session->session_id));
            
            $session->complete();
            $logger->crawlerFinished($session->session_id, count($jobs));
            
        } catch (Exception $e) {
            $session->fail($e->getMessage());
            $logger->crawlerError($session->session_id, $e);
            throw $e;
        }
    }
}
```

#### ImportJobsJob
```php
class ImportJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 60;
    
    public function __construct(
        protected array $rawJobs,
        protected string $sessionId
    ) {}
    
    public function handle(
        JobParserService $parser,
        DuplicateCheckerService $duplicateChecker,
        JobStorageService $storage,
        LoggingService $logger
    ): void {
        $newCount = 0;
        $duplicateCount = 0;
        
        foreach ($this->rawJobs as $rawJob) {
            try {
                $jobDTO = $parser->parse($rawJob);
                
                if ($duplicateChecker->isDuplicate($jobDTO)) {
                    $duplicateCount++;
                    continue;
                }
                
                $job = $storage->store($jobDTO);
                dispatch(new EvaluateJobsJob($job->id));
                
                $newCount++;
                
            } catch (Exception $e) {
                $logger->log('warning', "Failed to import job: {$e->getMessage()}", [
                    'job_data' => $rawJob,
                ]);
            }
        }
        
        $logger->log('info', "Import completed: {$newCount} new, {$duplicateCount} duplicates", [
            'session_id' => $this->sessionId,
            'new' => $newCount,
            'duplicates' => $duplicateCount,
        ]);
    }
}
```

#### EvaluateJobsJob
```php
class EvaluateJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 30;
    
    public function __construct(protected int $jobId) {}
    
    public function handle(
        AIEvaluationService $ai,
        FilterService $filter,
        NotificationService $notification,
        LoggingService $logger
    ): void {
        $job = Job::findOrFail($this->jobId);
        $job->update(['status' => 'scoring']);
        
        try {
            $score = $ai->evaluate($job);
            
            $aiScore = $job->aiScore()->create([
                'score' => $score->score,
                'reasoning' => $score->reason,
                'technologies' => $score->technologies,
                'red_flags' => $score->redFlags,
                'estimated_hours' => $score->estimatedHours,
                'estimated_price' => $score->estimatedPrice,
                'recommendation' => $score->recommendation,
            ]);
            
            if ($filter->shouldNotify($score, $job)) {
                $notification->queue($job, $score);
                $job->update(['status' => 'notified']);
            } else {
                $job->update(['status' => 'scored']);
            }
            
        } catch (AIServiceException $e) {
            $job->update(['status' => 'skipped']);
            $logger->log('error', "AI scoring failed: {$e->getMessage()}", [
                'job_id' => $this->jobId,
            ]);
        }
    }
}
```

#### SendNotificationJob
```php
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 20;
    
    public function __construct(
        protected int $jobId,
        protected int $aiScoreId
    ) {}
    
    public function handle(
        WhatsAppService $whatsapp,
        LoggingService $logger
    ): void {
        $job = Job::findOrFail($this->jobId);
        $aiScore = JobAiScore::findOrFail($this->aiScoreId);
        
        $notification = Notification::create([
            'job_id' => $this->jobId,
            'ai_score_id' => $this->aiScoreId,
            'phone_number' => config('whatsapp.phone_number'),
            'message_content' => $whatsapp->formatMessage($job, $aiScore),
            'status' => 'processing',
        ]);
        
        try {
            $result = $whatsapp->send($notification->message_content);
            
            $notification->update([
                'status' => 'sent',
                'whatsapp_message_id' => $result['message_id'],
                'sent_at' => now(),
            ]);
            
            $logger->log('info', 'WhatsApp notification sent', [
                'job_id' => $this->jobId,
                'message_id' => $result['message_id'],
            ]);
            
        } catch (WhatsAppException $e) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $notification->retry_count + 1,
            ]);
            
            $logger->log('error', "WhatsApp send failed: {$e->getMessage()}", [
                'job_id' => $this->jobId,
                'attempt' => $this->attempts(),
            ]);
            
            if ($this->attempts() < $this->tries) {
                $this->release(60); // Retry in 1 minute
            }
        }
    }
}
```

#### CleanupJob
```php
class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 300; // 5 minutes
    
    public function handle(LoggingService $logger): void
    {
        $deleted = [
            'jobs' => 0,
            'notifications' => 0,
            'crawler_logs' => 0,
            'debug_logs' => 0,
        ];
        
        // Delete jobs older than 2 hours
        $deleted['jobs'] = Job::where('created_at', '<', now()->subHours(2))
            ->where('status', '!=', 'notified')
            ->delete();
        
        // Delete notifications older than 7 days
        $deleted['notifications'] = Notification::where('created_at', '<', now()->subDays(7))
            ->delete();
        
        // Delete crawler logs older than 7 days
        $deleted['crawler_logs'] = CrawlerLog::where('created_at', '<', now()->subDays(7))
            ->delete();
        
        // Delete debug logs older than 1 day
        $deleted['debug_logs'] = SystemLog::where('type', 'debug')
            ->where('created_at', '<', now()->subDay())
            ->delete();
        
        $logger->log('info', 'Cleanup completed', [
            'deleted' => $deleted,
        ]);
    }
}
```

---

## Docker Configuration

### Docker Compose

```yaml
version: '3.8'

services:
  # PHP-FPM Application
  app:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: upwork-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    networks:
      - upwork-network
    depends_on:
      - mysql
      - redis

  # Nginx Web Server
  nginx:
    image: nginx:alpine
    container_name: upwork-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - upwork-network
    depends_on:
      - app

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: upwork-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - upwork-network

  # Redis Queue & Cache
  redis:
    image: redis:alpine
    container_name: upwork-redis
    restart: unless-stopped
    command: redis-server --appendonly yes
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - upwork-network

  # Node.js Crawler
  crawler:
    build:
      context: ./crawler
      dockerfile: Dockerfile
    container_name: upwork-crawler
    restart: unless-stopped
    volumes:
      - ./crawler:/app
      - crawler-data:/app/storage
    environment:
      - NODE_ENV=production
    networks:
      - upwork-network

networks:
  upwork-network:
    driver: bridge

volumes:
  mysql-data:
  crawler-data:
  redis-data:
```

### PHP Dockerfile

```dockerfile
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

EXPOSE 9000

CMD ["php-fpm"]
```

### Supervisor Configuration

```ini
[supervisord]
nodaemon=true
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker.log
stopwaitsecs=3600

[program:laravel-scheduler]
command=php /var/www/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/scheduler.log
```

---

## 8-Milestone Implementation Plan

### Milestone 1: Architecture & Database Design
**Duration**: 1-2 days
**Deliverables**:
- Complete database migrations (9 tables)
- Database seeds (admin user, default settings)
- Repository interfaces
- Service contracts
- DTOs
- Architecture documentation

**Tasks**:
1. Create all 9 migration files
2. Create seeder classes
3. Define repository interfaces
4. Define service contracts
5. Create DTO classes
6. Run migrations and verify
7. Test database relationships

**Acceptance Criteria**:
- ✅ All migrations run successfully
- ✅ Database relationships work correctly
- ✅ Seed data populates correctly
- ✅ All interfaces and contracts defined

---

### Milestone 2: Laravel Project Scaffolding
**Duration**: 1-2 days
**Deliverables**:
- Laravel 12 project initialized
- Authentication configured (Laravel Breeze)
- Queue system configured (Redis)
- Basic dashboard layout
- Settings service
- Logging service

**Tasks**:
1. Install Laravel 12
2. Configure authentication with Breeze
3. Set up Redis queue connection
4. Create basic dashboard layout (Blade)
5. Implement SettingsService
6. Implement LoggingService
7. Create base controller
8. Set up route middleware

**Acceptance Criteria**:
- ✅ Login/logout works
- ✅ Queue system processes jobs
- ✅ Dashboard accessible after login
- ✅ Settings can be stored/retrieved
- ✅ Logging writes to database

---

### Milestone 3: Playwright Crawler
**Duration**: 2-3 days
**Deliverables**:
- Playwright crawler (Node.js)
- Authentication service
- Job extraction logic
- Parser service
- Encrypted storage handling

**Tasks**:
1. Initialize Node.js project in crawler/ directory
2. Install Playwright and dependencies
3. Implement login flow with storage.json
4. Implement job scraping from Upwork
5. Create parser for raw HTML → JSON
6. Add encryption for storage.json
7. Test crawler independently
8. Create Node.js service bridge

**Acceptance Criteria**:
- ✅ Manual login works and saves session
- ✅ Crawler scrapes all visible jobs
- ✅ Parsed JSON matches schema
- ✅ storage.json encrypted at rest
- ✅ C completes in <5 seconds

---

### Milestone 4: Import & Duplicate Detection
**Duration**: 2-3 days
**Deliverables**:
- Import service
- Duplicate checker
- Job storage service
- ImportJobsJob
- Repository implementation
- Unit tests

**Tasks**:
1. Implement JobRepository
2. Implement DuplicateCheckerService
3. Implement JobParserService (PHP side)
4. Implement JobStorageService
5. Create ImportJobsJob
6. Add comprehensive logging
7. Write unit tests (80%+ coverage)
8. Test duplicate detection edge cases

**Acceptance Criteria**:
- ✅ Jobs import correctly
- ✅ Duplicate detection works with job_id
- ✅ Fingerprint detection works as fallback
- ✅ All edge cases handled
- ✅ Unit tests pass

---

### Milestone 5: AI Scoring Service
**Duration**: 2-3 days
**Deliverables**:
- OpenAI integration
- AI evaluation service
- Prompt builder
- EvaluateJobsJob
- Filter service
- Unit tests

**Tasks**:
1. Implement OpenAIClient
2. Implement PromptBuilderService
3. Implement AIEvaluationService
4. Implement FilterService
5. Create EvaluateJobsJob
6. Add error handling & retries
7. Write unit tests
8. Test with real OpenAI API

**Acceptance Criteria**:
- ✅ OpenAI API calls work
- ✅ AI returns valid scores
- ✅ Prompt customization works
- ✅ Filters apply correctly
- ✅ Scoring completes in <3 seconds
- ✅ Unit tests pass

---

### Milestone 6: WhatsApp Notification Service
**Duration**: 2-3 days
**Deliverables**:
- WhatsApp Cloud API client
- Notification service
- Message formatting
- SendNotificationJob
- Rate limiting (10/min)
- Unit tests

**Tasks**:
1. Implement WhatsAppClient
2. Implement NotificationService
3. Create message templates
4. Implement rate limiting
5. Create SendNotificationJob
6. Add error handling
7. Write unit tests
8. Test with real WhatsApp API

**Acceptance Criteria**:
- ✅ WhatsApp API sends messages
- ✅ Message formatting correct
- ✅ Rate limiting works
- ✅ Failed sends retry correctly
- ✅ Notification completes in <5 seconds
- ✅ Unit tests pass

---

### Milestone 7: Dashboard UI
**Duration**: 3-4 days
**Deliverables**:
- Complete dashboard pages
- Real-time updates
- Charts & analytics
- Settings editor
- Responsive design
- Feature tests

**Tasks**:
1. Create dashboard layout
2. Implement jobs list page
3. Implement notifications page
4. Implement settings page
5. Implement logs page
6. Add real-time updates (polling/WebSocket)
7. Create analytics charts
8. Make responsive
9. Write feature tests

**Acceptance Criteria**:
- ✅ All pages render correctly
- ✅ Real-time updates work
- ✅ Charts display data
- ✅ Settings save correctly
- ✅ Mobile responsive
- ✅ Feature tests pass

---

### Milestone 8: Testing, Docker, Deployment
**Duration**: 3-4 days
**Deliverables**:
- Complete test suite (80%+ coverage)
- Docker environment
- Supervisor configuration
- Deployment scripts
- Installation guide
- API documentation

**Tasks**:
1. Complete unit tests
2. Complete integration tests
3. Complete feature tests
4. Create Docker setup
5. Configure Supervisor
6. Write deployment guide
7. Create installation documentation
8. Write API documentation
9. Create ER diagram
10. Create sequence diagrams

**Acceptance Criteria**:
- ✅ 80%+ code coverage
- ✅ All tests pass
- ✅ Docker compose works
- ✅ Supervisor keeps processes running
- ✅ Documentation complete
- ✅ Production deployment successful

---

## System Requirements & Decisions

### Confirmed Decisions

#### Authentication & Security
| Decision | Choice |
|----------|--------|
| Upwork 2FA | No 2FA enabled (currently) |
| Dashboard Auth | Full Laravel auth (username/password) |
| storage.json | Encrypt at rest |

#### Crawler Behavior
| Decision | Choice |
|----------|--------|
| Jobs Per Run | All visible jobs (50+) |
| Infinite Scroll | Only initial page |
| HTML Changes | Resilient selectors (data attributes) |

#### Data & Business Logic
| Decision | Choice |
|----------|--------|
| Duplicate Detection | Both methods (job_id AND title+client+time_posted) |
| Budget Filtering | No budget filter now, configurable later |
| Location in Scoring | User will decide |
| Filter Profiles | User will decide |

#### Infrastructure & Performance
| Decision | Choice |
|----------|--------|
| WhatsApp Rate Limit | 10/min, queue rest |
| Memory/CPU Alerts | Alert admin only |

#### Data Management
| Decision | Choice |
|----------|--------|
| Job Retention | 2 hours |
| Notification Logs | User will specify duration |
| Crawler Session Logs | User will specify duration |
| Cleanup Schedule | Every 10 minutes |
| Soft Deletes | Yes |

#### Testing & Development
| Decision | Choice |
|----------|--------|
| Test Coverage | 80%+ critical, basic dashboard |
| CI/CD vs Manual | User will decide |
| Docker | Yes, local mirrors production |
| Environment Configs | .env + settings table |

#### Edge Cases
| Decision | Choice |
|----------|--------|
| Browser Crashes | Immediate retry |
| Upwork Blocked | Wait until unblocked |
| OpenAI Down | User will decide (Skip/Fallback/Stop) |
| WhatsApp Down | User will decide (Queue/Stop) |
| Redis Down | User will decide (Stop/DB fallback) |
| MySQL Integrity | Use transactions, recover partial |
| Concurrent Runs | User will decide (Skip/Wait/Kill) |

### Pending Decisions

Please provide the following decisions:

1. **Location in AI Scoring**: Should AI factor in client location?
2. **API Failure Handling**:
   - OpenAI down: Skip scoring / Use fallback / Stop?
   - WhatsApp down: Queue / Stop notifications?
   - Redis down: Stop / Use DB queue fallback?
3. **Concurrent Runs**: Skip / Wait / Kill previous?
4. **Log Retention**:
   - Notification logs: How many days?
   - Session logs: How many days?
5. **Slow Crawler Action**: Continue / Auto-increase interval / Stop?
6. **CI/CD**: Auto / Manual / Both?
7. **Migration Strategy**: Git branches / Manual / CI/CD?

---

## Required Credentials

Please provide the following to begin implementation:

### Database
```env
DB_DATABASE=upwork_agent
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_HOST=127.0.0.1
DB_PORT=3306
```

### OpenAI API
```env
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```

### WhatsApp Cloud API
```env
WHATSAPP_PHONE_ID=your_phone_id_from_meta
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER=+1234567890
WHATSAPP_BUSINESS_ACCOUNT_ID=your_ba_id
```

### Admin User
```env
ADMIN_NAME=Your Name
ADMIN_EMAIL=your@email.com
ADMIN_PASSWORD=your_secure_password
```

### Redis
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Application
```env
APP_NAME="Upwork Job Agent"
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=false
APP_KEY=base64:...
```

---

## Performance Targets

| Component | Target | Monitoring |
|-----------|--------|------------|
| Crawler | <5 seconds | Timer per run |
| AI Scoring | <3 seconds | API timing |
| WhatsApp Send | <5 seconds | Queue timing |
| Memory Usage | <300 MB | Continuous |
| CPU Usage | <20% | Continuous |

---

## Security Checklist

- [ ] No passwords stored in code
- [ ] storage.json encrypted at rest
- [ ] No cookies stored publicly
- [ ] API keys never logged
- [ ] .env not web-accessible
- [ ] All inputs validated
- [ ] HTML escaped in dashboard
- [ ] Rate limiting on APIs
- [ ] Sensitive settings encrypted
- [ ] CSRF protection enabled
- [ ] Security headers configured

---

## Success Criteria

The system will be considered successful when:

1. ✅ Crawls Upwork every 30-60 seconds automatically
2. ✅ Detects new jobs without duplicates
3. ✅ Scores jobs using AI based on user skills
4. ✅ Sends high-scoring jobs to WhatsApp within seconds
5. ✅ Dashboard allows managing all aspects
6. ✅ Runs continuously for months without intervention
7. ✅ Handles failures gracefully and auto-recovers
8. ✅ All performance targets met
9. ✅ 80%+ test coverage achieved
10. ✅ Production deployment stable

---

*This implementation plan is a living document. Update as decisions are made during development.*
