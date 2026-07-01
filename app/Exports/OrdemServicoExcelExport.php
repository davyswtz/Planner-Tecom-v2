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
    private const COL_LAST = 'E';

    private const COL_LIST_LAST = 'F';

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

        if (! empty($dashboard['lista'])) {
            $row = $this->escreverListaOs($sheet, $row + 1, collect($dashboard['lista']));
        }

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

        return $row + 2;
    }

    /**
     * @param  array{total: int, aberta: int, em_andamento: int, finalizada: int}  $totais
     */
    private function escreverTabela(Worksheet $sheet, int $row, Collection $porTecnico, array $totais): int
    {
        $headers = ['Técnico', 'Abertas', 'Em andamento', 'Finalizadas', 'Total'];
        $cols = ['A', 'B', 'C', 'D', 'E'];

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
            $sheet->setCellValue("E{$row}", $item['total']);
            $row++;
        }

        if ($dataStart < $row) {
            $this->estiloLinhasDados($sheet, $dataStart, $row - 1);
        }

        $totalRow = $row;
        $sheet->setCellValue("A{$totalRow}", 'Total');
        $sheet->setCellValue("B{$totalRow}", $totais['aberta']);
        $sheet->setCellValue("C{$totalRow}", $totais['em_andamento']);
        $sheet->setCellValue("D{$totalRow}", $totais['finalizada']);
        $sheet->setCellValue("E{$totalRow}", $totais['total']);

        $sheet->getStyle("A{$totalRow}:".self::COL_LAST."{$totalRow}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::TEXT_DARK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::GREEN_PRIMARY]],
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GREEN_BORDER]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("B{$totalRow}:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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

    /** @param  Collection<int, array{tecnico: string, taskCode: string, titulo: string, status: string, data_criacao: string, data_conclusao: ?string}>  $lista */
    private function escreverListaOs(Worksheet $sheet, int $row, Collection $lista): int
    {
        $sheet->mergeCells("A{$row}:".self::COL_LIST_LAST."{$row}");
        $sheet->setCellValue("A{$row}", 'Lista de ordens de serviço');
        $sheet->getStyle("A{$row}:".self::COL_LIST_LAST."{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row++;

        $headers = ['Técnico', 'Código', 'Título', 'Status', 'Criação', 'Conclusão'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i].$row, $header);
        }

        $headerRow = $row;
        $sheet->getStyle("A{$headerRow}:".self::COL_LIST_LAST."{$headerRow}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::GREEN_PRIMARY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GREEN_BORDER]]],
        ]);
        $sheet->getStyle("A{$headerRow}:C{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $row++;

        $dataStart = $row;
        foreach ($lista as $item) {
            $sheet->setCellValue("A{$row}", $item['tecnico']);
            $sheet->setCellValue("B{$row}", $item['taskCode']);
            $sheet->setCellValue("C{$row}", $item['titulo']);
            $sheet->setCellValue("D{$row}", $item['status']);
            $sheet->setCellValue("E{$row}", $this->formatarDataBr($item['data_criacao']));
            $sheet->setCellValue("F{$row}", $this->formatarDataBr($item['data_conclusao'] ?? ''));
            $row++;
        }

        if ($dataStart < $row) {
            $sheet->getStyle("A{$dataStart}:".self::COL_LIST_LAST.($row - 1))->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => self::TEXT_DARK]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$dataStart}:C".($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("D{$dataStart}:F".($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            for ($r = $dataStart; $r < $row; $r++) {
                if (($r - $dataStart) % 2 === 1) {
                    $sheet->getStyle("A{$r}:".self::COL_LIST_LAST."{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(self::GREEN_ZEBRA);
                }
                $sheet->getRowDimension($r)->setRowHeight(22);
            }
        }

        $lastRow = max($headerRow, $row - 1);
        $sheet->getStyle("A{$headerRow}:".self::COL_LIST_LAST.$lastRow)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::GREEN_BORDER]],
                'inside' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::GREEN_BORDER]],
            ],
        ]);

        return $row;
    }

    private function formatarDataBr(?string $data): string
    {
        $data = trim((string) $data);
        if ($data === '') {
            return '—';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $matches)) {
            return "{$matches[3]}/{$matches[2]}/{$matches[1]}";
        }

        return $data;
    }

    private function estiloLinhasDados(Worksheet $sheet, int $inicio, int $fim): void
    {
        $sheet->getStyle("A{$inicio}:".self::COL_LAST."{$fim}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => self::TEXT_DARK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("B{$inicio}:E{$fim}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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

    private function ajustarLarguras(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(36);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
    }
}
