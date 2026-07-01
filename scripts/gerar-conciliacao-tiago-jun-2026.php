<?php

/**
 * Gera planilha de conciliação: lista manual × banco (Tiago, jun/2026).
 * Uso: php scripts/gerar-conciliacao-tiago-jun-2026.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Services\OrdemServicoService;
use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conciliacao = [
    ['n' => 1, 'data' => '18/06/26', 'servico' => 'virada da OLT', 'operador' => 'Maurício', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Parcial', 'codigo_banco' => '—', 'titulo_banco' => 'Migrações OLT (várias O.S. no mês)', 'tecnico_banco' => 'Tiago', 'data_banco' => '—', 'acao' => 'Verificar se está coberto por GV-NET-0119 / NET-0145 / NET-0152'],
    ['n' => 2, 'data' => '19/06/26', 'servico' => 'auxílio na instalação do Cláudio', 'operador' => 'João Pedro', 'os_lista' => 'enviada', 'materiais' => 'nenhum', 'status' => 'Faltando', 'codigo_banco' => '—', 'titulo_banco' => '—', 'tecnico_banco' => '—', 'data_banco' => '—', 'acao' => 'Criar O.S. ou vincular ao Tiago'],
    ['n' => 3, 'data' => '19/06/26', 'servico' => 'troca de spliter GVS0405', 'operador' => 'João Pedro', 'os_lista' => 'enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-ATD-0138', 'titulo_banco' => 'TROCA DE SPLITTER - GVS0405', 'tecnico_banco' => 'Tiago', 'data_banco' => '19/06/26', 'acao' => 'OK — lista diz enviada, banco confirma'],
    ['n' => 4, 'data' => '19/06/26', 'servico' => 'instalação juntamente com Cláudio exposição (Assembleia de Deus)', 'operador' => 'suporte', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Faltando', 'codigo_banco' => '—', 'titulo_banco' => '—', 'tecnico_banco' => '—', 'data_banco' => '—', 'acao' => 'Criar O.S.'],
    ['n' => 5, 'data' => '19/06/26', 'servico' => 'retirada de atenuação caixa expansão exposição', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Faltando', 'codigo_banco' => '—', 'titulo_banco' => '—', 'tecnico_banco' => '—', 'data_banco' => '—', 'acao' => 'Criar O.S.'],
    ['n' => 6, 'data' => '19/06/26', 'servico' => 'troca de poste Atalaia', 'operador' => 'Raimundo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Outro técnico', 'codigo_banco' => 'GV-POS-0058', 'titulo_banco' => 'Troca de poste - Jardim Atalaia', 'tecnico_banco' => 'Diogo', 'data_banco' => '19/06/26', 'acao' => 'Existe, mas não está no Tiago — reatribuir ou corrigir lista'],
    ['n' => 7, 'data' => '22/06/26', 'servico' => 'virada de fibras emenda p06', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Outro técnico', 'codigo_banco' => 'GV-NET-0133', 'titulo_banco' => 'Encaixe da emenda P06', 'tecnico_banco' => 'Leyzon', 'data_banco' => '12/06/26', 'acao' => 'Existe com outro técnico e data diferente'],
    ['n' => 8, 'data' => '22/06/26', 'servico' => 'virada de fibra na emenda BB-GV-241', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0145', 'titulo_banco' => 'ENCAIXE BB-GV-241 - MIGRAÇÃO ETAPA 1/2', 'tecnico_banco' => 'Tiago', 'data_banco' => '19/06/26', 'acao' => 'OK no Tiago — data lista 22/06, banco 19/06'],
    ['n' => 9, 'data' => '23/06/26', 'servico' => 'Virada de fibra na emenda X08', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Outro técnico', 'codigo_banco' => 'GV-NET-0145', 'titulo_banco' => 'ENCAIXE X08 - MIGRAÇÃO ETAPA 1/2', 'tecnico_banco' => 'Guilherme', 'data_banco' => '19/06/26', 'acao' => 'Existe, mas não no Tiago'],
    ['n' => 10, 'data' => '23/06/26', 'servico' => 'virada de fibra na emenda M06', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => '1 Kit derivação emenda DPR', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0150', 'titulo_banco' => 'ENCAIXE M06 / P07 / GVT12 / GVT13 - ETAPA 2/2', 'tecnico_banco' => 'Tiago', 'data_banco' => '23/06/26', 'acao' => 'OK'],
    ['n' => 11, 'data' => '24/06/26', 'servico' => 'certificação da OLT antiga e nova são Raimundo', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Parcial', 'codigo_banco' => 'GV-NET-0128', 'titulo_banco' => 'CERTIFICAÇÃO + ADEQUAÇÃO CEO MIGRAÇÃO POP VILA ISA', 'tecnico_banco' => 'Tiago', 'data_banco' => '11/06/26', 'acao' => 'Serviço similar, mas não São Raimundo / data diferente'],
    ['n' => 12, 'data' => '24/06/26', 'servico' => 'manutenção do X9 T8 pico do Ibituruna', 'operador' => 'João Victor', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-MCR-0048', 'titulo_banco' => 'REALIZAR A MANUTENÇÃO DO X9 DO PICO DA IBITURUNA', 'tecnico_banco' => 'Tiago', 'data_banco' => '24/06/26', 'acao' => 'OK — em andamento no banco'],
    ['n' => 13, 'data' => '25/06/26', 'servico' => 'retirada da retificadora OLT antiga São Raimundo', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Agrupado em O.S. única do POP antigo'],
    ['n' => 14, 'data' => '25/06/26', 'servico' => 'instalação da retificadora Nova OLT são Raimundo', 'operador' => 'João Pedro', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Agrupado em O.S. única do POP antigo'],
    ['n' => 15, 'data' => '25/06/26', 'servico' => 'troca de spliter da CTO GVT0404', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => '1 splitter 1x16', 'status' => 'Confirmada', 'codigo_banco' => 'GV-ATD-0139', 'titulo_banco' => 'Troca de splitter GVT0404', 'tecnico_banco' => 'Tiago', 'data_banco' => '19/06/26', 'acao' => 'OK no Tiago — data lista 25/06, banco 19/06'],
    ['n' => 16, 'data' => '25/06/26', 'servico' => 'retirada de todos os equipamentos da OLT 8 antiga', 'operador' => 'jobert', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Agrupado em O.S. única'],
    ['n' => 17, 'data' => '26/06/26', 'servico' => 'separação de tamanho dos cordões retirados da OLT 8', 'operador' => 'Jobert', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Agrupado em O.S. única'],
    ['n' => 18, 'data' => '26/06/26', 'servico' => 'manutenção da câmera 3 OLT 8', 'operador' => 'Mateus', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Provável subatividade da mesma O.S.'],
    ['n' => 19, 'data' => '26/06/26', 'servico' => 'ligação do cabo de energia 2 OLT 8', 'operador' => 'jobert', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0152', 'titulo_banco' => 'Manutenção - Antigo POP da OLT 8', 'tecnico_banco' => 'Tiago', 'data_banco' => '25/06/26', 'acao' => 'Agrupado em O.S. única'],
    ['n' => 20, 'data' => '26/06/26', 'servico' => 'atualização da etiqueta CTO GVZ1105', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Outro técnico', 'codigo_banco' => 'GV-ROM-0249', 'titulo_banco' => 'encaixe GVZ1105', 'tecnico_banco' => 'Guilherme', 'data_banco' => '19/06/26', 'acao' => 'Existe, mas não no Tiago — verificar Grupo 3 (GV-NET-0153)'],
    ['n' => 21, 'data' => '26/06/26', 'servico' => 'atualização da etiqueta CTO GVK0408', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Sem vínculo', 'codigo_banco' => '—', 'titulo_banco' => 'GVK0408 (registro órfão)', 'tecnico_banco' => '— / Leyzon', 'data_banco' => '16–19/06', 'acao' => 'Vincular ao Tiago ou incluir em Grupo 3'],
    ['n' => 22, 'data' => '26/06/26', 'servico' => 'atualização da etiqueta CTO GVH0603', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Sem vínculo', 'codigo_banco' => '—', 'titulo_banco' => 'GVH0603 (registro órfão)', 'tecnico_banco' => '—', 'data_banco' => '22/06/26', 'acao' => 'Vincular ao Tiago'],
    ['n' => 23, 'data' => '29/06/26', 'servico' => 'Manutenção porta 14 CTO GVE0508', 'operador' => 'Maurício', 'os_lista' => 'enviada', 'materiais' => 'nenhum', 'status' => 'Faltando', 'codigo_banco' => '—', 'titulo_banco' => '—', 'tecnico_banco' => '—', 'data_banco' => '—', 'acao' => 'Não encontrada no banco — criar O.S.'],
    ['n' => 24, 'data' => '26/06/25', 'servico' => 'troca de poste distrito industrial', 'operador' => 'Matheus', 'os_lista' => 'enviada', 'materiais' => 'nenhum', 'status' => 'Sem vínculo', 'codigo_banco' => 'GV-POS-0069', 'titulo_banco' => 'Troca de poste - Distrito Industrial', 'tecnico_banco' => '—', 'data_banco' => '29/06/26', 'acao' => 'Existe sem técnico — data lista provável typo (26/06/26)'],
    ['n' => 25, 'data' => '26/06/26', 'servico' => 'atualização etiqueta CTO W0102', 'operador' => 'enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Sem vínculo', 'codigo_banco' => '—', 'titulo_banco' => 'W0102 (registro órfão)', 'tecnico_banco' => '—', 'data_banco' => '16/06/26', 'acao' => 'Vincular ao Tiago'],
    ['n' => 26, 'data' => '26/06/26', 'servico' => 'atualização etiqueta CTO N1606', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0153', 'titulo_banco' => 'REFAZER LACRES CTO PENDURADA N1606', 'tecnico_banco' => 'Tiago', 'data_banco' => '29/06/26', 'acao' => 'OK — data banco 29/06'],
    ['n' => 27, 'data' => '26/06/26', 'servico' => 'atualização etiqueta CTO W0710', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Parcial', 'codigo_banco' => 'GV-MCR-0022', 'titulo_banco' => 'W0710 com perna quebrada', 'tecnico_banco' => 'Tiago', 'data_banco' => '01/06/26', 'acao' => 'Mesma CTO, serviço/descrição e data diferentes'],
    ['n' => 28, 'data' => '26/06/36', 'servico' => 'atualização etiqueta CTO W0908', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Sem vínculo', 'codigo_banco' => '—', 'titulo_banco' => 'W0908 (registro órfão)', 'tecnico_banco' => '—', 'data_banco' => '18/06/26', 'acao' => 'Vincular ao Tiago — data lista com typo (26/06/26)'],
    ['n' => 29, 'data' => '26/06/26', 'servico' => 'CTO N1606 pendurada', 'operador' => 'Enzo', 'os_lista' => 'não enviada', 'materiais' => '2 fecho/2 anel', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0153', 'titulo_banco' => 'REFAZER LACRES CTO PENDURADA N1606', 'tecnico_banco' => 'Tiago', 'data_banco' => '29/06/26', 'acao' => 'Mesma O.S. do item 26'],
    ['n' => 30, 'data' => '30/06/26', 'servico' => 'troca de poste bairro Santa Rita', 'operador' => 'Maurício', 'os_lista' => 'enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-POS-0071', 'titulo_banco' => 'Acopanhar troca de poste - Santa Rita', 'tecnico_banco' => 'Tiago', 'data_banco' => '30/06/26', 'acao' => 'OK'],
    ['n' => 31, 'data' => '30/06/26', 'servico' => 'troca de poste bairro castanheiras', 'operador' => 'Maurício', 'os_lista' => 'não enviada', 'materiais' => 'nenhum', 'status' => 'Confirmada', 'codigo_banco' => 'GV-POS-0071', 'titulo_banco' => 'Troca de poste - Castanheiras', 'tecnico_banco' => 'Tiago', 'data_banco' => '30/06/26', 'acao' => 'OK — mesmo código GV-POS-0071'],
    ['n' => 32, 'data' => '30/06/26', 'servico' => 'troca de splitter CTO GVE0501', 'operador' => 'Maurício', 'os_lista' => 'enviada', 'materiais' => '1 Splitter', 'status' => 'Confirmada', 'codigo_banco' => 'GV-NET-0154', 'titulo_banco' => 'Realizar a troca do splitter da GVE0501', 'tecnico_banco' => 'Tiago', 'data_banco' => '29/06/26', 'acao' => 'OK — data banco 29/06'],
];

$service = app(OrdemServicoService::class);
$tiagoItems = collect($service->listar([
    'tecnico' => 'Tiago',
    'dataInicio' => '2026-06-01',
    'dataFim' => '2026-06-30',
    'tipoData' => 'criacao',
], 500, 0)['items']);

$codigosUsados = collect($conciliacao)
    ->pluck('codigo_banco')
    ->filter(fn ($c) => $c !== '—')
    ->unique();

$soNoBanco = $tiagoItems->filter(function ($item) use ($codigosUsados) {
    return ! $codigosUsados->contains($item['taskCode'] ?? '');
})->values();

$spreadsheet = new Spreadsheet;

// --- Aba 1: Conciliação ---
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Conciliação');

$COLOR_PRIMARY = '166AC4';
$COLOR_HEADER = 'F8FAFC';
$COLOR_BORDER = 'E2E8F0';
$statusColors = [
    'Confirmada' => 'DCFCE7',
    'Faltando' => 'FEE2E2',
    'Outro técnico' => 'FEF9C3',
    'Sem vínculo' => 'FFEDD5',
    'Parcial' => 'DBEAFE',
];

$sheet->mergeCells('A1:L1');
$sheet->setCellValue('A1', 'Conciliação — Lista manual × Banco (Tiago · jun/2026)');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $COLOR_PRIMARY]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
]);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->mergeCells('A2:L2');
$sheet->setCellValue('A2', 'Gerado em '.now()->format('d/m/Y H:i').' · 32 itens na lista · '.$tiagoItems->count().' O.S. do Tiago no banco');
$sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

$headers = ['#', 'Data lista', 'Serviço executado', 'Operador', 'OS na lista', 'Materiais', 'Status', 'Código banco', 'Título no banco', 'Técnico banco', 'Data banco', 'Ação sugerida'];
$cols = range('A', 'L');
$row = 4;
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i].$row, $h);
}
$sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $COLOR_HEADER]],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $COLOR_PRIMARY]]],
]);
$headerRow = $row;
$row++;

$dataStart = $row;
foreach ($conciliacao as $item) {
    $sheet->setCellValue("A{$row}", $item['n']);
    $sheet->setCellValue("B{$row}", $item['data']);
    $sheet->setCellValue("C{$row}", $item['servico']);
    $sheet->setCellValue("D{$row}", $item['operador']);
    $sheet->setCellValue("E{$row}", $item['os_lista']);
    $sheet->setCellValue("F{$row}", $item['materiais']);
    $sheet->setCellValue("G{$row}", $item['status']);
    $sheet->setCellValue("H{$row}", $item['codigo_banco']);
    $sheet->setCellValue("I{$row}", $item['titulo_banco']);
    $sheet->setCellValue("J{$row}", $item['tecnico_banco']);
    $sheet->setCellValue("K{$row}", $item['data_banco']);
    $sheet->setCellValue("L{$row}", $item['acao']);

    if (isset($statusColors[$item['status']])) {
        $sheet->getStyle("G{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($statusColors[$item['status']]);
    }
    $row++;
}

$sheet->getStyle("A{$dataStart}:L".($row - 1))->applyFromArray([
    'borders' => [
        'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $COLOR_BORDER]],
        'insideHorizontal' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => $COLOR_BORDER]],
    ],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'font' => ['size' => 10],
]);

$widths = ['A' => 5, 'B' => 11, 'C' => 38, 'D' => 14, 'E' => 12, 'F' => 18, 'G' => 14, 'H' => 14, 'I' => 36, 'J' => 14, 'K' => 11, 'L' => 40];
foreach ($widths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}
$sheet->freezePane('A'.($headerRow + 1));
$sheet->setShowGridlines(false);

// Resumo
$row += 1;
$sheet->setCellValue("A{$row}", 'Resumo');
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;
$contagem = collect($conciliacao)->countBy('status');
foreach ($contagem as $status => $qtd) {
    $sheet->setCellValue("A{$row}", $status);
    $sheet->setCellValue("B{$row}", $qtd);
    $row++;
}

// --- Aba 2: Só no banco ---
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Só no banco');
$sheet2->mergeCells('A1:F1');
$sheet2->setCellValue('A1', 'O.S. do Tiago em jun/2026 não presentes na lista manual');
$sheet2->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $COLOR_PRIMARY]],
]);

$h2 = ['Data', 'Código', 'Título', 'Status', 'Região', 'Origem'];
$row2 = 3;
foreach ($h2 as $i => $h) {
    $sheet2->setCellValue(chr(65 + $i).$row2, $h);
}
$sheet2->getStyle("A{$row2}:F{$row2}")->getFont()->setBold(true);
$sheet2->getStyle("A{$row2}:F{$row2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($COLOR_HEADER);
$row2++;

foreach ($soNoBanco as $item) {
    $sheet2->setCellValue("A{$row2}", $item['data_criacao'] ?? '');
    $sheet2->setCellValue("B{$row2}", $item['taskCode'] ?? '');
    $sheet2->setCellValue("C{$row2}", $item['titulo'] ?? '');
    $sheet2->setCellValue("D{$row2}", $item['status'] ?? '');
    $sheet2->setCellValue("E{$row2}", $item['regiao'] ?? '');
    $sheet2->setCellValue("F{$row2}", $item['categoria_pai_label'] ?? '');
    $row2++;
}

foreach (['A' => 12, 'B' => 14, 'C' => 48, 'D' => 14, 'E' => 14, 'F' => 18] as $col => $w) {
    $sheet2->getColumnDimension($col)->setWidth($w);
}
$sheet2->freezePane('A4');
$sheet2->setShowGridlines(false);

$dir = storage_path('app/exports');
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$arquivo = $dir.'/conciliacao-tiago-jun-2026.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($arquivo);
$spreadsheet->disconnectWorksheets();

echo "Planilha gerada: {$arquivo}\n";
echo 'Itens na lista: '.count($conciliacao)."\n";
echo 'Só no banco: '.$soNoBanco->count()."\n";
