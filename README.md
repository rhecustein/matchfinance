# 🏦 MatchFinance - Bank Statement Transaction Matching System

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**Automatic Bank Statement Processing & Transaction Categorization System**

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [Documentation](#-documentation) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Features](#-features)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Structure](#-database-structure)
- [Usage Guide](#-usage-guide)
- [API Integration](#-api-integration)
- [Architecture](#-architecture)
- [Screenshots](#-screenshots)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Contributing](#-contributing)
- [License](#-license)
- [Credits](#-credits)

---

## 🎯 About

**MatchFinance** is a powerful Laravel-based system designed to automate the processing of bank statements and intelligently categorize transactions using keyword matching algorithms. This system helps businesses and individuals manage their financial data efficiently by automatically parsing PDF bank statements and categorizing transactions based on customizable rules.

### 🌟 Key Highlights

- 🤖 **Automatic OCR Processing** - Upload PDF bank statements and get structured data
- 🎯 **Smart Matching Algorithm** - Intelligent transaction categorization using keywords
- 🏷️ **Flexible Categorization** - Multi-level categorization (Type → Category → Sub Category → Keywords)
- 📊 **Rich Analytics** - Comprehensive dashboard with transaction insights
- ✅ **Verification Workflow** - Manual review and approval system
- 🔄 **Re-matching Capability** - Update keywords and re-process transactions
- 🏦 **Multi-Bank Support** - Support for 6 major Indonesian banks (BCA, Mandiri, BNI, BRI, BTN, CIMB)

---

## ✨ Features

### 🔐 Authentication & Authorization
- User registration and login (Laravel Breeze)
- Email verification
- Profile management
- Role-based access control ready

### 📁 Master Data Management
- **Banks Management** - Manage supported banks with logos
- **Types** - Define transaction types (Outlet, Transfer, Payment, etc.)
- **Categories** - Organize by category with color coding
- **Sub Categories** - Detailed subcategorization with priority levels
- **Keywords** - Define matching rules with regex support

### 📄 Bank Statement Processing
- PDF upload with validation
- External OCR API integration
- Processing status tracking (Pending → Processing → Completed/Failed)
- Download original statements
- Bulk processing support

### 💳 Transaction Management
- Automatic transaction parsing from OCR
- Smart keyword matching with confidence scores
- Manual categorization override
- Transaction verification workflow
- Bulk verification operations
- Re-matching capability
- Advanced filtering (status, date range, category, bank)

### 📊 Analytics & Reporting
- Real-time dashboard statistics
- Transaction trends by category and bank
- Daily transaction charts
- Top keywords usage
- Matching accuracy metrics
- Verification rate tracking

### 🎨 User Interface
- Modern, responsive design with Tailwind CSS
- Dark/Light mode ready
- Mobile-friendly interface
- Interactive charts and graphs
- Intuitive CRUD operations

---

## 💻 System Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **Laravel**: 12.x
- **MySQL**: 8.0 or higher (or MariaDB 10.11+)
- **Composer**: 2.x
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher

### Recommended Server Specs
- **RAM**: 2GB minimum, 4GB recommended
- **Disk Space**: 10GB minimum
- **CPU**: 2 cores minimum

### PHP Extensions Required
```
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick (for image processing)
```

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/matchfinance.git
cd matchfinance
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file:

```env
APP_NAME="MatchFinance"
APP_ENV=local
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=matchfinance
DB_USERNAME=root
DB_PASSWORD=

# OCR Service Configuration
OCR_API_URL=https://your-ocr-api.com/api/process
OCR_API_KEY=your_api_key_here
OCR_API_TIMEOUT=120
OCR_MOCK_MODE=true
```

### 5. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE matchfinance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate

# Seed master data
php artisan db:seed
```

### 6. Storage Setup

```bash
# Create storage link
php artisan storage:link

# Create required directories
mkdir -p storage/app/private/bank-statements
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 7. Compile Assets

```bash
# Development mode (with watch)
npm run dev

# Production build
npm run build
```

### 8. Run the Application

```bash
# Using Laravel's built-in server
php artisan serve

# Access the application at: http://localhost:8000
```

### 9. Create Admin User (Optional)

```bash
php artisan tinker
>>> \App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@matchfinance.com',
...     'password' => bcrypt('password123'),
...     'email_verified_at' => now()
... ]);
```

---

## ⚙️ Configuration

### OCR Service Setup

MatchFinance requires an external OCR API for processing PDF bank statements. Configure in `.env`:

```env
OCR_API_URL=https://your-ocr-api.com/api/process
OCR_API_KEY=your_secret_api_key
OCR_API_TIMEOUT=120
OCR_MOCK_MODE=false  # Set to true for testing without real API
```

#### Expected OCR Response Format

```json
{
    "success": true,
    "message": "OCR processed successfully",
    "data": {
        "period_start": "2024-01-01",
        "period_end": "2024-01-31",
        "transactions": [
            {
                "date": "2024-01-05",
                "description": "APOTEK KIMIA FARMA QR",
                "amount": -50000.00,
                "balance": 1000000.00,
                "type": "debit"
            }
        ]
    }
}
```

### Cache Configuration

For better performance, configure Redis:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 🗄️ Database Structure

### Entity Relationship Diagram

```
Banks (1) ──< (N) BankStatements
                      │
                      └──< (N) StatementTransactions
                                    │
                                    ├──< (1) Type
                                    ├──< (1) Category
                                    ├──< (1) SubCategory
                                    ├──< (1) Keyword (matched)
                                    └──< (N) MatchingLogs

Types (1) ──< (N) Categories (1) ──< (N) SubCategories (1) ──< (N) Keywords
```

### Key Tables

#### Master Data Tables
1. **banks** - Supported banks
2. **types** - Transaction types
3. **categories** - Transaction categories
4. **sub_categories** - Sub-categories with priority
5. **keywords** - Matching keywords with regex support

#### Operational Tables
6. **bank_statements** - Uploaded statements
7. **statement_transactions** - Parsed transactions
8. **matching_logs** - Audit trail for matching

### Sample Data Structure

After seeding, you'll have:
- 6 Banks (Mandiri, BCA, BNI, BRI, BTN, CIMB)
- 5 Types (Outlet, Transaction, Transfer, Payment, E-Commerce)
- 13 Categories (Pharmacy, Minimarket, Restaurant, etc.)
- 33+ Sub Categories (Kimia Farma, Indomaret, GoPay, etc.)
- 35+ Keywords for matching

---

## 📖 Usage Guide

### 1. Upload Bank Statement

```
Dashboard → Bank Statements → Upload New Statement
```

1. Select the bank
2. Upload PDF file (max 10MB)
3. Wait for OCR processing
4. Review parsed transactions

### 2. Process Matching

```
Bank Statement Detail → Process Matching
```

The system will automatically:
1. Loop through all transactions
2. Match descriptions against keywords
3. Calculate confidence scores
4. Assign categories based on best match
5. Log matching history

### 3. Review & Verify

```
Transactions → Filter by status
```

- **Matched** - Automatically categorized
- **Unmatched** - Need manual categorization
- **Low Confidence** - Need review (<80% confidence)
- **Verified** - Approved by user

### 4. Manual Categorization

For unmatched transactions:
1. Click on transaction detail
2. Select appropriate sub-category
3. Add notes if needed
4. Save changes

### 5. Bulk Operations

Select multiple transactions and:
- Bulk verify
- Bulk export
- Bulk re-match

### 6. Manage Keywords

Add new keywords to improve matching:
1. Go to Keywords → Create New
2. Select sub-category
3. Enter keyword (supports regex)
4. Set priority (1-10, higher = checked first)
5. Enable/disable as needed

---

## 🔌 API Integration

### OCR Service Integration

Create your OCR service that accepts PDF and returns JSON:

**Endpoint**: `POST /api/process`

**Request**:
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@statement.pdf" \
  https://your-ocr-api.com/api/process
```

**Response**:
```json
{
    "success": true,
    "data": {
        "period_start": "2024-01-01",
        "period_end": "2024-01-31",
        "transactions": [...]
    }
}
```

### Internal API Endpoints

**Get Categories by Type**:
```javascript
GET /categories/by-type/{typeId}
```

**Get Sub Categories by Category**:
```javascript
GET /sub-categories/by-category/{categoryId}
```

---

## 🏗️ Architecture

### Directory Structure

```
matchfinance/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── BankController.php
│   │       ├── BankStatementController.php
│   │       ├── CategoryController.php
│   │       ├── DashboardController.php
│   │       ├── KeywordController.php
│   │       ├── SubCategoryController.php
│   │       ├── TransactionController.php
│   │       └── TypeController.php
│   ├── Models/
│   │   ├── Bank.php
│   │   ├── BankStatement.php
│   │   ├── Category.php
│   │   ├── Keyword.php
│   │   ├── MatchingLog.php
│   │   ├── StatementTransaction.php
│   │   ├── SubCategory.php
│   │   └── Type.php
│   └── Services/
│       ├── OcrService.php
│       └── TransactionMatchingService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   └── js/
├── routes/
│   └── web.php
└── storage/
    └── app/
        └── private/
            └── bank-statements/
```

### Service Layer Architecture

**OcrService**: Handles PDF processing and API communication
- `processStatement()` - Send PDF to OCR API
- `createTransactionsFromOcr()` - Parse OCR response
- `getMockOcrResponse()` - Testing mode

**TransactionMatchingService**: Core matching algorithm
- `matchTransaction()` - Find best matching keyword
- `calculateConfidenceScore()` - Score calculation (0-100)
- `processStatementTransactions()` - Batch processing
- `rematchTransaction()` - Re-process single transaction

### Matching Algorithm

```php
Priority-based matching:
1. Load all active keywords (cached)
2. Sort by priority (10 → 1)
3. For each keyword:
   - Check regex/string match
   - Calculate confidence score
   - Keep best match
4. Assign category hierarchy
5. Log matching details
```

**Confidence Score Calculation**:
- Base score: `(priority × 6) + 40` (40-100 range)
- Exact match: 100%
- Match ratio bonus: +10 max
- Regex penalty: -5

---

## 🖼️ Screenshots

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)
*Real-time statistics and analytics*

### Bank Statement Upload
![Upload](docs/screenshots/upload.png)
*Simple drag-and-drop interface*

### Transaction Matching
![Matching](docs/screenshots/matching.png)
*Automatic categorization with confidence scores*

### Master Data Management
![Master Data](docs/screenshots/master-data.png)
*Comprehensive CRUD operations*

---

## 🧪 Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TransactionMatchingTest.php

# Run with coverage
php artisan test --coverage
```

### Manual Testing

1. **Test OCR in Mock Mode**:
```env
OCR_MOCK_MODE=true
```

2. **Upload Sample Statement**:
```bash
# Use sample PDF in tests/fixtures/sample_statement.pdf
```

3. **Test Matching**:
```bash
php artisan tinker
>>> $service = app(\App\Services\TransactionMatchingService::class);
>>> $service->matchTransaction('APOTEK KIMIA FARMA QR');
```

---

## 🚢 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up OCR API credentials
- [ ] Configure Redis for caching
- [ ] Set up queue workers
- [ ] Configure backup system
- [ ] Set up monitoring (Laravel Telescope)
- [ ] Enable HTTPS
- [ ] Configure CORS if needed

### Queue Workers

```bash
# Start queue worker
php artisan queue:work --tries=3

# With Supervisor (recommended)
[program:matchfinance-worker]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
user=www-data
autostart=true
autorestart=true
```

### Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Write meaningful commit messages
- Add tests for new features
- Update documentation

### Reporting Issues

Use GitHub Issues to report bugs or request features:
- Bug reports: Use bug template
- Feature requests: Use feature template
- Include Laravel version and PHP version

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2024 MatchFinance

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 👥 Credits

### Built With

- [Laravel](https://laravel.com) - PHP Framework
- [Tailwind CSS](https://tailwindcss.com) - CSS Framework
- [Alpine.js](https://alpinejs.dev) - JavaScript Framework
- [Chart.js](https://chartjs.org) - Data Visualization

### Supported Banks

- 🏦 Bank Mandiri
- 🏦 Bank Central Asia (BCA)
- 🏦 Bank Negara Indonesia (BNI)
- 🏦 Bank Rakyat Indonesia (BRI)
- 🏦 Bank Tabungan Negara (BTN)
- 🏦 CIMB Niaga

---

## 📞 Support

### Documentation

- [Full Documentation](docs/README.md)
- [API Reference](docs/API.md)
- [Database Schema](docs/DATABASE.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

### Community

- GitHub Issues: [Report a bug](https://github.com/yourusername/matchfinance/issues)
- Discussions: [Ask questions](https://github.com/yourusername/matchfinance/discussions)

### Contact

- Email: support@matchfinance.com
- Website: https://matchfinance.com

---

## 🗺️ Roadmap

### Version 2.0 (Planned)

- [ ] Machine Learning-based categorization
- [ ] Multi-language support
- [ ] Mobile app (React Native)
- [ ] Bank API integration (direct data fetch)
- [ ] Advanced analytics dashboard
- [ ] Export to accounting software (Xero, QuickBooks)
- [ ] Receipt image matching
- [ ] Budgeting module
- [ ] Multi-tenant support

---

## 🌟 Star History

[![Star History Chart](https://api.star-history.com/svg?repos=yourusername/matchfinance&type=Date)](https://star-history.com/#yourusername/matchfinance&Date)

---

<div align="center">

**Made with ❤️ by the MatchFinance Team**

[⬆ Back to Top](#-matchfinance---bank-statement-transaction-matching-system)

</div>