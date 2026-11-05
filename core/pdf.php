<?php
/** Minimal A4 PDF renderer for the finance export. */
function nexus_pdf_escape(string $value): string
{
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
    if ($encoded === false) {
        throw new RuntimeException('Impossible d’encoder le texte du rapport PDF.');
    }

    $escaped = '';
    $length = strlen($encoded);
    for ($i = 0; $i < $length; $i++) {
        $byte = ord($encoded[$i]);
        if ($byte === 40 || $byte === 41 || $byte === 92) {
            $escaped .= '\\' . $encoded[$i];
        } elseif ($byte < 32 || $byte > 126) {
            $escaped .= sprintf('\\%03o', $byte);
        } else {
            $escaped .= $encoded[$i];
        }
    }

    return $escaped;
}

function nexus_pdf_text(float $x, float $top, string $text, int $size = 9, bool $bold = false, string $color = '0.12 0.16 0.23'): string
{
    $y = 842 - $top;
    $font = $bold ? 'F2' : 'F1';
    return sprintf("q %s rg BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET Q\n", $color, $font, $size, $x, $y, nexus_pdf_escape($text));
}

function nexus_pdf_rect(float $x, float $top, float $width, float $height, string $color): string
{
    return sprintf("q %s rg %.2f %.2f %.2f %.2f re f Q\n", $color, $x, 842 - $top - $height, $width, $height);
}

function nexus_pdf_line(float $x1, float $top1, float $x2, float $top2, string $color = '0.88 0.90 0.93'): string
{
    return sprintf("q %s RG 0.6 w %.2f %.2f m %.2f %.2f l S Q\n", $color, $x1, 842 - $top1, $x2, 842 - $top2);
}

function nexus_pdf_format_amount(float $amount, string $currency): string
{
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function nexus_pdf_truncate(string $value, int $maxCharacters): string
{
    $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if ($characters === false) {
        return substr($value, 0, $maxCharacters);
    }
    if (count($characters) <= $maxCharacters) {
        return $value;
    }
    return implode('', array_slice($characters, 0, $maxCharacters - 1)) . '…';
}

function nexus_pdf_build_page(array $rows, array $pageInfo, int $pageNumber, int $pageCount): string
{
    $content = '';
    $content .= nexus_pdf_rect(0, 0, 595, 98, '0.055 0.075 0.12');
    $content .= nexus_pdf_rect(40, 31, 4, 38, '0.00 0.78 0.88');
    $content .= nexus_pdf_text(56, 48, 'NEXUS', 21, true, '1 1 1');
    $content .= nexus_pdf_text(56, 69, $pageInfo['title'], 10, false, '0.72 0.78 0.86');
    $content .= nexus_pdf_text(393, 45, $pageInfo['period'], 10, true, '1 1 1');
    $content .= nexus_pdf_text(393, 66, $pageInfo['generated'], 8, false, '0.72 0.78 0.86');

    if ($pageNumber === 1) {
        $cardX = [40, 215, 390];
        $cardColors = ['0.11 0.16 0.21', '0.18 0.12 0.17', '0.11 0.15 0.21'];
        $labels = [
            [$pageInfo['income_label'], $pageInfo['income'], '0.24 0.82 0.57'],
            [$pageInfo['expense_label'], $pageInfo['expense'], '1.00 0.39 0.43'],
            [$pageInfo['net_label'], $pageInfo['net'], '0.19 0.78 0.88'],
        ];
        foreach ($labels as $index => $card) {
            $content .= nexus_pdf_rect($cardX[$index], 115, 165, 58, $cardColors[$index]);
            $content .= nexus_pdf_text($cardX[$index] + 12, 135, $card[0], 8, false, '0.72 0.78 0.86');
            $content .= nexus_pdf_text($cardX[$index] + 12, 158, $card[1], 13, true, $card[2]);
        }
        $content .= nexus_pdf_text(40, 194, $pageInfo['count'], 8, false, '0.38 0.43 0.51');
    } else {
        $content .= nexus_pdf_text(40, 124, $pageInfo['count'], 8, false, '0.38 0.43 0.51');
    }

    $tableTop = $pageNumber === 1 ? 207 : 143;
    $content .= nexus_pdf_rect(40, $tableTop, 515, 25, '0.055 0.075 0.12');
    $columns = [
        ['date_label', 50],
        ['label_label', 112],
        ['category_label', 328],
        ['type_label', 421],
        ['amount_label', 480],
    ];
    foreach ($columns as [$key, $x]) {
        $content .= nexus_pdf_text($x, $tableTop + 16, $pageInfo[$key], 8, true, '1 1 1');
    }

    $rowTop = $tableTop + 25;
    if (!$rows) {
        $content .= nexus_pdf_text(50, $rowTop + 24, $pageInfo['empty'], 9, false, '0.38 0.43 0.51');
    }
    foreach ($rows as $index => $row) {
        if ($index % 2 === 1) {
            $content .= nexus_pdf_rect(40, $rowTop, 515, 22, '0.96 0.97 0.98');
        }
        $content .= nexus_pdf_text(50, $rowTop + 15, date('d/m/Y', strtotime($row['occurred_on'])), 8);
        $content .= nexus_pdf_text(112, $rowTop + 15, nexus_pdf_truncate($row['label'], 35), 8);
        $content .= nexus_pdf_text(328, $rowTop + 15, nexus_pdf_truncate($row['category_name'] ?? $pageInfo['no_category'], 16), 8);
        $isIncome = $row['type'] === 'income';
        $content .= nexus_pdf_text(421, $rowTop + 15, $isIncome ? $pageInfo['income_type'] : $pageInfo['expense_type'], 8, false, $isIncome ? '0.10 0.55 0.37' : '0.78 0.20 0.25');
        $amount = ($isIncome ? '+' : '−') . nexus_pdf_format_amount((float)$row['amount'], $pageInfo['currency']);
        $content .= nexus_pdf_text(480, $rowTop + 15, nexus_pdf_truncate($amount, 16), 8, true, $isIncome ? '0.10 0.55 0.37' : '0.78 0.20 0.25');
        $content .= nexus_pdf_line(40, $rowTop + 22, 555, $rowTop + 22, '0.90 0.92 0.94');
        $rowTop += 22;
    }

    $content .= nexus_pdf_line(40, 800, 555, 800);
    $content .= nexus_pdf_text(40, 818, 'NEXUS  •  ' . $pageInfo['footer'], 8, false, '0.45 0.49 0.56');
    $content .= nexus_pdf_text(495, 818, $pageNumber . ' / ' . $pageCount, 8, true, '0.20 0.25 0.33');

    return $content;
}

function nexus_transactions_pdf(array $rows, array $labels, string $currency, string $periodFrom, string $periodTo, string $generatedBy): string
{
    $income = 0.0;
    $expense = 0.0;
    foreach ($rows as $row) {
        if ($row['type'] === 'income') {
            $income += (float)$row['amount'];
        } else {
            $expense += (float)$row['amount'];
        }
    }

    $pageInfo = array_merge($labels, [
        'period' => date('d/m/Y', strtotime($periodFrom)) . ' – ' . date('d/m/Y', strtotime($periodTo)),
        'generated' => $labels['generated_by'] . ' ' . $generatedBy . '  •  ' . date('d/m/Y'),
        'income' => nexus_pdf_format_amount($income, $currency),
        'expense' => nexus_pdf_format_amount($expense, $currency),
        'net' => ($income - $expense >= 0 ? '+' : '−') . nexus_pdf_format_amount(abs($income - $expense), $currency),
        'count' => count($rows) . ' ' . $labels['transactions'],
        'currency' => $currency,
    ]);

    $chunks = array_chunk($rows, 25);
    if (!$chunks) {
        $chunks = [[]];
    }

    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
    ];
    $pageIds = [];
    foreach ($chunks as $index => $pageRows) {
        $pageId = 5 + ($index * 2);
        $streamId = $pageId + 1;
        $pageIds[] = $pageId . ' 0 R';
        $stream = nexus_pdf_build_page($pageRows, $pageInfo, $index + 1, count($chunks));
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
        $objects[$streamId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($chunks) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $objectCount = max(array_keys($objects)) + 1;
    $pdf .= "xref\n0 " . $objectCount . "\n0000000000 65535 f \n";
    for ($id = 1; $id < $objectCount; $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . $objectCount . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}
