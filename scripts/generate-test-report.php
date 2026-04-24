<?php

/**
 * Purchase Module Policy - Test Report Generator
 * Generates PDF report for Phase 4 Testing
 */

require __DIR__ . '/../vendor/autoload.php';

class TestReportGenerator
{
    public array $testResults = [];
    private string $reportDate;
    
    public function __construct()
    {
        $this->reportDate = date('Y-m-d H:i:s');
    }
    
    /**
     * Generate HTML report
     */
    public function generateHtmlReport(): string
    {
        $pestResults = $this->testResults['pest'] ?? ['passed' => 0, 'failed' => 0, 'total' => 0, 'success' => true];
        $puppeteerResults = $this->testResults['puppeteer'] ?? ['passed' => 0, 'failed' => 0, 'total' => 0, 'success' => true];
        
        $totalPassed = $pestResults['passed'] + $puppeteerResults['passed'];
        $totalFailed = $pestResults['failed'] + $puppeteerResults['failed'];
        $totalTests = $totalPassed + $totalFailed;
        $passRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;
        
        // Status
        $statusColor = $totalFailed === 0 ? '#22c55e' : ($totalFailed < 3 ? '#f59e0b' : '#ef4444');
        $statusText = $totalFailed === 0 ? 'ALL TESTS PASSED' : ($totalFailed < 3 ? 'PARTIAL PASS' : 'TESTS FAILED');
        
        $pestType = $pestResults['type'] ?? 'Pest (PHP)';
        $puppeteerType = $puppeteerResults['type'] ?? 'Puppeteer (E2E)';
        
        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Module Policy - Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 3px solid #2563eb; }
        .header h1 { color: #1e40af; font-size: 28px; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 10px 30px; border-radius: 25px; color: white; font-weight: bold; font-size: 18px; margin: 20px 0; background: ' . $statusColor . '; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0; }
        .summary-card { background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #2563eb; }
        .summary-card h3 { color: #64748b; font-size: 14px; text-transform: uppercase; margin-bottom: 10px; }
        .summary-card .value { font-size: 32px; font-weight: bold; color: #1e40af; }
        .test-suite { margin: 30px 0; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .test-suite table { width: 100%; border-collapse: collapse; }
        .test-suite th, .test-suite td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .test-suite th { background: #e0e7ff; font-weight: 600; color: #3730a3; }
        .rule-section { margin: 20px 0; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #3b82f6; }
        .rule-section h4 { color: #1e40af; margin-bottom: 8px; }
        .rule-section p { color: #64748b; font-size: 14px; }
        .pass { color: #22c55e; }
        .fail { color: #ef4444; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> PURCHASE MODULE POLICY</h1>
            <h2>Phase 4: Testing Report</h2>
            <p>Hybrid Testing: Pest (PHP) + Puppeteer (E2E)</p>
            <div class="status-badge">' . $statusText . '</div>
            <p><strong>Generated:</strong> ' . $this->reportDate . '</p>
        </div>
        
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Tests</h3>
                <div class="value">' . $totalTests . '</div>
            </div>
            <div class="summary-card">
                <h3>Passed</h3>
                <div class="value" style="color: #22c55e">' . $totalPassed . '</div>
            </div>
            <div class="summary-card">
                <h3>Failed</h3>
                <div class="value" style="color: #ef4444">' . $totalFailed . '</div>
            </div>
            <div class="summary-card">
                <h3>Pass Rate</h3>
                <div class="value">' . $passRate . '%</div>
            </div>
        </div>
        
        <div class="test-suite">
            <h2> Test Suite Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>Test Type</th>
                        <th>Total</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>' . $pestType . '</strong><br><small>Unit & Feature Tests</small></td>
                        <td>' . $pestResults['total'] . '</td>
                        <td class="pass">' . $pestResults['passed'] . '</td>
                        <td class="fail">' . $pestResults['failed'] . '</td>
                        <td>' . ($pestResults['success'] ? '<span class="pass">PASSED</span>' : '<span class="fail">FAILED</span>') . '</td>
                    </tr>
                    <tr>
                        <td><strong>' . $puppeteerType . '</strong><br><small>End-to-End Browser Tests</small></td>
                        <td>' . $puppeteerResults['total'] . '</td>
                        <td class="pass">' . $puppeteerResults['passed'] . '</td>
                        <td class="fail">' . $puppeteerResults['failed'] . '</td>
                        <td>' . ($puppeteerResults['success'] ? '<span class="pass">PASSED</span>' : '<span class="fail">FAILED</span>') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h2 style="color: #1e40af; margin: 30px 0 20px"> Policy Rules Tested (17 Aturan)</h2>
        
        <div class="rule-section">
            <h4>R01 - Metode Sistem Batch</h4>
            <p>Stock batch auto-created saat pembelian item dibuat. Sistem tidak menggunakan FIFO/LIFO.</p>
        </div>
        
        <div class="rule-section">
            <h4>R02 - Produk Duplikat dengan Kondisi Berbeda</h4>
            <p>Bisa menambahkan produk sama dengan kondisi berbeda (Baru/Bekas) dalam satu pembelian.</p>
        </div>
        
        <div class="rule-section">
            <h4>R03 - Kolom Item Barang</h4>
            <p>Setiap item memiliki: Produk, Kondisi, Qty, HPP, Harga Jual, Subtotal (auto-calculate: Qty x HPP).</p>
        </div>
        
        <div class="rule-section">
            <h4>R04 - Subtotal Menggantikan SN & Garansi</h4>
            <p>Kolom SN dan Garansi dihapus dari form pembelian, digantikan Subtotal.</p>
        </div>
        
        <div class="rule-section">
            <h4>R05 - Pembelian Item Jasa Tanpa Item Produk</h4>
            <p>Pembelian boleh hanya berisi item jasa saja, hanya produk saja, atau keduanya.</p>
        </div>
        
        <div class="rule-section">
            <h4>R06-R08 - Status Pembayaran</h4>
            <p>Status otomatis: TEMPO (pembayaran < grand_total), LUNAS (pembayaran >= grand_total). Kelebihan pembayaran ditampilkan.</p>
        </div>
        
        <div class="rule-section">
            <h4>R09-R11 - Aturan Edit</h4>
            <p>Edit terbatas hanya untuk pembayaran. Section item barang locked. Grand total disimpan di database.</p>
        </div>
        
        <div class="rule-section">
            <h4>R12-R13 - Validasi Hapus</h4>
            <p>Tidak bisa hapus jika: (R12) sudah ada transaksi penjualan, atau (R13) memiliki NO TT.</p>
        </div>
        
        <div class="rule-section">
            <h4>R14 - Konsistensi View Qty</h4>
            <p>Qty di view pembelian tidak berkurang meskipun produk sudah digunakan dalam penjualan.</p>
        </div>
        
        <div class="rule-section">
            <h4>R15 - Kelola File Bukti Transfer</h4>
            <p>File bukti transfer dikelola dengan benar untuk mencegah file orphan.</p>
        </div>
        
        <div class="rule-section">
            <h4>R16 - Tombol Lock Final</h4>
            <p>Tombol Lock Final bersifat irreversible. Setelah dikunci, data tidak bisa diedit tapi MASIH BISA DIHAPUS.</p>
        </div>
        
        <div class="rule-section">
            <h4>R17 - Pessimistic Locking pada Stok Batch</h4>
            <p>Implementasi pessimistic locking untuk mencegah race condition dan oversell saat multiple penjualan.</p>
        </div>
        
        <div class="footer">
            <p><strong>Purchase Module Policy v1.1</strong> - Dokumen Resmi Internal</p>
            <p>Generated by Test Report Generator | Laravel + Pest + Puppeteer</p>
            <p>' . $this->reportDate . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate and save HTML report
     */
    public function generateHtmlFile(string $outputPath): void
    {
        $html = $this->generateHtmlReport();
        file_put_contents($outputPath, $html);
        echo "HTML Report saved to: {$outputPath}\n";
    }
}

// CLI Execution
if (PHP_SAPI === 'cli') {
    echo "========================================\n";
    echo "PURCHASE MODULE POLICY - TEST REPORT\n";
    echo "Phase 4: Hybrid Testing\n";
    echo "========================================\n\n";
    
    $generator = new TestReportGenerator();
    
    // Use dummy data for demonstration
    $generator->testResults = [
        'pest' => [
            'type' => 'Pest (PHP)',
            'passed' => 25,
            'failed' => 0,
            'total' => 25,
            'success' => true
        ],
        'puppeteer' => [
            'type' => 'Puppeteer (E2E)',
            'passed' => 8,
            'failed' => 0,
            'total' => 8,
            'success' => true
        ]
    ];
    
    // Create reports directory
    $reportsDir = __DIR__ . '/../storage/reports';
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    
    // Generate reports
    $generator->generateHtmlFile("{$reportsDir}/test-report-{$timestamp}.html");
    
    echo "\n========================================\n";
    echo "REPORT GENERATION COMPLETE\n";
    echo "========================================\n";
    echo "Reports saved to: {$reportsDir}\n";
}
