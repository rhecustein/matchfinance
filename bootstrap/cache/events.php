<?php return array (
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\PatternLearned' => 
    array (
      0 => 'App\\Listeners\\ClearPatternCache@handle',
    ),
    'App\\Events\\KeywordSuggested' => 
    array (
      0 => 'App\\Listeners\\NotifyAdminOfSuggestion@handle',
    ),
    'App\\Events\\TransactionMatched' => 
    array (
      0 => 'App\\Listeners\\NotifyTransactionMatched@handle',
      1 => 'App\\Listeners\\UpdateKeywordStatistics@handle',
    ),
    'App\\Events\\BankStatementUploaded' => 
    array (
      0 => 'App\\Listeners\\ProcessBankStatement@handle',
    ),
    'App\\Events\\TransactionMatchingCompleted' => 
    array (
      0 => 'App\\Listeners\\StartAccountMatching@handle',
    ),
    'App\\Events\\BankStatementOcrCompleted' => 
    array (
      0 => 'App\\Listeners\\StartTransactionMatching@handle',
    ),
  ),
);