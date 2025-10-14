<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KeywordPatternSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Pattern Groups
        $groups = [
            [
                'code' => 'GROUP_EWALLET',
                'name' => 'E-Wallet Services',
                'description' => 'Digital wallet and payment services',
                'is_system' => true,
                'sort_order' => 1
            ],
            [
                'code' => 'GROUP_PAYMENT_METHOD',
                'name' => 'Payment Methods',
                'description' => 'Various payment method indicators',
                'is_system' => true,
                'sort_order' => 2
            ],
            [
                'code' => 'GROUP_BANK',
                'name' => 'Bank Indicators',
                'description' => 'Bank transaction types and channels',
                'is_system' => true,
                'sort_order' => 3
            ],
            [
                'code' => 'GROUP_ECOMMERCE',
                'name' => 'E-Commerce Platforms',
                'description' => 'Online shopping platforms',
                'is_system' => true,
                'sort_order' => 4
            ],
            [
                'code' => 'GROUP_TRANSPORT',
                'name' => 'Transportation',
                'description' => 'Transportation and ride-hailing services',
                'is_system' => true,
                'sort_order' => 5
            ],
            [
                'code' => 'GROUP_FOOD',
                'name' => 'Food & Beverage',
                'description' => 'Food delivery and restaurant chains',
                'is_system' => true,
                'sort_order' => 6
            ],
            [
                'code' => 'GROUP_UTILITY',
                'name' => 'Utilities',
                'description' => 'Utility payments and services',
                'is_system' => true,
                'sort_order' => 7
            ],
            [
                'code' => 'GROUP_RETAIL',
                'name' => 'Retail Stores',
                'description' => 'Retail and convenience stores',
                'is_system' => true,
                'sort_order' => 8
            ]
        ];

        foreach ($groups as $group) {
            DB::table('pattern_groups')->insert([
                'uuid' => Str::uuid(),
                'company_id' => null, // Global
                'code' => $group['code'],
                'name' => $group['name'],
                'description' => $group['description'],
                'is_system' => $group['is_system'],
                'sort_order' => $group['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 2. Create Keyword Patterns
        $patterns = [
            // E-Wallet Patterns
            ['code' => 'EWALLET_OVO', 'pattern' => 'OVO', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_GOPAY', 'pattern' => 'GOPAY', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_DANA', 'pattern' => 'DANA', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_SHOPEEPAY', 'pattern' => 'SHOPEEPAY', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_LINKAJA', 'pattern' => 'LINKAJA', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_JENIUS', 'pattern' => 'JENIUS', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_SAKUKU', 'pattern' => 'SAKUKU', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_ISAKU', 'pattern' => 'I.SAKU', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_DOKU', 'pattern' => 'DOKU', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            ['code' => 'EWALLET_PAYTREN', 'pattern' => 'PAYTREN', 'group' => 'GROUP_EWALLET', 'category_hint' => 'E-Wallet'],
            
            // Payment Method Patterns
            ['code' => 'PAY_QRIS', 'pattern' => 'QRIS', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Digital Payment'],
            ['code' => 'PAY_QR', 'pattern' => 'QR', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Digital Payment'],
            ['code' => 'PAY_EDC', 'pattern' => 'EDC', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Card Payment'],
            ['code' => 'PAY_ATM', 'pattern' => 'ATM', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'ATM Transaction'],
            ['code' => 'PAY_TRANSFER', 'pattern' => 'TRANSFER', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Transfer'],
            ['code' => 'PAY_TRSF', 'pattern' => 'TRSF', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Transfer'],
            ['code' => 'PAY_DEBIT', 'pattern' => 'DEBIT', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Debit'],
            ['code' => 'PAY_CREDIT', 'pattern' => 'CREDIT', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Credit'],
            ['code' => 'PAY_TOPUP', 'pattern' => 'TOP UP', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Top Up'],
            ['code' => 'PAY_TOPUP2', 'pattern' => 'TOPUP', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Top Up'],
            ['code' => 'PAY_VA', 'pattern' => 'VIRTUAL ACCOUNT', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Virtual Account'],
            ['code' => 'PAY_VA2', 'pattern' => 'VA', 'group' => 'GROUP_PAYMENT_METHOD', 'category_hint' => 'Virtual Account'],
            
            // Bank Transaction Types
            ['code' => 'BANK_TARIK', 'pattern' => 'TARIK', 'group' => 'GROUP_BANK', 'category_hint' => 'Cash Withdrawal'],
            ['code' => 'BANK_SETOR', 'pattern' => 'SETOR', 'group' => 'GROUP_BANK', 'category_hint' => 'Cash Deposit'],
            ['code' => 'BANK_KLIRING', 'pattern' => 'KLIRING', 'group' => 'GROUP_BANK', 'category_hint' => 'Clearing'],
            ['code' => 'BANK_RTGS', 'pattern' => 'RTGS', 'group' => 'GROUP_BANK', 'category_hint' => 'RTGS Transfer'],
            ['code' => 'BANK_GIRO', 'pattern' => 'GIRO', 'group' => 'GROUP_BANK', 'category_hint' => 'Giro'],
            ['code' => 'BANK_CDM', 'pattern' => 'CDM', 'group' => 'GROUP_BANK', 'category_hint' => 'Cash Deposit Machine'],
            ['code' => 'BANK_CRM', 'pattern' => 'CRM', 'group' => 'GROUP_BANK', 'category_hint' => 'Cash Recycle Machine'],
            ['code' => 'BANK_INTEREST', 'pattern' => 'BUNGA', 'group' => 'GROUP_BANK', 'category_hint' => 'Interest'],
            ['code' => 'BANK_INTEREST2', 'pattern' => 'INTEREST', 'group' => 'GROUP_BANK', 'category_hint' => 'Interest'],
            ['code' => 'BANK_FEE', 'pattern' => 'BIAYA ADM', 'group' => 'GROUP_BANK', 'category_hint' => 'Bank Fee'],
            ['code' => 'BANK_FEE2', 'pattern' => 'ADMIN FEE', 'group' => 'GROUP_BANK', 'category_hint' => 'Bank Fee'],
            
            // E-Commerce Platforms
            ['code' => 'ECOM_TOKOPEDIA', 'pattern' => 'TOKOPEDIA', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_SHOPEE', 'pattern' => 'SHOPEE', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_LAZADA', 'pattern' => 'LAZADA', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_BUKALAPAK', 'pattern' => 'BUKALAPAK', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_BLIBLI', 'pattern' => 'BLIBLI', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_JD', 'pattern' => 'JD.ID', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_ZALORA', 'pattern' => 'ZALORA', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            ['code' => 'ECOM_AMAZON', 'pattern' => 'AMAZON', 'group' => 'GROUP_ECOMMERCE', 'category_hint' => 'E-Commerce'],
            
            // Transportation
            ['code' => 'TRANS_GRAB', 'pattern' => 'GRAB', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Transportation'],
            ['code' => 'TRANS_GOJEK', 'pattern' => 'GOJEK', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Transportation'],
            ['code' => 'TRANS_UBER', 'pattern' => 'UBER', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Transportation'],
            ['code' => 'TRANS_MAXIM', 'pattern' => 'MAXIM', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Transportation'],
            ['code' => 'TRANS_BLUEBIRD', 'pattern' => 'BLUE BIRD', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Transportation'],
            ['code' => 'TRANS_MRT', 'pattern' => 'MRT', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Public Transport'],
            ['code' => 'TRANS_KRL', 'pattern' => 'KRL', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Public Transport'],
            ['code' => 'TRANS_TJ', 'pattern' => 'TRANSJAKARTA', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Public Transport'],
            ['code' => 'TRANS_LRT', 'pattern' => 'LRT', 'group' => 'GROUP_TRANSPORT', 'category_hint' => 'Public Transport'],
            
            // Food & Beverage
            ['code' => 'FOOD_GOFOOD', 'pattern' => 'GOFOOD', 'group' => 'GROUP_FOOD', 'category_hint' => 'Food Delivery'],
            ['code' => 'FOOD_GRABFOOD', 'pattern' => 'GRABFOOD', 'group' => 'GROUP_FOOD', 'category_hint' => 'Food Delivery'],
            ['code' => 'FOOD_SHOPEEFOOD', 'pattern' => 'SHOPEEFOOD', 'group' => 'GROUP_FOOD', 'category_hint' => 'Food Delivery'],
            ['code' => 'FOOD_MCDONALDS', 'pattern' => 'MCDONALD', 'group' => 'GROUP_FOOD', 'category_hint' => 'Fast Food'],
            ['code' => 'FOOD_KFC', 'pattern' => 'KFC', 'group' => 'GROUP_FOOD', 'category_hint' => 'Fast Food'],
            ['code' => 'FOOD_PHD', 'pattern' => 'PIZZA HUT', 'group' => 'GROUP_FOOD', 'category_hint' => 'Fast Food'],
            ['code' => 'FOOD_DOMINOS', 'pattern' => 'DOMINO', 'group' => 'GROUP_FOOD', 'category_hint' => 'Fast Food'],
            ['code' => 'FOOD_STARBUCKS', 'pattern' => 'STARBUCKS', 'group' => 'GROUP_FOOD', 'category_hint' => 'Coffee Shop'],
            ['code' => 'FOOD_COFFEE', 'pattern' => 'COFFEE', 'group' => 'GROUP_FOOD', 'category_hint' => 'Coffee Shop'],
            
            // Utilities
            ['code' => 'UTIL_PLN', 'pattern' => 'PLN', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Electricity'],
            ['code' => 'UTIL_PDAM', 'pattern' => 'PDAM', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Water'],
            ['code' => 'UTIL_TELKOM', 'pattern' => 'TELKOM', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Telecom'],
            ['code' => 'UTIL_INDIHOME', 'pattern' => 'INDIHOME', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Internet'],
            ['code' => 'UTIL_FIRSTMEDIA', 'pattern' => 'FIRST MEDIA', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Internet'],
            ['code' => 'UTIL_BIZNET', 'pattern' => 'BIZNET', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Internet'],
            ['code' => 'UTIL_XL', 'pattern' => 'XL AXIATA', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Mobile'],
            ['code' => 'UTIL_TELKOMSEL', 'pattern' => 'TELKOMSEL', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Mobile'],
            ['code' => 'UTIL_INDOSAT', 'pattern' => 'INDOSAT', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Mobile'],
            ['code' => 'UTIL_TRI', 'pattern' => 'TRI', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Mobile'],
            ['code' => 'UTIL_SMARTFREN', 'pattern' => 'SMARTFREN', 'group' => 'GROUP_UTILITY', 'category_hint' => 'Mobile'],
            
            // Retail Stores
            ['code' => 'RETAIL_INDOMARET', 'pattern' => 'INDOMARET', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_ALFAMART', 'pattern' => 'ALFAMART', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_ALFAMIDI', 'pattern' => 'ALFAMIDI', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_LAWSON', 'pattern' => 'LAWSON', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_CIRCLEK', 'pattern' => 'CIRCLE K', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_7ELEVEN', 'pattern' => '7-ELEVEN', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Convenience Store'],
            ['code' => 'RETAIL_SUPERINDO', 'pattern' => 'SUPERINDO', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Supermarket'],
            ['code' => 'RETAIL_HYPERMART', 'pattern' => 'HYPERMART', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Hypermarket'],
            ['code' => 'RETAIL_CARREFOUR', 'pattern' => 'CARREFOUR', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Hypermarket'],
            ['code' => 'RETAIL_GIANT', 'pattern' => 'GIANT', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Hypermarket'],
            ['code' => 'RETAIL_LOTTE', 'pattern' => 'LOTTE', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Hypermarket'],
            ['code' => 'RETAIL_ACE', 'pattern' => 'ACE HARDWARE', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Hardware Store'],
            ['code' => 'RETAIL_IKEA', 'pattern' => 'IKEA', 'group' => 'GROUP_RETAIL', 'category_hint' => 'Furniture'],
        ];

        // Get group IDs
        $groups = DB::table('pattern_groups')->pluck('id', 'code');

        foreach ($patterns as $pattern) {
            $patternId = DB::table('keyword_patterns')->insertGetId([
                'uuid' => Str::uuid(),
                'company_id' => null, // Global pattern
                'code' => $pattern['code'],
                'name' => ucwords(str_replace('_', ' ', $pattern['code'])),
                'pattern' => $pattern['pattern'],
                'pattern_type' => 'contains',
                'case_sensitive' => false,
                'extract_variant' => true,
                'category_hint' => $pattern['category_hint'],
                'priority' => 5,
                'is_active' => true,
                'is_system' => true,
                'match_count' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Link to group
            if (isset($groups[$pattern['group']])) {
                DB::table('pattern_group_items')->insert([
                    'pattern_group_id' => $groups[$pattern['group']],
                    'keyword_pattern_id' => $patternId,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 3. Create some common merchants
        $merchants = [
            [
                'code' => 'MERCHANT_APOTEK_KF',
                'name' => 'Apotek Kimia Farma',
                'display_name' => 'Kimia Farma',
                'type' => 'healthcare',
                'keywords' => ['APOTEK KIMIA FARMA', 'KIMIA FARMA', 'APOTEK K24'],
                'category_hint' => 'Healthcare'
            ],
            [
                'code' => 'MERCHANT_APOTEK_ROXY',
                'name' => 'Apotek Roxy',
                'display_name' => 'Roxy',
                'type' => 'healthcare',
                'keywords' => ['APOTEK ROXY', 'ROXY PHARMACY'],
                'category_hint' => 'Healthcare'
            ],
            [
                'code' => 'MERCHANT_GRAMEDIA',
                'name' => 'Gramedia',
                'display_name' => 'Gramedia',
                'type' => 'retail',
                'keywords' => ['GRAMEDIA', 'TOKO BUKU GRAMEDIA'],
                'category_hint' => 'Books & Stationery'
            ],
            [
                'code' => 'MERCHANT_UNIQLO',
                'name' => 'Uniqlo',
                'display_name' => 'Uniqlo',
                'type' => 'retail',
                'keywords' => ['UNIQLO'],
                'category_hint' => 'Fashion'
            ],
            [
                'code' => 'MERCHANT_ZARA',
                'name' => 'Zara',
                'display_name' => 'Zara',
                'type' => 'retail',
                'keywords' => ['ZARA'],
                'category_hint' => 'Fashion'
            ],
            [
                'code' => 'MERCHANT_H&M',
                'name' => 'H&M',
                'display_name' => 'H&M',
                'type' => 'retail',
                'keywords' => ['H&M', 'H & M', 'HM HENNES'],
                'category_hint' => 'Fashion'
            ],
        ];

        foreach ($merchants as $merchant) {
            DB::table('merchants')->insert([
                'uuid' => Str::uuid(),
                'company_id' => null, // Global
                'code' => $merchant['code'],
                'name' => $merchant['name'],
                'display_name' => $merchant['display_name'],
                'type' => $merchant['type'],
                'keywords' => json_encode($merchant['keywords']),
                'is_active' => true,
                'is_verified' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 4. Create some pattern rules examples
        $rules = [
            [
                'name' => 'E-Wallet Top Up',
                'description' => 'Identify e-wallet top up transactions',
                'rule_type' => 'all_match',
                'patterns' => ['TOPUP', 'OVO|GOPAY|DANA|SHOPEEPAY'],
                'category_hint' => 'E-Wallet Top Up',
                'confidence_boost' => 10
            ],
            [
                'name' => 'Food Delivery',
                'description' => 'Identify food delivery transactions',
                'rule_type' => 'any_match',
                'patterns' => ['GOFOOD', 'GRABFOOD', 'SHOPEEFOOD'],
                'category_hint' => 'Food Delivery',
                'confidence_boost' => 15
            ],
            [
                'name' => 'ATM Cash Withdrawal',
                'description' => 'Identify ATM withdrawal transactions',
                'rule_type' => 'all_match',
                'patterns' => ['ATM', 'TARIK|WITHDRAWAL'],
                'category_hint' => 'Cash Withdrawal',
                'confidence_boost' => 20
            ]
        ];

        foreach ($rules as $rule) {
            DB::table('pattern_rules')->insert([
                'uuid' => Str::uuid(),
                'company_id' => null, // Global
                'name' => $rule['name'],
                'description' => $rule['description'],
                'rule_type' => $rule['rule_type'],
                'patterns' => json_encode($rule['patterns']),
                'confidence_boost' => $rule['confidence_boost'],
                'priority' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}