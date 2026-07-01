<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdemServicoExcelExport
{
    private const COL_LAST = 'D';

    private const GREEN_DARK = '14532D';

    private const GREEN_PRIMARY = '166534';

    private const GREEN_ACCENT = '22C55E';

    private const GREEN_LIGHT = 'F0FDF4';

    private const GREEN_ZEBRA = 'ECFDF5';

    private const GREEN_BORDER = 'BBF7D0';

    private const GREEN_MUTED = '4B7C5F';

    private const TEXT_DARK = '052E16';

    /**
     * @param  list<array{0: string, 1: string}>  $filtrosAplicados
     */
    public function download(
        array $dashboard,
        array $filtrosAplicados,
        string $nomeArquivo,
    ): StreamedResponse {
        $spreadsheet = $this->build($dashboard, $filtrosAplicados);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $nomeArquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $filtrosAplicados
     */
    public function build(array $dashboard, array $filtrosAplicados): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumo');

        $row = 1;
        $row = $this->escreverCabecalho($sheet, $row, $filtrosAplicados, $dashboard['totais']);
        $row = $this->escreverTabela($sheet, $row, $dashboard['por_tecnico'], $dashboard['totais']);

        $this->ajustarLarguras($sheet);
        $sheet->setShowGridlines(false);

        return $spreadsheet;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $filtrosAplicados
     * @param  array{total: int, aberta: int, em_andamento: int, finalizada: int}  $totais
     */
    private function escreverCabecalho(
        Worksheet $sheet,
        int $row,
        array $filtrosAplicados,
        array $totais,
    ): int {
        $sheet->mergeCells("A{$row}:".self::COL_LAST."{$row}");
        $sheet->setCellValue("A{$row}", 'Resumo de Ordens de Serviço');
        $sheet->getStyle("A{$row}:".self::COL_LAST."{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 18, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_DARK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(36);
        $row++;

        $periodo = $this->extrairPeriodo($filtrosAplicados);
        $gerado = now()->timezone(config('app.timezone'))->format('d/m/Y \à\s H:i');

        $sheet->mergeCells("A{$row}:".self::COL_LAST."{$row}");
        $sheet->setCellValue("A{$row}", "{$periodo} · Gerado em {$gerado}");
        $sheet->getStyle("A{$row}:".self::COL_LAST."{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => self::GREEN_MUTED]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $sheet->mergeCells("A{$row}:".self::COL_LAST."{$row}");
        $sheet->setCellValue("A{$row}", "Total de O.S. no período: {$totais['total']}");
        $sheet->getStyle("A{$row}:".self::COL_LAST."{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::GREEN_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        return $row + 2;
    }

    /**
     * @param  array{total: int, aberta: int, em_andamento: int, finalizada: int}  $totais
     */
    private function escreverTabela(Worksheet $sheet, int $row, Collection $porTecnico, array $totais): int
    {
        $headers = ['Técnico', 'Abertas', 'Em andamento', 'Finalizadas'];
        $cols = ['A', 'B', 'C', 'D'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i].$row, $header);
        }

        $headerRow = $row;
        $sheet->getStyle("A{$headerRow}:".self::COL_LAST."{$headerRow}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::GREEN_ACCENT]]],
        ]);
        $sheet->getStyle("A{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);
        $row++;

        $dataStart = $row;
        foreach ($porTecnico as $item) {
            $sheet->setCellValue("A{$row}", $item['tecnico']);
            $sheet->setCellValue("B{$row}", $item['aberta']);
            $sheet->setCellValue("C{$row}", $item['em_andamento']);
            $sheet->setCellValue("D{$row}", $item['finalizada']);
            $row++;
        }

        if ($dataStart < $row) {
            $this->estiloLinhasDados($sheet, $dataStart, $row - 1);
        }

        $totalRow = $row;
        $sheet->setCellValue("A{$totalRow}", 'Total do período');
        $sheet->setCellValue("B{$totalRow}", $totais['aberta']);
        $sheet->setCellValue("C{$totalRow}", $totais['em_andamento']);
        $sheet->setCellValue("D{$totalRow}", $totais['finalizada']);

        $sheet->getStyle("A{$totalRow}:".self::COL_LAST."{$totalRow}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::TEXT_DARK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::GREEN_PRIMARY]],
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GREEN_BORDER]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("B{$totalRow}:D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $sheet->getRowDimension($totalRow)->setRowHeight(30);

        $sheet->getStyle("A{$headerRow}:".self::COL_LAST.$totalRow)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GREEN_BORDER]],
                'inside' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::GREEN_BORDER]],
            ],
        ]);

        return $totalRow + 1;
    }

    private function estiloLinhasDados(Worksheet $sheet, int $inicio, int $fim): void
    {
        $sheet->getStyle("A{$inicio}:".self::COL_LAST."{$fim}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => self::TEXT_DARK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("B{$inicio}:D{$fim}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$inicio}:A{$fim}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);

        for ($r = $inicio; $r <= $fim; $r++) {
            if (($r - $inicio) % 2 === 1) {
                $sheet->getStyle("A{$r}:".self::COL_LAST."{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB(self::GREEN_ZEBRA);
            }
            $sheet->getRowDimension($r)->setRowHeight(24);
        }
    }

    /** @param  list<array{0: string, 1: string}>  $filtrosAplicados */
    private function extrairPeriodo(array $filtrosAplicados): string
    {
        foreach ($filtrosAplicados as [$campo, $valor]) {
            if ($campo === 'Período') {
                return $valor;
            }
        }

        return 'Período: todos os registros';
    }

    private function ajustarLarguras(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(14);
    }
}
