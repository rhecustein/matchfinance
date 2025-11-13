# MatchFinance Flutter - Advanced Features Guide

**Reports, Export, Backup, dan Performance Optimization**

---

## Table of Contents

1. [Reports Generation](#1-reports-generation)
2. [Excel Export](#2-excel-export)
3. [PDF Export](#3-pdf-export)
4. [Backup & Restore](#4-backup--restore)
5. [Performance Optimization](#5-performance-optimization)
6. [Background Processing](#6-background-processing)
7. [Error Handling](#7-error-handling)
8. [Best Practices](#8-best-practices)

---

## 1. Reports Generation

### 1.1 Report Types

**6 Standard Reports**:
1. Monthly by Bank
2. By Keyword
3. By Category
4. By Sub-Category
5. By Account
6. Comparison View

### 1.2 Report Data Provider

```dart
// lib/presentation/providers/report_provider.dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/usecases/reports/generate_report_usecase.dart';

final reportProvider = StateNotifierProvider<ReportNotifier, ReportState>((ref) {
  return ReportNotifier(ref.watch(generateReportUseCaseProvider));
});

class ReportNotifier extends StateNotifier<ReportState> {
  final GenerateReportUseCase _useCase;

  ReportNotifier(this._useCase) : super(ReportState.initial());

  Future<void> generateMonthlyByBankReport({
    required DateTime startDate,
    required DateTime endDate,
    int? bankId,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _useCase.generateMonthlyByBank(
      startDate: startDate,
      endDate: endDate,
      bankId: bankId,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (data) => state = state.copyWith(
        isLoading: false,
        data: data,
      ),
    );
  }

  Future<void> generateByCategoryReport({
    required DateTime startDate,
    required DateTime endDate,
    List<int>? categoryIds,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _useCase.generateByCategory(
      startDate: startDate,
      endDate: endDate,
      categoryIds: categoryIds,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (data) => state = state.copyWith(
        isLoading: false,
        data: data,
      ),
    );
  }

  // Other report methods...
}

class ReportState {
  final bool isLoading;
  final ReportData? data;
  final String? error;

  ReportState({
    required this.isLoading,
    this.data,
    this.error,
  });

  factory ReportState.initial() => ReportState(isLoading: false);

  ReportState copyWith({
    bool? isLoading,
    ReportData? data,
    String? error,
  }) {
    return ReportState(
      isLoading: isLoading ?? this.isLoading,
      data: data ?? this.data,
      error: error,
    );
  }
}
```

### 1.3 Monthly by Bank Report Screen

```dart
// lib/presentation/screens/reports/monthly_by_bank_report_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:data_table_2/data_table_2.dart';

class MonthlyByBankReportScreen extends ConsumerStatefulWidget {
  const MonthlyByBankReportScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<MonthlyByBankReportScreen> createState() =>
      _MonthlyByBankReportScreenState();
}

class _MonthlyByBankReportScreenState
    extends ConsumerState<MonthlyByBankReportScreen> {
  late DateTime _startDate;
  late DateTime _endDate;
  int? _selectedBankId;

  @override
  void initState() {
    super.initState();
    // Default: Last 30 days
    _endDate = DateTime.now();
    _startDate = _endDate.subtract(const Duration(days: 30));
  }

  Future<void> _generateReport() async {
    await ref.read(reportProvider.notifier).generateMonthlyByBankReport(
          startDate: _startDate,
          endDate: _endDate,
          bankId: _selectedBankId,
        );
  }

  @override
  Widget build(BuildContext context) {
    final reportState = ref.watch(reportProvider);
    final banksAsync = ref.watch(banksProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Monthly Report by Bank'),
        actions: [
          IconButton(
            icon: const Icon(Icons.download),
            onPressed: () => _exportReport(context),
          ),
          IconButton(
            icon: const Icon(Icons.print),
            onPressed: () => _printReport(context),
          ),
        ],
      ),
      body: Column(
        children: [
          // Filter panel
          Card(
            margin: const EdgeInsets.all(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  // Date range picker
                  Expanded(
                    child: InkWell(
                      onTap: () => _selectDateRange(context),
                      child: InputDecorator(
                        decoration: const InputDecoration(
                          labelText: 'Date Range',
                          border: OutlineInputBorder(),
                          prefixIcon: Icon(Icons.calendar_today),
                        ),
                        child: Text(
                          '${DateFormat.yMd().format(_startDate)} - ${DateFormat.yMd().format(_endDate)}',
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),

                  // Bank filter
                  Expanded(
                    child: banksAsync.when(
                      data: (banks) {
                        return DropdownButtonFormField<int?>(
                          decoration: const InputDecoration(
                            labelText: 'Bank',
                            border: OutlineInputBorder(),
                          ),
                          value: _selectedBankId,
                          items: [
                            const DropdownMenuItem(
                              value: null,
                              child: Text('All Banks'),
                            ),
                            ...banks.map((bank) {
                              return DropdownMenuItem(
                                value: bank.id,
                                child: Text(bank.bankName),
                              );
                            }),
                          ],
                          onChanged: (value) {
                            setState(() {
                              _selectedBankId = value;
                            });
                          },
                        );
                      },
                      loading: () => const LinearProgressIndicator(),
                      error: (err, stack) => Text('Error: $err'),
                    ),
                  ),
                  const SizedBox(width: 16),

                  // Generate button
                  ElevatedButton.icon(
                    onPressed: reportState.isLoading ? null : _generateReport,
                    icon: const Icon(Icons.play_arrow),
                    label: const Text('Generate'),
                  ),
                ],
              ),
            ),
          ),

          // Report content
          Expanded(
            child: reportState.isLoading
                ? const Center(child: CircularProgressIndicator())
                : reportState.error != null
                    ? Center(child: Text('Error: ${reportState.error}'))
                    : reportState.data != null
                        ? _buildReportTable(reportState.data!)
                        : const Center(
                            child: Text('Click Generate to create report'),
                          ),
          ),
        ],
      ),
    );
  }

  Widget _buildReportTable(ReportData data) {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Summary cards
            Row(
              children: [
                Expanded(
                  child: _SummaryCard(
                    title: 'Total Income',
                    value: NumberFormat.currency(
                      locale: 'id_ID',
                      symbol: 'Rp ',
                      decimalDigits: 0,
                    ).format(data.totalCredit),
                    color: Colors.green,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _SummaryCard(
                    title: 'Total Expense',
                    value: NumberFormat.currency(
                      locale: 'id_ID',
                      symbol: 'Rp ',
                      decimalDigits: 0,
                    ).format(data.totalDebit),
                    color: Colors.red,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _SummaryCard(
                    title: 'Net Amount',
                    value: NumberFormat.currency(
                      locale: 'id_ID',
                      symbol: 'Rp ',
                      decimalDigits: 0,
                    ).format(data.netAmount),
                    color: data.netAmount >= 0 ? Colors.green : Colors.red,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Data table
            Expanded(
              child: DataTable2(
                columnSpacing: 12,
                horizontalMargin: 12,
                minWidth: 1000,
                columns: const [
                  DataColumn2(label: Text('Month'), size: ColumnSize.M),
                  DataColumn2(label: Text('Bank'), size: ColumnSize.M),
                  DataColumn2(label: Text('Transactions'), size: ColumnSize.S),
                  DataColumn2(label: Text('Income'), size: ColumnSize.L),
                  DataColumn2(label: Text('Expense'), size: ColumnSize.L),
                  DataColumn2(label: Text('Net'), size: ColumnSize.L),
                ],
                rows: data.rows.map((row) {
                  return DataRow2(
                    cells: [
                      DataCell(Text(row.monthLabel)),
                      DataCell(Text(row.bankName)),
                      DataCell(Text(row.transactionCount.toString())),
                      DataCell(
                        Text(
                          NumberFormat.currency(
                            locale: 'id_ID',
                            symbol: 'Rp ',
                            decimalDigits: 0,
                          ).format(row.creditAmount),
                        ),
                      ),
                      DataCell(
                        Text(
                          NumberFormat.currency(
                            locale: 'id_ID',
                            symbol: 'Rp ',
                            decimalDigits: 0,
                          ).format(row.debitAmount),
                        ),
                      ),
                      DataCell(
                        Text(
                          NumberFormat.currency(
                            locale: 'id_ID',
                            symbol: 'Rp ',
                            decimalDigits: 0,
                          ).format(row.netAmount),
                          style: TextStyle(
                            color: row.netAmount >= 0 ? Colors.green : Colors.red,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _selectDateRange(BuildContext context) async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );

    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
    }
  }

  Future<void> _exportReport(BuildContext context) async {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Export Report'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.table_chart),
              title: const Text('Export to Excel'),
              onTap: () {
                Navigator.pop(context);
                _exportToExcel();
              },
            ),
            ListTile(
              leading: const Icon(Icons.picture_as_pdf),
              title: const Text('Export to PDF'),
              onTap: () {
                Navigator.pop(context);
                _exportToPdf();
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _exportToExcel() async {
    final reportData = ref.read(reportProvider).data;
    if (reportData == null) return;

    final exportService = ExcelExportService();
    final file = await exportService.exportMonthlyByBankReport(reportData);

    // Show save dialog
    // ... (implementation using file_picker)

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Report exported to ${file.path}')),
    );
  }

  Future<void> _exportToPdf() async {
    // Similar to Excel export
  }

  Future<void> _printReport(BuildContext context) async {
    // Implementation using printing package
  }
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final Color color;

  const _SummaryCard({
    required this.title,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: color.withOpacity(0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: color,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    color: color,
                    fontWeight: FontWeight.bold,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 2. Excel Export

### 2.1 Excel Export Service

```dart
// lib/services/export/excel_export_service.dart
import 'dart:io';
import 'package:excel/excel.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'package:intl/intl.dart';

class ExcelExportService {
  /// Export Monthly by Bank Report
  Future<File> exportMonthlyByBankReport(ReportData data) async {
    final excel = Excel.createExcel();
    final sheet = excel['Report'];

    // Title
    sheet.merge(
      CellIndex.indexByString('A1'),
      CellIndex.indexByString('F1'),
    );
    sheet.cell(CellIndex.indexByString('A1')).value = TextCellValue('Monthly Report by Bank');
    sheet.cell(CellIndex.indexByString('A1')).cellStyle = CellStyle(
      fontSize: 16,
      bold: true,
      horizontalAlign: HorizontalAlign.Center,
    );

    // Date range
    sheet.merge(
      CellIndex.indexByString('A2'),
      CellIndex.indexByString('F2'),
    );
    sheet.cell(CellIndex.indexByString('A2')).value = TextCellValue(
      'Period: ${DateFormat.yMd().format(data.startDate)} - ${DateFormat.yMd().format(data.endDate)}',
    );

    // Empty row
    int currentRow = 4;

    // Summary section
    sheet.cell(CellIndex.indexByColumnRow(columnIndex: 0, rowIndex: currentRow))
        .value = TextCellValue('Summary');
    sheet.cell(CellIndex.indexByColumnRow(columnIndex: 0, rowIndex: currentRow))
        .cellStyle = CellStyle(bold: true);
    currentRow++;

    _addSummaryRow(sheet, currentRow++, 'Total Income', data.totalCredit);
    _addSummaryRow(sheet, currentRow++, 'Total Expense', data.totalDebit);
    _addSummaryRow(sheet, currentRow++, 'Net Amount', data.netAmount);

    currentRow += 2;

    // Headers
    final headers = ['Month', 'Bank', 'Transactions', 'Income', 'Expense', 'Net'];
    for (int i = 0; i < headers.length; i++) {
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: i, rowIndex: currentRow))
          .value = TextCellValue(headers[i]);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: i, rowIndex: currentRow))
          .cellStyle = CellStyle(
        bold: true,
        backgroundColorHex: ExcelColor.fromHexString('#4472C4'),
        fontColorHex: ExcelColor.white,
      );
    }
    currentRow++;

    // Data rows
    for (final row in data.rows) {
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 0, rowIndex: currentRow))
          .value = TextCellValue(row.monthLabel);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 1, rowIndex: currentRow))
          .value = TextCellValue(row.bankName);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 2, rowIndex: currentRow))
          .value = IntCellValue(row.transactionCount);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 3, rowIndex: currentRow))
          .value = DoubleCellValue(row.creditAmount);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 4, rowIndex: currentRow))
          .value = DoubleCellValue(row.debitAmount);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 5, rowIndex: currentRow))
          .value = DoubleCellValue(row.netAmount);

      // Format currency columns
      for (int col = 3; col <= 5; col++) {
        sheet.cell(CellIndex.indexByColumnRow(columnIndex: col, rowIndex: currentRow))
            .cellStyle = CellStyle(
          numberFormat: NumFormat.custom('Rp #,##0'),
        );
      }

      currentRow++;
    }

    // Auto-fit columns
    for (int i = 0; i < headers.length; i++) {
      sheet.setColumnWidth(i, 15);
    }

    // Save file
    final dir = await getApplicationDocumentsDirectory();
    final timestamp = DateFormat('yyyyMMdd_HHmmss').format(DateTime.now());
    final filePath = path.join(
      dir.path,
      'exports',
      'monthly_by_bank_$timestamp.xlsx',
    );

    final file = File(filePath);
    await file.create(recursive: true);
    await file.writeAsBytes(excel.encode()!);

    return file;
  }

  void _addSummaryRow(Sheet sheet, int row, String label, double value) {
    sheet.cell(CellIndex.indexByColumnRow(columnIndex: 0, rowIndex: row))
        .value = TextCellValue(label);
    sheet.cell(CellIndex.indexByColumnRow(columnIndex: 1, rowIndex: row))
        .value = DoubleCellValue(value);
    sheet.cell(CellIndex.indexByColumnRow(columnIndex: 1, rowIndex: row))
        .cellStyle = CellStyle(
      numberFormat: NumFormat.custom('Rp #,##0'),
      bold: true,
    );
  }

  /// Export Transactions to Excel
  Future<File> exportTransactions(List<Transaction> transactions) async {
    final excel = Excel.createExcel();
    final sheet = excel['Transactions'];

    // Headers
    final headers = [
      'Date',
      'Description',
      'Type',
      'Debit',
      'Credit',
      'Balance',
      'Category',
      'Sub-Category',
      'Account',
      'Confidence',
      'Verified',
    ];

    for (int i = 0; i < headers.length; i++) {
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: i, rowIndex: 0))
          .value = TextCellValue(headers[i]);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: i, rowIndex: 0))
          .cellStyle = CellStyle(bold: true);
    }

    // Data rows
    for (int i = 0; i < transactions.length; i++) {
      final t = transactions[i];
      final row = i + 1;

      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 0, rowIndex: row))
          .value = DateCellValue(
        year: t.transactionDate.year,
        month: t.transactionDate.month,
        day: t.transactionDate.day,
      );
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 1, rowIndex: row))
          .value = TextCellValue(t.description);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 2, rowIndex: row))
          .value = TextCellValue(t.transactionType ?? '');
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 3, rowIndex: row))
          .value = DoubleCellValue(t.debitAmount);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 4, rowIndex: row))
          .value = DoubleCellValue(t.creditAmount);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 5, rowIndex: row))
          .value = DoubleCellValue(t.balance ?? 0);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 6, rowIndex: row))
          .value = TextCellValue(t.categoryName ?? '');
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 7, rowIndex: row))
          .value = TextCellValue(t.subCategoryName ?? '');
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 8, rowIndex: row))
          .value = TextCellValue(t.accountName ?? '');
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 9, rowIndex: row))
          .value = IntCellValue(t.confidenceScore);
      sheet.cell(CellIndex.indexByColumnRow(columnIndex: 10, rowIndex: row))
          .value = TextCellValue(t.isVerified ? 'Yes' : 'No');
    }

    // Save file
    final dir = await getApplicationDocumentsDirectory();
    final timestamp = DateFormat('yyyyMMdd_HHmmss').format(DateTime.now());
    final filePath = path.join(
      dir.path,
      'exports',
      'transactions_$timestamp.xlsx',
    );

    final file = File(filePath);
    await file.create(recursive: true);
    await file.writeAsBytes(excel.encode()!);

    return file;
  }

  /// Import Keywords from Excel
  Future<List<KeywordImportRow>> importKeywords(File excelFile) async {
    final bytes = await excelFile.readAsBytes();
    final excel = Excel.decodeBytes(bytes);

    final sheet = excel.tables.values.first;
    final rows = <KeywordImportRow>[];

    // Skip header row
    for (int i = 1; i < sheet.rows.length; i++) {
      final row = sheet.rows[i];
      if (row.isEmpty) continue;

      try {
        rows.add(
          KeywordImportRow(
            keyword: row[0]?.value.toString() ?? '',
            subCategoryName: row[1]?.value.toString() ?? '',
            matchType: row[2]?.value.toString() ?? 'contains',
            priority: int.tryParse(row[3]?.value.toString() ?? '5') ?? 5,
            isRegex: row[4]?.value.toString().toLowerCase() == 'true',
            caseSensitive: row[5]?.value.toString().toLowerCase() == 'true',
          ),
        );
      } catch (e) {
        // Skip invalid rows
        continue;
      }
    }

    return rows;
  }
}

class KeywordImportRow {
  final String keyword;
  final String subCategoryName;
  final String matchType;
  final int priority;
  final bool isRegex;
  final bool caseSensitive;

  KeywordImportRow({
    required this.keyword,
    required this.subCategoryName,
    required this.matchType,
    required this.priority,
    required this.isRegex,
    required this.caseSensitive,
  });
}
```

---

## 3. PDF Export

### 3.1 PDF Export Service

```dart
// lib/services/export/pdf_export_service.dart
import 'dart:io';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'package:intl/intl.dart';

class PdfExportService {
  /// Export Monthly by Bank Report to PDF
  Future<File> exportMonthlyByBankReport(ReportData data) async {
    final pdf = pw.Document();

    pdf.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4.landscape,
        margin: const pw.EdgeInsets.all(32),
        build: (pw.Context context) {
          return [
            // Title
            pw.Header(
              level: 0,
              child: pw.Text(
                'Monthly Report by Bank',
                style: pw.TextStyle(
                  fontSize: 24,
                  fontWeight: pw.FontWeight.bold,
                ),
              ),
            ),
            pw.SizedBox(height: 8),

            // Period
            pw.Text(
              'Period: ${DateFormat.yMd().format(data.startDate)} - ${DateFormat.yMd().format(data.endDate)}',
              style: const pw.TextStyle(fontSize: 12),
            ),
            pw.SizedBox(height: 24),

            // Summary section
            pw.Container(
              padding: const pw.EdgeInsets.all(16),
              decoration: pw.BoxDecoration(
                color: PdfColors.grey200,
                borderRadius: const pw.BorderRadius.all(pw.Radius.circular(8)),
              ),
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Text(
                    'Summary',
                    style: pw.TextStyle(
                      fontSize: 16,
                      fontWeight: pw.FontWeight.bold,
                    ),
                  ),
                  pw.SizedBox(height: 12),
                  _buildSummaryRow('Total Income', data.totalCredit),
                  _buildSummaryRow('Total Expense', data.totalDebit),
                  _buildSummaryRow('Net Amount', data.netAmount),
                ],
              ),
            ),
            pw.SizedBox(height: 24),

            // Data table
            pw.Table(
              border: pw.TableBorder.all(color: PdfColors.grey400),
              children: [
                // Header
                pw.TableRow(
                  decoration: const pw.BoxDecoration(color: PdfColors.blue),
                  children: [
                    _buildTableCell('Month', isHeader: true),
                    _buildTableCell('Bank', isHeader: true),
                    _buildTableCell('Transactions', isHeader: true),
                    _buildTableCell('Income', isHeader: true),
                    _buildTableCell('Expense', isHeader: true),
                    _buildTableCell('Net', isHeader: true),
                  ],
                ),

                // Data rows
                ...data.rows.map((row) {
                  return pw.TableRow(
                    children: [
                      _buildTableCell(row.monthLabel),
                      _buildTableCell(row.bankName),
                      _buildTableCell(row.transactionCount.toString()),
                      _buildTableCell(_formatCurrency(row.creditAmount)),
                      _buildTableCell(_formatCurrency(row.debitAmount)),
                      _buildTableCell(
                        _formatCurrency(row.netAmount),
                        textColor: row.netAmount >= 0
                            ? PdfColors.green
                            : PdfColors.red,
                      ),
                    ],
                  );
                }),
              ],
            ),

            pw.SizedBox(height: 24),

            // Footer
            pw.Align(
              alignment: pw.Alignment.bottomRight,
              child: pw.Text(
                'Generated on ${DateFormat.yMd().add_jm().format(DateTime.now())}',
                style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey),
              ),
            ),
          ];
        },
      ),
    );

    // Save file
    final dir = await getApplicationDocumentsDirectory();
    final timestamp = DateFormat('yyyyMMdd_HHmmss').format(DateTime.now());
    final filePath = path.join(
      dir.path,
      'exports',
      'monthly_by_bank_$timestamp.pdf',
    );

    final file = File(filePath);
    await file.create(recursive: true);
    await file.writeAsBytes(await pdf.save());

    return file;
  }

  pw.Widget _buildSummaryRow(String label, double value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 4),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label),
          pw.Text(
            _formatCurrency(value),
            style: pw.TextStyle(fontWeight: pw.FontWeight.bold),
          ),
        ],
      ),
    );
  }

  pw.Widget _buildTableCell(
    String text, {
    bool isHeader = false,
    PdfColor? textColor,
  }) {
    return pw.Padding(
      padding: const pw.EdgeInsets.all(8),
      child: pw.Text(
        text,
        style: pw.TextStyle(
          fontSize: isHeader ? 12 : 10,
          fontWeight: isHeader ? pw.FontWeight.bold : pw.FontWeight.normal,
          color: textColor ?? (isHeader ? PdfColors.white : PdfColors.black),
        ),
      ),
    );
  }

  String _formatCurrency(double value) {
    return NumberFormat.currency(
      locale: 'id_ID',
      symbol: 'Rp ',
      decimalDigits: 0,
    ).format(value);
  }

  /// Print PDF using printing package
  Future<void> printPdf(File pdfFile) async {
    final bytes = await pdfFile.readAsBytes();
    // Use printing package
    // await Printing.layoutPdf(onLayout: (format) async => bytes);
  }
}
```

---

## 4. Backup & Restore

### 4.1 Backup Service

```dart
// lib/services/backup/backup_service.dart
import 'dart:io';
import 'dart:convert';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'package:intl/intl.dart';
import 'package:archive/archive_io.dart';
import '../../data/database/app_database.dart';

class BackupService {
  final AppDatabase _db;

  BackupService(this._db);

  /// Create full backup of company data
  Future<File> createBackup(int companyId) async {
    final backupData = await _exportCompanyData(companyId);
    final json = jsonEncode(backupData);

    // Create backup directory
    final dir = await getApplicationDocumentsDirectory();
    final backupDir = Directory(path.join(dir.path, 'backups'));
    await backupDir.create(recursive: true);

    // Create backup file
    final timestamp = DateFormat('yyyyMMdd_HHmmss').format(DateTime.now());
    final backupFile = File(
      path.join(backupDir.path, 'backup_company_${companyId}_$timestamp.json'),
    );

    await backupFile.writeAsString(json);

    // Optionally compress
    final compressedFile = await _compressBackup(backupFile);

    return compressedFile;
  }

  /// Export all company data to JSON
  Future<Map<String, dynamic>> _exportCompanyData(int companyId) async {
    // Export company info
    final company = await (_db.select(_db.companies)
          ..where((tbl) => tbl.id.equals(companyId)))
        .getSingle();

    // Export users
    final users = await (_db.select(_db.users)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export banks
    final banks = await (_db.select(_db.banks)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export types
    final types = await (_db.select(_db.types)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export categories
    final categories = await (_db.select(_db.categories)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export sub-categories
    final subCategories = await (_db.select(_db.subCategories)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export keywords
    final keywords = await (_db.select(_db.keywords)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export accounts
    final accounts = await (_db.select(_db.accounts)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export account keywords
    final accountKeywords = await (_db.select(_db.accountKeywords)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export bank statements
    final statements = await (_db.select(_db.bankStatements)
          ..where((tbl) => tbl.companyId.equals(companyId)))
        .get();

    // Export transactions
    final transactions = <StatementTransaction>[];
    for (final statement in statements) {
      final stmtTransactions = await (_db.select(_db.statementTransactions)
            ..where((tbl) => tbl.bankStatementId.equals(statement.id)))
          .get();
      transactions.addAll(stmtTransactions);
    }

    // Build backup data
    return {
      'version': '1.0.0',
      'created_at': DateTime.now().toIso8601String(),
      'company': _serializeCompany(company),
      'users': users.map(_serializeUser).toList(),
      'banks': banks.map(_serializeBank).toList(),
      'types': types.map(_serializeType).toList(),
      'categories': categories.map(_serializeCategory).toList(),
      'sub_categories': subCategories.map(_serializeSubCategory).toList(),
      'keywords': keywords.map(_serializeKeyword).toList(),
      'accounts': accounts.map(_serializeAccount).toList(),
      'account_keywords': accountKeywords.map(_serializeAccountKeyword).toList(),
      'bank_statements': statements.map(_serializeBankStatement).toList(),
      'transactions': transactions.map(_serializeTransaction).toList(),
    };
  }

  /// Compress backup file
  Future<File> _compressBackup(File backupFile) async {
    final bytes = await backupFile.readAsBytes();
    final archive = Archive();

    archive.addFile(ArchiveFile(
      path.basename(backupFile.path),
      bytes.length,
      bytes,
    ));

    final compressed = ZipEncoder().encode(archive);
    if (compressed == null) throw Exception('Failed to compress backup');

    final compressedFile = File('${backupFile.path}.zip');
    await compressedFile.writeAsBytes(compressed);

    // Delete uncompressed file
    await backupFile.delete();

    return compressedFile;
  }

  /// Restore backup
  Future<void> restoreBackup(File backupFile) async {
    // Decompress if needed
    File jsonFile;
    if (backupFile.path.endsWith('.zip')) {
      jsonFile = await _decompressBackup(backupFile);
    } else {
      jsonFile = backupFile;
    }

    // Parse JSON
    final json = await jsonFile.readAsString();
    final data = jsonDecode(json) as Map<String, dynamic>;

    // Validate version
    final version = data['version'] as String;
    if (version != '1.0.0') {
      throw Exception('Unsupported backup version: $version');
    }

    // Import data
    await _importCompanyData(data);

    // Clean up temp file
    if (backupFile.path.endsWith('.zip')) {
      await jsonFile.delete();
    }
  }

  Future<File> _decompressBackup(File compressedFile) async {
    final bytes = await compressedFile.readAsBytes();
    final archive = ZipDecoder().decodeBytes(bytes);

    final tempDir = await getTemporaryDirectory();
    final extractedFile = File(
      path.join(tempDir.path, archive.files.first.name),
    );

    await extractedFile.writeAsBytes(archive.files.first.content as List<int>);

    return extractedFile;
  }

  /// Import data from backup
  Future<void> _importCompanyData(Map<String, dynamic> data) async {
    await _db.transaction(() async {
      // Import company
      final companyData = data['company'] as Map<String, dynamic>;
      await _db.into(_db.companies).insert(
            _deserializeCompany(companyData),
            mode: InsertMode.insertOrReplace,
          );

      // Import users
      for (final userData in data['users'] as List) {
        await _db.into(_db.users).insert(
              _deserializeUser(userData as Map<String, dynamic>),
              mode: InsertMode.insertOrReplace,
            );
      }

      // Import banks
      for (final bankData in data['banks'] as List) {
        await _db.into(_db.banks).insert(
              _deserializeBank(bankData as Map<String, dynamic>),
              mode: InsertMode.insertOrReplace,
            );
      }

      // Import types, categories, sub-categories, keywords, etc.
      // ... (similar pattern)

      // Import statements and transactions
      for (final stmtData in data['bank_statements'] as List) {
        await _db.into(_db.bankStatements).insert(
              _deserializeBankStatement(stmtData as Map<String, dynamic>),
              mode: InsertMode.insertOrReplace,
            );
      }

      for (final txnData in data['transactions'] as List) {
        await _db.into(_db.statementTransactions).insert(
              _deserializeTransaction(txnData as Map<String, dynamic>),
              mode: InsertMode.insertOrReplace,
            );
      }
    });
  }

  // Serialization helpers
  Map<String, dynamic> _serializeCompany(Company company) {
    return {
      'id': company.id,
      'uuid': company.uuid,
      'name': company.name,
      'slug': company.slug,
      'logo_path': company.logoPath,
      'settings': company.settings,
      'status': company.status,
      'created_at': company.createdAt,
      'updated_at': company.updatedAt,
    };
  }

  CompaniesCompanion _deserializeCompany(Map<String, dynamic> data) {
    return CompaniesCompanion.insert(
      id: Value(data['id'] as int),
      uuid: data['uuid'] as String,
      name: data['name'] as String,
      slug: Value(data['slug'] as String?),
      logoPath: Value(data['logo_path'] as String?),
      settings: Value(data['settings'] as String?),
      status: data['status'] as String,
      createdAt: data['created_at'] as int,
      updatedAt: data['updated_at'] as int,
    );
  }

  // Similar serialization/deserialization methods for other entities
  // ...

  /// List all backups
  Future<List<BackupInfo>> listBackups() async {
    final dir = await getApplicationDocumentsDirectory();
    final backupDir = Directory(path.join(dir.path, 'backups'));

    if (!await backupDir.exists()) {
      return [];
    }

    final files = await backupDir
        .list()
        .where((entity) => entity is File && entity.path.endsWith('.zip'))
        .cast<File>()
        .toList();

    final backups = <BackupInfo>[];
    for (final file in files) {
      final stat = await file.stat();
      backups.add(
        BackupInfo(
          file: file,
          size: stat.size,
          createdAt: stat.modified,
        ),
      );
    }

    // Sort by date (newest first)
    backups.sort((a, b) => b.createdAt.compareTo(a.createdAt));

    return backups;
  }

  /// Delete backup
  Future<void> deleteBackup(File backupFile) async {
    await backupFile.delete();
  }

  /// Schedule automatic backup
  Future<void> scheduleAutoBackup(int companyId, Duration interval) async {
    // Implementation using periodic timer or platform-specific scheduler
    // ...
  }
}

class BackupInfo {
  final File file;
  final int size;
  final DateTime createdAt;

  BackupInfo({
    required this.file,
    required this.size,
    required this.createdAt,
  });

  String get sizeFormatted {
    if (size < 1024) return '$size B';
    if (size < 1024 * 1024) return '${(size / 1024).toStringAsFixed(1)} KB';
    return '${(size / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
}
```

### 4.2 Backup Screen

```dart
// lib/presentation/screens/backup/backup_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

class BackupScreen extends ConsumerWidget {
  const BackupScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final backupsAsync = ref.watch(backupsProvider);
    final companyId = ref.watch(selectedCompanyIdProvider)!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Backup & Restore'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Create backup button
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Row(
                  children: [
                    const Icon(Icons.backup, size: 48),
                    const SizedBox(width: 24),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Create Backup',
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Create a full backup of your company data',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton.icon(
                      onPressed: () => _createBackup(context, ref, companyId),
                      icon: const Icon(Icons.add),
                      label: const Text('Create Backup'),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Backup list
            Text(
              'Backup History',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),

            Expanded(
              child: backupsAsync.when(
                data: (backups) {
                  if (backups.isEmpty) {
                    return const Center(
                      child: Text('No backups found'),
                    );
                  }

                  return ListView.builder(
                    itemCount: backups.length,
                    itemBuilder: (context, index) {
                      final backup = backups[index];
                      return Card(
                        child: ListTile(
                          leading: const Icon(Icons.folder_zip),
                          title: Text(path.basename(backup.file.path)),
                          subtitle: Text(
                            '${backup.sizeFormatted} • ${DateFormat.yMd().add_jm().format(backup.createdAt)}',
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.restore),
                                onPressed: () => _restoreBackup(
                                  context,
                                  ref,
                                  backup.file,
                                ),
                                tooltip: 'Restore',
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete),
                                onPressed: () => _deleteBackup(
                                  context,
                                  ref,
                                  backup.file,
                                ),
                                tooltip: 'Delete',
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  );
                },
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (err, stack) => Center(child: Text('Error: $err')),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _createBackup(
    BuildContext context,
    WidgetRef ref,
    int companyId,
  ) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const AlertDialog(
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Creating backup...'),
          ],
        ),
      ),
    );

    try {
      final file = await ref.read(backupServiceProvider).createBackup(companyId);

      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Backup created: ${file.path}')),
        );
        ref.invalidate(backupsProvider);
      }
    } catch (e) {
      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  Future<void> _restoreBackup(
    BuildContext context,
    WidgetRef ref,
    File backupFile,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Restore Backup'),
        content: const Text(
          'This will replace all current data with the backup data. This action cannot be undone. Continue?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Restore'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const AlertDialog(
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Restoring backup...'),
          ],
        ),
      ),
    );

    try {
      await ref.read(backupServiceProvider).restoreBackup(backupFile);

      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Backup restored successfully')),
        );
      }
    } catch (e) {
      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  Future<void> _deleteBackup(
    BuildContext context,
    WidgetRef ref,
    File backupFile,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Backup'),
        content: const Text('Are you sure you want to delete this backup?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await ref.read(backupServiceProvider).deleteBackup(backupFile);
      ref.invalidate(backupsProvider);

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Backup deleted')),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }
}
```

---

## 5. Performance Optimization

### 5.1 Database Indexing Strategy

```dart
// Add to database migrations
@override
MigrationStrategy get migration => MigrationStrategy(
  onCreate: (Migrator m) async {
    await m.createAll();

    // Create performance indexes
    await customStatement('''
      CREATE INDEX idx_transactions_compound
      ON statement_transactions(company_id, bank_statement_id, deleted_at)
    ''');

    await customStatement('''
      CREATE INDEX idx_transactions_category_date
      ON statement_transactions(company_id, sub_category_id, transaction_date)
    ''');

    await customStatement('''
      CREATE INDEX idx_keywords_priority
      ON keywords(company_id, is_active, priority DESC)
    ''');

    await customStatement('''
      CREATE INDEX idx_statements_period
      ON bank_statements(company_id, period_from, period_to)
    ''');
  },
);
```

### 5.2 Query Optimization

```dart
// Bad: Loading all data
final transactions = await db.select(db.statementTransactions).get();

// Good: Pagination with limit/offset
Future<List<StatementTransaction>> getTransactionsPaginated({
  required int page,
  required int pageSize,
}) async {
  return await (db.select(db.statementTransactions)
        ..limit(pageSize, offset: page * pageSize)
        ..orderBy([
          (tbl) => OrderingTerm.desc(tbl.transactionDate),
        ]))
      .get();
}

// Good: Specific column selection
Future<List<TransactionSummary>> getTransactionSummaries() async {
  return await (db.select(db.statementTransactions).addColumns([
    db.statementTransactions.id,
    db.statementTransactions.description,
    db.statementTransactions.amount,
    db.statementTransactions.transactionDate,
  ]))
      .get()
      .then((rows) => rows.map((r) => TransactionSummary.fromRow(r)).toList());
}

// Good: Batch operations
Future<void> insertTransactionsBatch(
  List<StatementTransactionsCompanion> transactions,
) async {
  await db.batch((batch) {
    batch.insertAll(db.statementTransactions, transactions);
  });
}
```

### 5.3 Caching Strategy

```dart
// lib/core/cache/cache_manager.dart
import 'package:hive/hive.dart';

class CacheManager {
  static const String _keywordsCacheKey = 'active_keywords';
  static const Duration _cacheDuration = Duration(hours: 1);

  late Box _cacheBox;

  Future<void> init() async {
    _cacheBox = await Hive.openBox('app_cache');
  }

  /// Cache active keywords
  Future<void> cacheKeywords(List<Keyword> keywords) async {
    final data = {
      'timestamp': DateTime.now().millisecondsSinceEpoch,
      'keywords': keywords.map((k) => k.toJson()).toList(),
    };

    await _cacheBox.put(_keywordsCacheKey, data);
  }

  /// Get cached keywords
  Future<List<Keyword>?> getCachedKeywords() async {
    final data = _cacheBox.get(_keywordsCacheKey) as Map?;
    if (data == null) return null;

    final timestamp = data['timestamp'] as int;
    final now = DateTime.now().millisecondsSinceEpoch;

    // Check if cache expired
    if (now - timestamp > _cacheDuration.inMilliseconds) {
      return null;
    }

    final keywords = (data['keywords'] as List)
        .map((k) => Keyword.fromJson(k as Map<String, dynamic>))
        .toList();

    return keywords;
  }

  /// Clear cache
  Future<void> clearCache() async {
    await _cacheBox.clear();
  }

  /// Clear specific key
  Future<void> clearCacheKey(String key) async {
    await _cacheBox.delete(key);
  }
}
```

### 5.4 Memory Management

```dart
// Use Stream for large datasets
Stream<List<Transaction>> watchTransactions(int statementId) {
  return (db.select(db.statementTransactions)
        ..where((tbl) => tbl.bankStatementId.equals(statementId)))
      .watch();
}

// Dispose controllers properly
class TransactionScreen extends StatefulWidget {
  @override
  State<TransactionScreen> createState() => _TransactionScreenState();
}

class _TransactionScreenState extends State<TransactionScreen> {
  late ScrollController _scrollController;
  late TextEditingController _searchController;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    _searchController = TextEditingController();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // UI implementation
    return Container();
  }
}
```

---

## 6. Background Processing

### 6.1 Using Dart Isolates

```dart
// lib/core/background/isolate_manager.dart
import 'dart:isolate';

class IsolateManager {
  /// Run heavy computation in isolate
  static Future<T> runInIsolate<T>(
    Future<T> Function() computation,
  ) async {
    return await Isolate.run(computation);
  }

  /// Run with progress reporting
  static Future<T> runWithProgress<T>({
    required Future<T> Function(SendPort sendPort) computation,
    required void Function(dynamic progress) onProgress,
  }) async {
    final receivePort = ReceivePort();
    final isolate = await Isolate.spawn(
      _isolateEntry<T>,
      _IsolateParams(
        sendPort: receivePort.sendPort,
        computation: computation,
      ),
    );

    final completer = Completer<T>();

    receivePort.listen((message) {
      if (message is _ProgressMessage) {
        onProgress(message.progress);
      } else if (message is _ResultMessage<T>) {
        completer.complete(message.result);
        receivePort.close();
        isolate.kill();
      } else if (message is _ErrorMessage) {
        completer.completeError(message.error);
        receivePort.close();
        isolate.kill();
      }
    });

    return completer.future;
  }

  static Future<void> _isolateEntry<T>(_IsolateParams params) async {
    try {
      final result = await params.computation(params.sendPort);
      params.sendPort.send(_ResultMessage<T>(result));
    } catch (e) {
      params.sendPort.send(_ErrorMessage(e));
    }
  }
}

class _IsolateParams {
  final SendPort sendPort;
  final Function computation;

  _IsolateParams({
    required this.sendPort,
    required this.computation,
  });
}

class _ProgressMessage {
  final dynamic progress;
  _ProgressMessage(this.progress);
}

class _ResultMessage<T> {
  final T result;
  _ResultMessage(this.result);
}

class _ErrorMessage {
  final dynamic error;
  _ErrorMessage(this.error);
}
```

### 6.2 Background Task Example

```dart
// Process bank statement in background
Future<void> processStatementInBackground(int statementId) async {
  await IsolateManager.runWithProgress<void>(
    computation: (sendPort) async {
      // This runs in separate isolate
      final db = AppDatabase(); // Create new DB connection for isolate

      // 1. Run OCR (20% progress)
      sendPort.send(_ProgressMessage(0.0));
      final statement = await db.getBankStatement(statementId);
      final ocrResult = await TesseractOcrService().processPdf(
        File(statement.filePath),
      );
      sendPort.send(_ProgressMessage(0.2));

      // 2. Parse transactions (40% progress)
      final parser = BankParserFactory.getParser(statement.bankName);
      final parseResult = parser.parse(ocrResult.fullText);
      sendPort.send(_ProgressMessage(0.4));

      // 3. Save transactions (60% progress)
      await db.saveTransactions(statementId, parseResult.transactions);
      sendPort.send(_ProgressMessage(0.6));

      // 4. Run matching (80% progress)
      final matchingService = TransactionMatchingService(db);
      await matchingService.matchAllTransactions(statementId);
      sendPort.send(_ProgressMessage(0.8));

      // 5. Run account matching (100% progress)
      final accountService = AccountMatchingService(db);
      await accountService.matchAllToAccounts(statementId);
      sendPort.send(_ProgressMessage(1.0));

      db.close();
    },
    onProgress: (progress) {
      // Update UI with progress
      print('Progress: ${(progress * 100).toStringAsFixed(0)}%');
    },
  );
}
```

---

## 7. Error Handling

### 7.1 Global Error Handler

```dart
// lib/core/errors/error_handler.dart
import 'package:logger/logger.dart';

class ErrorHandler {
  static final Logger _logger = Logger();

  static void handleError(Object error, StackTrace stackTrace) {
    _logger.e('Error occurred', error: error, stackTrace: stackTrace);

    // Log to file or external service
    _logToFile(error, stackTrace);

    // Show user-friendly message
    _showUserMessage(error);
  }

  static void _logToFile(Object error, StackTrace stackTrace) {
    // Implementation: Write to log file
  }

  static void _showUserMessage(Object error) {
    // Implementation: Show snackbar or dialog
  }

  static String getUserFriendlyMessage(Object error) {
    if (error is DatabaseException) {
      return 'Database error occurred. Please try again.';
    } else if (error is FileSystemException) {
      return 'File operation failed. Check permissions.';
    } else if (error is FormatException) {
      return 'Invalid data format.';
    } else {
      return 'An unexpected error occurred.';
    }
  }
}
```

---

## 8. Best Practices

### 8.1 Code Organization

```
✅ DO: Organize by feature
lib/
  features/
    transactions/
      data/
      domain/
      presentation/

❌ DON'T: Organize by type
lib/
  models/
  services/
  screens/
```

### 8.2 State Management

```dart
// ✅ DO: Use const constructors
const MyWidget({super.key});

// ✅ DO: Dispose controllers
@override
void dispose() {
  _controller.dispose();
  super.dispose();
}

// ✅ DO: Handle loading states
ref.watch(provider).when(
  data: (data) => ...,
  loading: () => ...,
  error: (err, stack) => ...,
);
```

### 8.3 Performance

```dart
// ✅ DO: Use const widgets
const Text('Hello');

// ✅ DO: Limit rebuilds
final value = ref.watch(provider.select((s) => s.value));

// ✅ DO: Use ListView.builder for long lists
ListView.builder(
  itemCount: items.length,
  itemBuilder: (context, index) => ...,
);

// ❌ DON'T: Use ListView with many items
ListView(
  children: items.map((item) => ...).toList(),
);
```

---

**End of Advanced Features Guide**

Documentation lengkap sudah dibuat! Apakah ada topik spesifik yang ingin saya perjelas lebih detail?
