<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use DB;
use App\Traits\General;
use App\Traits\Presensi;
use Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class LaporanApiController extends Controller
{
    use General;
    use Presensi;

    public function export_pegawai_bulan()
    {
        $bulan = request('bulan');
        $tahun = session('tahun_penganggaran') ? session('tahun_penganggaran') : date('Y');

        $tanggal_awal = date("Y-m-d", strtotime($tahun . '-' . $bulan . '-01'));
        $tanggal_akhir = date("Y-m-d", strtotime($tahun . '-' . $bulan . '-' . cal_days_in_month(CAL_GREGORIAN, $bulan, date('Y'))));

        $jabatan_req = request("status");
        $pegawai = request('pegawai') ? request('pegawai') : Auth::user()->id_pegawai;
        $pegawai_info = $this->findPegawai($pegawai, $jabatan_req);
        $data = $this->data_kehadiran_pegawai($pegawai, $tanggal_awal, $tanggal_akhir, $pegawai_info->waktu_masuk, $pegawai_info->waktu_keluar, $pegawai_info->tipe_pegawai, $pegawai_info->jumlah_shift);
        $type = request('type');
        if ($pegawai_info->tipe_pegawai == 'pegawai_administratif' || $pegawai_info->tipe_pegawai == 'tenaga_pendidik') {
            return $this->export_rekap_pegawai($data, $type, $pegawai_info, $tanggal_awal, $tanggal_akhir, $pegawai_info->tipe_pegawai);
        } else {
            return $this->export_rekap_pegawai_nakes($data, $type, $pegawai_info, $tanggal_awal, $tanggal_akhir);
        }
    }

    public function export_rekap_pegawai($data, $type, $pegawai_info, $tanggal_awal, $tanggal_akhir, $tipe_pegawai)
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()->setCreator('BKPSDM BULUKUMBA')
            ->setLastModifiedBy('BKPSDM BULUKUMBA')
            ->setTitle('Laporan Rekapitulasi Absen Pegawai')
            ->setSubject('Laporan Rekapitulasi Absen Pegawai')
            ->setDescription('Laporan Rekapitulasi Absen Pegawai')
            ->setKeywords('pdf php')
            ->setCategory('LAPORAN ABSEN');
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);


        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $sheet->setCellValue('A1', 'Laporan Rekapitulasi Absen Pegawai')->mergeCells('A1:I1');
        $sheet->setCellValue('A2', '' . $pegawai_info->nama_unit_kerja)->mergeCells('A2:I2');
        // $sheet->setCellValue('A3', $pegawai_info->nama . ' / ' . $pegawai_info->nip)->mergeCells('A3:G3');
        $sheet->getStyle('A1:I4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:I4')->getFont()->setSize(14);

        $sheet->setCellValue('A7', ' ')->mergeCells('A10:I10');

        $sheet->setCellValue('A8', 'Nama')->mergeCells('A8' . ':B8');
        $sheet->setCellValue('C8', ': ' . $pegawai_info->nama)->mergeCells('C8' . ':G8');
        $sheet->setCellValue('A9', 'NIP')->mergeCells('A8' . ':B8');
        $sheet->setCellValue('C9', ': ' . $pegawai_info->nip)->mergeCells('C9' . ':G9');

        // $sheet->setCellValue('A10', ' ')->mergeCells('A10:G10');

        $sheet->setCellValue('A11', 'No')->mergeCells('A11:A12');
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->setCellValue('B11', 'Tanggal')->mergeCells('B11:B12');
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->setCellValue('C11', 'Status Absen')->mergeCells('C11:C12');
        $sheet->getColumnDimension('C')->setWidth(25);

        $sheet->setCellValue('D11', 'Datang')->mergeCells('D11:E11');
        $sheet->setCellValue('D12', 'Waktu');
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->setCellValue('E12', 'Keterangan');
        $sheet->getColumnDimension('E')->setWidth(25);

        $sheet->setCellValue('F11', 'Istirahat')->mergeCells('F11:G11');
        $sheet->setCellValue('F12', 'Waktu');
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->setCellValue('G12', 'Keterangan');
        $sheet->getColumnDimension('G')->setWidth(25);

        $sheet->setCellValue('H11', 'Pulang')->mergeCells('H11:I11');
        $sheet->setCellValue('H12', 'Waktu');
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->setCellValue('I12', 'Keterangan');
        $sheet->getColumnDimension('I')->setWidth(25);


        $sheet->setCellValue('B13', 'Nama')->mergeCells('B11:B12');
        $sheet->setCellValue('C11', 'Status Absen')->mergeCells('C11:C12');

        $sheet->getStyle('A:I')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:I12')->getFont()->setBold(true);
        $sheet->getRowDimension(11)->setRowHeight(30);
        $sheet->getRowDimension(12)->setRowHeight(30);

        $sheet->getStyle('A11:I12')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');


        $cell = 13;

        foreach ($data['data'] as $index => $value) {
            $sheet->getRowDimension($cell)->setRowHeight(30);
            $sheet->setCellValue('A' . $cell, $index + 1);
            $sheet->setCellValue('B' . $cell, date('d/m/y', strtotime($value['tanggal_absen'])));
            $sheet->setCellValue('C' . $cell, ucfirst($value['status']));
            $sheet->setCellValue('D' . $cell, $value['waktu_masuk']);
            $sheet->setCellValue('E' . $cell, $value['keterangan_masuk']);
            $sheet->setCellValue('F' . $cell, $value['status_masuk_istirahat']);
            $sheet->setCellValue('G' . $cell, $value['waktu_masuk_istirahat']);
            $sheet->setCellValue('H' . $cell, $value['waktu_keluar']);
            $sheet->setCellValue('I' . $cell, $value['keterangan_pulang']);
            $cell++;
        }


        $sheet->getStyle('A5:I9')->getFont()->setSize(12);

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];


        $sheet->getStyle('A11:I' . $cell)->applyFromArray($border);
        $sheet->getStyle('A11:I' . $cell)->getAlignment()->setVertical('center')->setHorizontal('center');

        $cell++;
        $sheet->setCellValue('A' . $cell, ' ')->mergeCells('A' . $cell . ':I' . $cell);
        $cell++;

        $cell_str = $cell;
        $sheet->setCellValue('A' . $cell, 'Keterangan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, 'Volume');
        $sheet->setCellValue('D' . $cell, 'Satuan');

        $sheet->getRowDimension($cell)->setRowHeight(25);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFont()->setBold(true);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');

        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Jumlah hari kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['jml_hari_kerja']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Kehadiran kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['kehadiran_kerja']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Tanpa keterangan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['tanpa_keterangan']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        if ($tipe_pegawai == 'pegawai_administratif') {
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Potongan tanpa keterangan')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['potongan_tanpa_keterangan']);
            $sheet->setCellValue('D' . $cell, '%');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Potongan masuk kerja')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['potongan_masuk_kerja']);
            $sheet->setCellValue('D' . $cell, '%');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Potongan pulang kerja')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['potongan_pulang_kerja']);
            $sheet->setCellValue('D' . $cell, '%');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Potongan apel')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['potongan_apel']);
            $sheet->setCellValue('D' . $cell, '%');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Total potongan')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['jml_potongan_kehadiran_kerja']);
            $sheet->setCellValue('D' . $cell, '%');
            $sheet->getRowDimension($cell)->setRowHeight(25);
            $sheet->getStyle('A' . $cell . ':D' . $cell)->getFont()->setBold(true);
            $sheet->getStyle('A' . $cell . ':D' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');
        }

        if ($tipe_pegawai == 'tenaga_pendidik' || $tipe_pegawai == 'tenaga_kesehatan_non_shift') {
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Jumlah Menit Terlambat Datang')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['jml_menit_terlambat_masuk_kerja']);
            $sheet->setCellValue('D' . $cell, 'Menit');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Jumlah Menit Cepat Pulang')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['jml_menit_terlambat_pulang_kerja']);
            $sheet->setCellValue('D' . $cell, 'Menit');
            $sheet->getRowDimension($cell)->setRowHeight(20);
            $cell = $cell + 1;
            $sheet->setCellValue('A' . $cell, 'Jumlah Total Menit Terlambat Datang dan Cepat Pulang')->mergeCells('A' . $cell . ':B' . $cell);
            $sheet->setCellValue('C' . $cell, $data['jml_menit_terlambat_masuk_kerja'] + $data['jml_menit_terlambat_pulang_kerja']);
            $sheet->setCellValue('D' . $cell, 'Menit');
            $sheet->getRowDimension($cell)->setRowHeight(20);
        }

        $sheet->getStyle('A' . $cell_str . ':D' . $cell)->applyFromArray($border);
        $sheet->getStyle('A' . $cell_str . ':D' . $cell)->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('A' . $cell_str + 1 . ':A' . $cell)->getAlignment()->setHorizontal('left');


        if ($type == 'excel') {
            // Untuk download 
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $periode = $tanggal_awal . ' s/d ' . $tanggal_akhir;
            $filename = "Laporan Absen {$pegawai_info->nama}_$pegawai_info->nip {$periode}.xlsx";
            header("Content-Disposition: attachment;filename=\"$filename\"");
        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }

    public function export_rekap_pegawai_nakes($data, $type, $pegawai_info, $tanggal_awal, $tanggal_akhir)
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()->setCreator('BKPSDM BULUKUMBA')
            ->setLastModifiedBy('BKPSDM BULUKUMBA')
            ->setTitle('Laporan Rekapitulasi Absen Pegawai')
            ->setSubject('Laporan Rekapitulasi Absen Pegawai')
            ->setDescription('Laporan Rekapitulasi Absen Pegawai')
            ->setKeywords('pdf php')
            ->setCategory('LAPORAN ABSEN');
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);


        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $sheet->setCellValue('A1', 'Laporan Rekapitulasi Absen Pegawai')->mergeCells('A1:H1');
        $sheet->setCellValue('A2', '' . $pegawai_info->nama_unit_kerja)->mergeCells('A2:H2');
        // $sheet->setCellValue('A3', $pegawai_info->nama . ' / ' . $pegawai_info->nip)->mergeCells('A3:G3');
        $sheet->getStyle('A1:H4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:H4')->getFont()->setSize(14);

        $sheet->setCellValue('A7', ' ')->mergeCells('A10:G10');

        $sheet->setCellValue('A8', 'Nama')->mergeCells('A8' . ':B8');
        $sheet->setCellValue('C8', ': ' . $pegawai_info->nama)->mergeCells('C8' . ':G8');
        $sheet->setCellValue('A9', 'NIP')->mergeCells('A8' . ':B8');
        $sheet->setCellValue('C9', ': ' . $pegawai_info->nip)->mergeCells('C9' . ':G9');

        // $sheet->setCellValue('A10', ' ')->mergeCells('A10:G10');

        $sheet->setCellValue('A11', 'No')->mergeCells('A11:A12');
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->setCellValue('B11', 'Tanggal')->mergeCells('B11:B12');
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->setCellValue('C11', 'Status Absen')->mergeCells('C11:C12');
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->setCellValue('D11', 'Shift')->mergeCells('D11:D12');
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->setCellValue('E11', 'Datang')->mergeCells('E11:F11');
        $sheet->setCellValue('E12', 'Waktu');
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->setCellValue('F12', 'Keterangan');
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->setCellValue('G11', 'Pulang')->mergeCells('G11:H11');
        $sheet->setCellValue('G12', 'Waktu');
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->setCellValue('H12', 'Keterangan');
        $sheet->getColumnDimension('H')->setWidth(25);

        $sheet->setCellValue('B13', 'Nama')->mergeCells('B11:B12');
        $sheet->setCellValue('C11', 'Status Absen')->mergeCells('C11:C12');

        $sheet->getStyle('A:H')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:H12')->getFont()->setBold(true);
        $sheet->getRowDimension(11)->setRowHeight(30);
        $sheet->getRowDimension(12)->setRowHeight(30);

        $sheet->getStyle('A11:H12')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');


        $cell = 13;

        foreach ($data['data'] as $index => $value) {
            $sheet->getRowDimension($cell)->setRowHeight(30);
            $sheet->setCellValue('A' . $cell, $index + 1);
            $sheet->setCellValue('B' . $cell, date('d/m/y', strtotime($value['tanggal_absen'])));
            $sheet->setCellValue('C' . $cell, ucfirst($value['status']));
            $sheet->setCellValue('D' . $cell, $value['shift']);
            $sheet->setCellValue('E' . $cell, $value['waktu_masuk']);
            $sheet->setCellValue('F' . $cell, $value['keterangan_masuk']);
            $sheet->setCellValue('G' . $cell, $value['waktu_keluar']);
            $sheet->setCellValue('H' . $cell, $value['keterangan_pulang']);
            $cell++;
        }


        $sheet->getStyle('A5:H9')->getFont()->setSize(12);

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];


        $sheet->getStyle('A11:H' . $cell)->applyFromArray($border);
        $sheet->getStyle('A11:H' . $cell)->getAlignment()->setVertical('center')->setHorizontal('center');

        $cell++;
        $sheet->setCellValue('A' . $cell, ' ')->mergeCells('A' . $cell . ':G' . $cell);
        $cell++;

        $cell_str = $cell;
        $sheet->setCellValue('A' . $cell, 'Keterangan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, 'Volume');
        $sheet->setCellValue('D' . $cell, 'Satuan');

        $sheet->getRowDimension($cell)->setRowHeight(25);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFont()->setBold(true);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');

        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Jumlah hari kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['jml_hari_kerja']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Kehadiran kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['kehadiran_kerja']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Tanpa keterangan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['tanpa_keterangan']);
        $sheet->setCellValue('D' . $cell, 'Hari');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Potongan tanpa keterangan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['potongan_tanpa_keterangan']);
        $sheet->setCellValue('D' . $cell, '%');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Potongan masuk kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['potongan_masuk_kerja']);
        $sheet->setCellValue('D' . $cell, '%');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Potongan pulang kerja')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['potongan_pulang_kerja']);
        $sheet->setCellValue('D' . $cell, '%');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Potongan apel')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['potongan_apel']);
        $sheet->setCellValue('D' . $cell, '%');
        $sheet->getRowDimension($cell)->setRowHeight(20);
        $cell = $cell + 1;
        $sheet->setCellValue('A' . $cell, 'Total potongan')->mergeCells('A' . $cell . ':B' . $cell);
        $sheet->setCellValue('C' . $cell, $data['jml_potongan_kehadiran_kerja']);
        $sheet->setCellValue('D' . $cell, '%');
        $sheet->getRowDimension($cell)->setRowHeight(25);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFont()->setBold(true);
        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E1F5FE');

        $sheet->getStyle('A' . $cell_str . ':D' . $cell)->applyFromArray($border);
        $sheet->getStyle('A' . $cell_str . ':D' . $cell)->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('A' . $cell_str + 1 . ':A' . $cell)->getAlignment()->setHorizontal('left');


        if ($type == 'excel') {
            // Untuk download 
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $periode = $tanggal_awal . ' s/d ' . $tanggal_akhir;
            $filename = "Laporan Absen {$pegawai_info->nama}_$pegawai_info->nip {$periode}.xlsx";
            header("Content-Disposition: attachment;filename=\"$filename\"");
        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }

    public function export_to_kinerja_pegawai()
    {
        $type = request('type');
        $pegawai_params = request('pegawai') ? request('pegawai') : Auth::user()->id_pegawai;
        $bulan = request('bulan');
        $role = hasRole();
        $role_check = 0;

        if ($role['guard'] == 'web' && $role['role'] == '2') {
            $role_check = 1;
        }

        $jabatan_req = request("status");
        $pegawai = $this->findPegawai($pegawai_params, $jabatan_req, $role_check);
        $checkJabatan = $this->checkJabatanDefinitif($pegawai_params, $jabatan_req, $role_check);

        $data = array();

        if ($checkJabatan) {
            $atasan = $this->findAtasan($pegawai_params);
            $data = $this->data_kinerja_pegawai($pegawai_params, $checkJabatan, $bulan);

            return $this->export_kinerja_pegawai($data, $type, $pegawai, $atasan, $bulan);

        } else {
            return redirect()->back()->withErrors(['error' => 'Belum bisa membuka laporan, pegawai tersebut belum mempunyai jabatan']);
        }
    }

    public function export_kinerja_pegawai($data, $type, $pegawai, $atasan, $bulan)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('BKPSDM BULUKUMBA')
            ->setLastModifiedBy('BKPSDM BULUKUMBA')
            ->setTitle('Laporan Pembayaran TPP')
            ->setSubject('Laporan Pembayaran TPP')
            ->setDescription('Laporan Pembayaran TPP')
            ->setKeywords('pdf php')
            ->setCategory('LAPORAN Pembayaran TPP');
        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);

        $sheet->getRowDimension(1)->setRowHeight(17);
        $sheet->getRowDimension(2)->setRowHeight(17);
        $sheet->getRowDimension(3)->setRowHeight(7);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $sheet->setCellValue('A1', 'LAPORAN KINERJA PEGAWAI (AKTIVITAS)')->mergeCells('A1:J1');
        $sheet->setCellValue('A2', strtoupper(konvertBulan($bulan)))->mergeCells('A2:J2');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $spreadsheet->getActiveSheet()->getStyle('A1:F4')->getFont()->setBold(true);


        $sheet->setCellValue('A4', 'PEGAWAI YANG DINILAI')->mergeCells('A4:E4');
        $sheet->setCellValue('F4', 'PEJABAT PENILAI')->mergeCells('F4:K4');
        $sheet->getStyle('A4:F4')->getAlignment()->setHorizontal('center');

        $sheet->getStyle('A4:K4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BCCBE1');

        $sheet->setCellValue('A5', ' Nama')->mergeCells('A5:C5');
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->setCellValue('D5', ' ' . $pegawai->nama)->mergeCells('D5:E5');
        $sheet->getColumnDimension('D')->setWidth(45);

        $sheet->setCellValue('F5', ' Nama');
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->setCellValue('G5', ' ' . ($atasan !== null ? $atasan->nama : '-'))->mergeCells('G5:K5');
        $sheet->getColumnDimension('G')->setWidth(45);

        $sheet->setCellValue('A6', ' NIP')->mergeCells('A6:C6');
        $sheet->setCellValue('D6', " " . $pegawai->nip)->mergeCells('D6:E6');

        $sheet->setCellValue('F6', ' NIP');
        $sheet->setCellValue('G6', " " . ($atasan !== null ? $atasan->nip : '-'))->mergeCells('G6:K6');

        $golongan_pegawai = '';
        $golongan_atasan = '';

        $pegawai->golongan !== null ? $golongan_pegawai = $pegawai->golongan : $golongan_pegawai = '-';
        $atasan && $atasan->golongan !== null ? $golongan_atasan = $atasan->golongan : $golongan_atasan = '-';

        $sheet->setCellValue('A7', ' Pangkat / Gol Ruang')->mergeCells('A7:C7');
        $sheet->setCellValue('D7', ' ' . $golongan_pegawai)->mergeCells('D7:E7');

        $sheet->setCellValue('F7', ' Pangkat / Gol Ruang');
        $sheet->setCellValue('G7', ' ' . $golongan_atasan)->mergeCells('G7:K7');

        $sheet->setCellValue('A8', ' Jabatan')->mergeCells('A8:C8');
        $sheet->setCellValue('D8', ' ' . $pegawai->nama_jabatan)->mergeCells('D8:E8');

        $sheet->setCellValue('F8', ' Jabatan');
        $sheet->setCellValue('G8', ' ' . ($atasan !== null ? $atasan->nama_jabatan : '-'))->mergeCells('G8:K8');

        $sheet->setCellValue('A9', ' Unit kerja')->mergeCells('A9:C9');
        $sheet->setCellValue('D9', ' ' . $pegawai->nama_unit_kerja)->mergeCells('D9:E9');

        $sheet->setCellValue('F9', ' Unit kerja');
        $sheet->setCellValue('G9', ' ' . ($atasan !== null ? $atasan->nama_unit_kerja : '-'))->mergeCells('G9:K9');

        $spreadsheet->getActiveSheet()->getRowDimension('4')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getRowDimension('5')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getRowDimension('6')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getRowDimension('7')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getRowDimension('8')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getRowDimension('9')->setRowHeight(20);
        $sheet->getStyle('A4:K9')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A4:K9')->getAlignment()->setVertical('center');
        $sheet->getStyle('A5:K9')->getAlignment()->setHorizontal('rigth');

        $border_header = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:K9')->applyFromArray($border_header);
        $sheet->setCellValue('A10', ' ');

        $sheet->setCellValue('A12', 'No');
        $sheet->setCellValue('B12', 'Tanggal');
        $sheet->setCellValue('C12', 'Aktifitas')->mergeCells('C12:E12');
        $sheet->setCellValue('F12', 'Keterangan aktivitas')->mergeCells('F12:G12');
        $sheet->setCellValue('H12', 'Hasil');
        $sheet->setCellValue('I12', 'Satuan');
        $sheet->setCellValue('J12', 'Waktu (menit)');
        $sheet->setCellValue('K12', 'Waktu di muat');
        $sheet->getStyle('I12')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J12')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('K12')->getAlignment()->setHorizontal('center');


        $spreadsheet->getActiveSheet()->getStyle('A12:K12')->getFont()->setBold(true);
        $sheet->getStyle('A12:K12')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BCCBE1');


        // $sheet->setCellValue('F12', 'Waktu (Menit)');

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(10);

        $cell = 13;

        $capaian_prod_kinerja = 0;

        foreach ($data as $key => $value) {
            if (count($value['aktivitas']) > 0) {
                $spreadsheet->getActiveSheet()->getRowDimension($cell)->setRowHeight(20);
                $sheet->setCellValue('A' . $cell, $key + 1);
                $sheet->getStyle('A' . $cell)->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('B' . $cell, " " . $value['rencana'])->mergeCells('B' . $cell . ':K' . $cell);
                $sheet->getStyle('A' . $cell . ':K' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('ECF1E0');
                $sheet->setCellValue('G' . $cell, '')->mergeCells('G' . $cell . ':H' . $cell);
                $sheet->getStyle('A' . $cell . ':K' . $cell)->getAlignment()->setVertical('center');

                $cell++;

                $index1 = $key + 1;
                $index2 = 0;

                foreach ($value['aktivitas'] as $k => $v) {
                    $spreadsheet->getActiveSheet()->getRowDimension($cell)->setRowHeight(20);
                    $selisih = strtotime($v->created_at) - strtotime($v->tanggal);
                    $selisih_hari = $selisih / (60 * 60 * 24);

                    $index2 = $k + 1;
                    $capaian_prod_kinerja += $v->total_waktu;
                    $sheet->setCellValue('A' . $cell, '');
                    $sheet->setCellValue('B' . $cell, " " . Carbon::createFromFormat('Y-m-d', $v->tanggal)->format('d/m/y'));
                    $sheet->getStyle('B' . $cell)->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('C' . $cell, " " . $v->aktivitas)->mergeCells('C' . $cell . ':E' . $cell);
                    $sheet->setCellValue('F' . $cell, " " . $v->keterangan)->mergeCells('F' . $cell . ':G' . $cell);
                    $sheet->setCellValue('H' . $cell, $v->volume);
                    $sheet->setCellValue('I' . $cell, $v->satuan);
                    $sheet->getStyle('H' . $cell . ':I' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->setCellValue('J' . $cell, $v->total_waktu);
                    $sheet->setCellValue('K' . $cell, date("d/m/y", strtotime($v->created_at)));
                    if ($selisih_hari >= 6) {
                        $sheet->getStyle('K' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('e83343');
                    }
                    $sheet->getStyle('A' . $cell . ':K' . $cell)->getAlignment()->setVertical('center');
                    $sheet->getStyle('J' . $cell)->getAlignment()->setHorizontal('center');
                    $cell++;
                }
            }
        }


        $target_produktivitas_kerja = 0;
        $nilai_produktivitas_kerja = 0;

        if ($pegawai->target_waktu !== null) {
            $target_produktivitas_kerja = $pegawai->target_waktu;
        }

        if ($capaian_prod_kinerja > 0 || $target_produktivitas_kerja > 0) {
            $nilai_produktivitas_kerja = $target_produktivitas_kerja ? ($capaian_prod_kinerja / $target_produktivitas_kerja) * 100 : 0;
        }

        if ($nilai_produktivitas_kerja > 100) {
            $nilai_produktivitas_kerja = 100;
        }



        for ($i = 0; $i < 3; $i++) {
            if ($i == 0) {
                $sheet->setCellValue('B' . $cell, ' Capaian Produktivitas Kerja (Menit)')->mergeCells('B' . $cell . ':I' . $cell);
                $sheet->setCellValue('K' . $cell, $capaian_prod_kinerja);
                $sheet->getStyle('K' . $cell)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $cell . ':K' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('ECF1E0');
            } elseif ($i == 1) {
                $sheet->setCellValue('B' . $cell, ' Target Produktivitas Kerja (Menit)')->mergeCells('B' . $cell . ':I' . $cell);
                $sheet->setCellValue('K' . $cell, $target_produktivitas_kerja);
                $sheet->getStyle('K' . $cell)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $cell . ':K' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('ECF1E0');
            } else {
                $sheet->setCellValue('B' . $cell, ' Nilai Produktifitas Kerja (%)')->mergeCells('B' . $cell . ':I' . $cell);
                $sheet->setCellValue('K' . $cell, round($nilai_produktivitas_kerja, 2));
                $sheet->getStyle('K' . $cell)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A' . $cell . ':K' . $cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BCCBE1');
            }
            $spreadsheet->getActiveSheet()->getRowDimension($cell)->setRowHeight(20);
            $sheet->getStyle('A' . $cell . ':K' . $cell)->getAlignment()->setVertical('center');
            $spreadsheet->getActiveSheet()->getStyle('A' . $cell . ':K' . $cell)->getFont()->setBold(true);
            $cell++;
        }

        $tahun_n = session('tahun_penganggaran') ? session('tahun_penganggaran') : date('Y');

        $tgl_cetak = date("t", strtotime((int) $tahun_n)) . ' ' . strftime('%B %Y', mktime(0, 0, 0, date("n") + 1, 0, (int) $tahun_n));


        $border_row = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A12:K' . $cell)->applyFromArray($border_row);

        $cell++;

        $sheet->setCellValue('H' . ++$cell, 'BULUKUMBA, ' . $tgl_cetak)->mergeCells('H' . $cell . ':J' . $cell);
        $sheet->getStyle('H' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('H' . ++$cell, 'Pejabat Penilai Kinerja')->mergeCells('H' . $cell . ':J' . $cell);
        $sheet->getStyle('H' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B' . $cell, 'Pegawai dinilai')->mergeCells('B' . $cell . ':D' . $cell);
        $cell_pegawai = $cell + 1;
        $cell = $cell + 3;
        // $sheet->setCellValue('B' . ++$cell_pegawai, $pegawai->nama)->mergeCells('B' . $cell_pegawai . ':D' . $cell_pegawai);

        $sheet->setCellValue('H' . ++$cell, $atasan ? $atasan->nama : '')->mergeCells('H' . $cell . ':J' . $cell);
        $sheet->getStyle('H' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('H' . ++$cell, $atasan ? $atasan->nip : '')->mergeCells('H' . $cell . ':J' . $cell);
        $sheet->getStyle('H' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B' . $cell - 1, $pegawai->nama)->mergeCells('B' . $cell - 1 . ':D' . $cell - 1);
        $sheet->setCellValue('B' . $cell, $pegawai->nip)->mergeCells('B' . $cell . ':D' . $cell);
        $sheet->getStyle('B' . $cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);




        $sheet->getStyle('A12:K12')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A12:K12')->getAlignment()->setVertical('center');
        // $sheet->getStyle('B6:C' . $cell)->getAlignment()->setHorizontal('rigth');



        if ($type == 'excel') {

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $bulan_tmt = strtoupper(konvertBulan($bulan));
            $filename = "LAPORAN KINERJA {$pegawai->nama} BULAN {$bulan_tmt}.xlsx";
            header("Content-Disposition: attachment;filename=\"$filename\"");
        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }

    public function export_to_tpp_pegawai()
    {
        $type = request('type');
        $pegawai_params = request('pegawai') ? request('pegawai') : Auth::user()->id_pegawai;
        $bulan = request('bulan');
        $jabatan_req = request("status");

        $pegawai = $this->findPegawai($pegawai_params, $jabatan_req);
        $checkJabatan = $this->checkJabatanDefinitif($pegawai_params, $jabatan_req);

        $data = array();
        if ($checkJabatan) {
            $atasan = $this->findAtasan($pegawai_params);
            // if ($atasan) {
            $data = $this->data_tpp_pegawai($pegawai_params, $bulan);
            return $this->export_tpp_pegawai($data, $type, $pegawai, $atasan, $bulan);
        } else {
            return redirect()->back()->withErrors(['error' => 'Belum bisa membuka laporan, pegawai tersebut belum mempunyai jabatan']);
        }
    }

    public function export_tpp_pegawai($data, $type, $pegawai, $atasan, $bulan)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('BKPSDM BULUKUMBA')
            ->setLastModifiedBy('BKPSDM BULUKUMBA')
            ->setTitle('Laporan Pembayaran TPP')
            ->setSubject('Laporan Pembayaran TPP')
            ->setDescription('Laporan Pembayaran TPP')
            ->setKeywords('pdf php')
            ->setCategory('LAPORAN Pembayaran TPP');
        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);

        $sheet->getRowDimension(1)->setRowHeight(17);
        $sheet->getRowDimension(2)->setRowHeight(17);
        $sheet->getRowDimension(3)->setRowHeight(7);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $sheet->setCellValue('A1', 'LAPORAN KINERJA PEGAWAI (AKTIVITAS)')->mergeCells('A1:J1');
        $sheet->setCellValue('A2', strtoupper(konvertBulan($bulan)))->mergeCells('A2:J2');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $spreadsheet->getActiveSheet()->getStyle('A1:F4')->getFont()->setBold(true);


        $sheet->setCellValue('A4', 'PEGAWAI YANG DINILAI')->mergeCells('A4:D4');
        $sheet->setCellValue('E4', 'PEJABAT PENILAI')->mergeCells('E4:H4');
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A5', ' Nama')->mergeCells('A5:B5');
        $sheet->setCellValue('C5', ' ' . $pegawai->nama)->mergeCells('C5:D5');

        $sheet->setCellValue('E5', ' Nama')->mergeCells('E5:F5');
        $sheet->setCellValue('G5', ' ' . ($atasan !== null ? $atasan->nama : '-'))->mergeCells('G5:H5');

        $sheet->setCellValue('A6', ' NIP')->mergeCells('A6:B6');
        $sheet->setCellValue('C6', " " . $pegawai->nip)->mergeCells('C6:D6');

        $sheet->setCellValue('E6', ' NIP')->mergeCells('E6:F6');
        $sheet->setCellValue('G6', " " . ($atasan !== null ? $atasan->nip : '-'))->mergeCells('G6:H6');

        $golongan_pegawai = '';
        $golongan_atasan = '';

        $pegawai->golongan !== null ? $golongan_pegawai = $pegawai->golongan : $golongan_pegawai = '-';
        $atasan->golongan !== null ? $golongan_atasan = $atasan->golongan : $golongan_atasan = '-';

        $sheet->setCellValue('A7', ' Pangkat / Gol Ruang')->mergeCells('A7:B7');
        $sheet->setCellValue('C7', $golongan_pegawai)->mergeCells('C7:D7');

        $sheet->setCellValue('E7', ' Pangkat / Gol Ruang')->mergeCells('E7:F7');
        $sheet->setCellValue('G7', ' ' . $golongan_atasan)->mergeCells('G7:H7');

        $sheet->setCellValue('A8', ' Jabatan')->mergeCells('A8:B8');
        $sheet->setCellValue('C8', ' ' . $pegawai->nama_jabatan)->mergeCells('C8:D8');

        $sheet->setCellValue('E8', ' Jabatan')->mergeCells('E8:F8');
        $sheet->setCellValue('G8', ' ' . ($atasan !== null ? $atasan->nama_jabatan : '-'))->mergeCells('G8:H8');

        $sheet->setCellValue('A9', ' Unit kerja')->mergeCells('A9:B9');
        $sheet->setCellValue('C9', ' ' . ($pegawai !== null ? $pegawai->nama_unit_kerja : '-'))->mergeCells('C9:D9');

        $sheet->setCellValue('E9', ' Unit kerja')->mergeCells('E9:F9');
        $sheet->setCellValue('G9', ' ' . ($atasan !== null ? $atasan->nama_unit_kerja : '-'))->mergeCells('G9:H9');

        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(30);

        $border_header = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:H9')->applyFromArray($border_header);
        $sheet->setCellValue('A10', ' ');

        $sheet->setCellValue('A11', 'No');
        $sheet->setCellValue('B11', 'Uraian')->mergeCells('B11:F11');
        $sheet->setCellValue('G11', 'Persentase');
        $sheet->setCellValue('H11', 'Total');

        $sheet->getStyle('A11:H11')->getAlignment()->setHorizontal('center');
        $spreadsheet->getActiveSheet()->getStyle('A11:H11')->getFont()->setBold(true);


        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);

        $cell = 12;

        $sheet->setCellValue('A12', 'A');
        $sheet->setCellValue('B12', 'PAGU TPP')->mergeCells('B12:F12');
        $sheet->setCellValue('H12', $data['nilaiPaguTpp']);

        $sheet->getStyle('A12:H12')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D3DFE2');
        $spreadsheet->getActiveSheet()->getStyle('A12:H12')->getFont()->setBold(true);

        $sheet->setCellValue('A13', '1');
        $sheet->setCellValue('B13', 'Kinerja Maks')->mergeCells('B13:F13');
        $sheet->setCellValue('G13', $data['persen_kinerja_maks'] . ' %');
        $sheet->setCellValue('H13', $data['kinerja_maks']);

        $sheet->setCellValue('A14', '2');
        $sheet->setCellValue('B14', 'Kehadiran Maks')->mergeCells('B14:F14');
        $sheet->setCellValue('G14', $data['persen_kehadiran_maks'] . ' %');
        $sheet->setCellValue('H14', $data['kehadiran_maks']);

        $sheet->setCellValue('A15', 'B');
        $sheet->setCellValue('B15', 'Potongan')->mergeCells('B15:F15');
        $spreadsheet->getActiveSheet()->getStyle('A15:H15')->getFont()->setBold(true);

        $sheet->setCellValue('A16', '1');
        $sheet->setCellValue('B16', 'Potongan Kehadiran')->mergeCells('B16:F16');
        $sheet->setCellValue('G16', $data['pembagi_nilai_kehadiran'] . ' %');
        $sheet->setCellValue('H16', $data['potongan_kehadiran']);

        $sheet->setCellValue('A17', '2');
        $sheet->setCellValue('B17', 'Potongan Kinerja')->mergeCells('B17:F17');
        $sheet->setCellValue('G17', $data['persen_potongan_kinerja'] . ' %');
        $sheet->setCellValue('H17', $data['potongan_kinerja']);

        //  $sheet->setCellValue('A17', '3');
        //  $sheet->setCellValue('B17', 'BPJS')->mergeCells('B17:F17');
        //  $sheet->setCellValue('G17', '1%');
        //  $sheet->setCellValue('H17', $data['bpjs']);

        $sheet->setCellValue('A18', 'C');
        $sheet->setCellValue('B18', 'Nilai Bruto')->mergeCells('B18:F18');
        $sheet->setCellValue('H18', $data['nilai_bruto']);

        $sheet->getStyle('A18:H18')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EDCDCC');
        $spreadsheet->getActiveSheet()->getStyle('A18:H18')->getFont()->setBold(true);

        $sheet->setCellValue('A19', '4');
        $sheet->setCellValue('B19', 'PPH21')->mergeCells('B19:F19');
        $sheet->setCellValue('G19', $data['perkalian_pph'] . '%');
        $sheet->setCellValue('H19', $data['pphPsl']);

        // $sheet->setCellValue('A22', 'E');
        //  $sheet->setCellValue('B22', 'Nilai Bruto SPM')->mergeCells('B22:F22');
        //  $sheet->setCellValue('H22', $data['brutoSpm']);
        //  $sheet->getStyle('A22:H22')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCD9F5');
        //  $spreadsheet->getActiveSheet()->getStyle('A22:H22')->getFont()->setBold(true);

        $sheet->setCellValue('A20', 'E');
        $sheet->setCellValue('B20', 'TPP Netto')->mergeCells('B20:F20');
        $sheet->setCellValue('H20', $data['tppNetto']);

        $sheet->getStyle('A20:H20')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDEAD5');
        $spreadsheet->getActiveSheet()->getStyle('A20:H20')->getFont()->setBold(true);

        //  $sheet->setCellValue('A21', '5');
        //  $sheet->setCellValue('B21', 'Iuran (Dibayarkan oleh Pemda)')->mergeCells('B21:F21');
        //  $sheet->setCellValue('G21', '4%');
        //  $sheet->setCellValue('H21', $data['iuran']);

        $sheet->getStyle('A12:A22')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('H12:H22')->getAlignment()->setHorizontal('right');
        //  $sheet->getStyle('A18')->getAlignment()->setHorizontal('center');
        //  $sheet->getStyle('A20')->getAlignment()->setHorizontal('center');
        //  $sheet->getStyle('A22')->getAlignment()->setHorizontal('center');

        $border_row = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A11:H20')->applyFromArray($border_row);


        if ($type == 'excel') {

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Laporan TPP ' . $pegawai->nama . '".xlsx');

        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }

    public function data_kinerja_pegawai($pegawai, $jabatan, $bulan)
    {
        $tahun = session('tahun_penganggaran') ? session('tahun_penganggaran') : date('Y');
        $dataArray = SasaranKinerja::query()
            ->select('id', 'id_satuan_kerja', 'rencana', 'id_jabatan', 'tahun')
            ->with([
                'aktivitas' => function ($query) use ($bulan, $jabatan, $tahun, $pegawai) {
                    $query->select('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at', DB::raw('SUM(id) as total_id'), DB::raw('SUM(volume) as total_volume'), DB::raw('SUM(waktu) as total_waktu'));
                    $query->groupBy('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at');
                    $query->whereMonth('tanggal', $bulan);
                    $query->where('tahun', $tahun);
                    $query->where('id_pegawai', $pegawai);
                    $query->where("validation", 1);
                    $query->orderBy('tanggal', 'ASC');
                }
            ])
            ->where('tahun', $tahun)
            ->where('id_jabatan', $jabatan->id_jabatan)
            ->orderBy('created_at', 'DESC')
            ->get();


        $aktivitas_sasaran_tanpa_jabatan = DB::table('tb_aktivitas')
            ->select('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at', DB::raw('SUM(id) as total_id'), DB::raw('SUM(volume) as total_volume'), DB::raw('SUM(waktu) as total_waktu'))
            ->where('id_pegawai', $pegawai)
            ->where('id_sasaran', '>', 0)
            ->whereMonth('tanggal', $bulan)
            ->where('tahun', $tahun)
            ->where("validation", 1)
            ->groupBy('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at')
            ->orderBy('tanggal', 'ASC')
            ->get();

        $aktivitas_tambahan = [];

        foreach ($aktivitas_sasaran_tanpa_jabatan as $aktivitas) {
            $id_sasaran = $aktivitas->id_sasaran;
            $sasaranAda = false;
            foreach ($dataArray as $skp) {
                foreach ($skp->aktivitas as $item) {
                    if ($item->id_sasaran == $id_sasaran) {
                        $sasaranAda = true;
                        break 2;
                    }
                }
            }
            if (!$sasaranAda) {
                $aktivitas_tambahan[] = $aktivitas;
            }
        }


        $aktivitas_non_sasaran = DB::table('tb_aktivitas')
            ->select('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at', DB::raw('SUM(id) as total_id'), DB::raw('SUM(volume) as total_volume'), DB::raw('SUM(waktu) as total_waktu'))
            ->where('id_pegawai', $pegawai)
            ->where('id_sasaran', 0)
            ->whereMonth('tanggal', $bulan)
            ->where('tahun', $tahun)
            ->where("validation", 1)
            ->groupBy('id_sasaran', 'tanggal', 'aktivitas', 'keterangan', 'volume', 'satuan', 'created_at')
            ->orderBy('tanggal', 'ASC')
            ->get();

        // dd($aktivitas_non_sasaran);


        // Konversi $aktivitas_tambahan menjadi objek Laravel Collection
        $aktivitas_tambahan_collection = collect($aktivitas_tambahan);

        // Gabungkan dua koleksi menggunakan metode merge()
        $aktivitas_group_non_sasaran_jabatan = $aktivitas_non_sasaran->merge($aktivitas_tambahan_collection);


        $skp_tmt = [
            'id_satuan_kerja' => 0,
            'rencana' => '-',
            'id_jabatan' => 0,
            'tahun' => date('Y'),
            'aktivitas' => $aktivitas_group_non_sasaran_jabatan
        ];

        $dataArray[] = $skp_tmt;
        return $dataArray;
    }

    public function data_tpp_pegawai($pegawai, $bulan)
    {
        $tahun = session('tahun_penganggaran');
        $tanggal_awal = date("$tahun-$bulan-01");
        $tanggal_akhir = date("Y-m-t", strtotime($tanggal_awal));

        $data = DB::table('tb_pegawai')
            ->selectRaw('
            tb_pegawai.id,
            tb_pegawai.nama,
            tb_pegawai.nip,
            tb_pegawai.golongan,
            tb_pegawai.tipe_pegawai,
            tb_master_jabatan.nama_jabatan,
            tb_jabatan.target_waktu,
            tb_master_jabatan.kelas_jabatan,
            tb_jabatan.pagu_tpp,
            tb_master_jabatan.jenis_jabatan,
            tb_master_jabatan.level_jabatan,
            tb_jabatan.pembayaran,
            tb_unit_kerja.waktu_masuk,
            tb_unit_kerja.waktu_keluar,
            tb_unit_kerja.jumlah_shift,
            (SELECT SUM(waktu) FROM tb_aktivitas WHERE tb_aktivitas.id_pegawai = tb_pegawai.id AND tb_aktivitas.validation = 1 AND tahun = ? AND  MONTH(tanggal) = ? LIMIT 1) as capaian_waktu', [$tahun, $bulan])
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
            ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
            ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
            ->where('tb_pegawai.id', $pegawai)
            ->groupBy('tb_pegawai.id', 'tb_pegawai.nama', 'tb_pegawai.nip', 'tb_pegawai.golongan', 'tb_master_jabatan.nama_jabatan', 'tb_jabatan.target_waktu', 'tb_master_jabatan.kelas_jabatan', 'tb_jabatan.pagu_tpp', 'tb_master_jabatan.jenis_jabatan', 'tb_master_jabatan.level_jabatan', 'tb_jabatan.pembayaran', 'tb_unit_kerja.waktu_masuk', 'tb_unit_kerja.waktu_keluar', 'tb_unit_kerja.jumlah_shift')
            ->first();

        $child = $this->data_kehadiran_pegawai($data->id, $tanggal_awal, $tanggal_akhir, $data->waktu_masuk, $data->waktu_keluar, $data->tipe_pegawai, $data->jumlah_shift);
        $data->jml_potongan_kehadiran_kerja = $child['jml_potongan_kehadiran_kerja'];
        $data->tanpa_keterangan = $child['tanpa_keterangan'];
        $data->jml_hari_kerja = $child['jml_hari_kerja'];
        $data->jml_hadir = $child['jml_hadir'];
        $data->jml_sakit = $child['jml_sakit'];
        $data->jml_cuti = $child['jml_cuti'];
        $data->jml_dinas_luar = $child['jml_dinas_luar'];
        $data->jml_tidak_apel = $child['jml_tidak_apel'];
        $data->potongan_apel = $child['potongan_apel'];
        $data->jml_menit_terlambat_masuk_kerja = $child['jml_menit_terlambat_masuk_kerja'];
        $data->jml_menit_terlambat_pulang_kerja = $child['jml_menit_terlambat_pulang_kerja'];

        $jmlPaguTpp = 0;
        $jmlNilaiKinerja = 0;
        $jmlNilaiKehadiran = 0;
        $jmlBpjs = 0;
        $jmlTppBruto = 0;
        $jmlPphPsl = 0;
        $jmlTppNetto = 0;
        $jmlBrutoSpm = 0;
        $jmlIuran = 0;
        $nilai_kinerja = 0;
        $target_nilai = 0;

        $capaian_prod = 0;
        $target_prod = 0;
        $nilaiKinerja = 0;
        $nilai_kinerja = 0;
        $keterangan = '';
        $kelas_jabatan = '';
        $golongan = '';


        $golongan = '-';
        if ($data->golongan !== null && str_contains($data->golongan, '/')) {
            $golonganParts = explode("/", $data->golongan);
            $golongan = isset($golonganParts[1]) ? $golonganParts[1] : '-';
        }
        $data->target_waktu !== null ? $target_nilai = $data->target_waktu : $target_nilai = 0;

        $target_nilai > 0 ? $nilai_kinerja = (intval($data->capaian_waktu) / $target_nilai) * 100 : $nilai_kinerja = 0;

        if ($nilai_kinerja > 100) {
            $nilai_kinerja = 100;
        }

        $pembagi_nilai_kinerja = 0;
        $pembagi_nilai_kehadiran = 0;

        $nilaiPaguTpp = $data->pagu_tpp * $data->pembayaran / 100;

        $nilai_kinerja_rp = $nilaiPaguTpp * 60 / 100;
        $nilaiKinerja = $nilai_kinerja * $nilai_kinerja_rp / 100;
        $persentaseKehadiran = 40 * $nilaiPaguTpp / 100;
        $nilaiKehadiran = $persentaseKehadiran * $data->jml_potongan_kehadiran_kerja / 100;
        $jumlahKehadiran = $persentaseKehadiran - $nilaiKehadiran;
        $bpjs = 1 * $nilaiPaguTpp / 100;
        $data->tanpa_keterangan > 3 || $data->potongan_apel > 40 ? $keterangan = 'TMS' : $keterangan = 'MS';
        $tppBruto = 0;
        $iuran = 4 * $nilaiPaguTpp / 100;
        if ($keterangan === 'TMS') {
            $tppBruto = 0;
            $bpjs = 0;
            $iuran = 0;
            $brutoSpm = 0;
        } else {
            $tppBruto = $nilaiKinerja + $jumlahKehadiran - $bpjs;
            $brutoSpm = $nilaiKinerja + $jumlahKehadiran + $iuran;
        }

        $perkalian_pph = 0;
        if (strstr($golongan, 'IV')) {
            $perkalian_pph = 15;
            $pphPsl = 15 * $tppBruto / 100;

        } elseif (strstr($golongan, 'III')) {
            $perkalian_pph = 5;
            $pphPsl = $perkalian_pph * $tppBruto / 100;
        } else {
            $pphPsl = 0;
        }
        $tppNetto = $tppBruto - $pphPsl;

        $tpp_bulan_ini = $tppNetto;
        $potongan_jkn_pph_tmt = $pphPsl + $bpjs;

        return [
            'kinerja_maks' => 'Rp. ' . number_format($nilai_kinerja_rp),
            'persen_kinerja_maks' => round($nilai_kinerja, 2),
            'kehadiran_maks' => 'Rp. ' . number_format($persentaseKehadiran),
            'persen_kehadiran_maks' => $data->jml_potongan_kehadiran_kerja,
            'potongan_kinerja' => 'Rp. ' . number_format($nilaiPaguTpp * (100 - $nilai_kinerja) / 100),
            'persen_potongan_kinerja' => round((100 - $nilai_kinerja), 2),
            'potongan_kehadiran' => 'Rp. ' . number_format($nilaiKehadiran),
            'persentase_potongan_kehadiran' => $data->jml_potongan_kehadiran_kerja,
            'bpjs' => $bpjs,
            'pphPsl' => number_format($pphPsl),
            'nilai_bruto' => 'Rp ' . number_format($tppBruto),
            'tppNetto' => number_format($tppNetto),
            'brutoSpm' => number_format($brutoSpm),
            'nilaiPaguTpp' => 'Rp. ' . number_format($nilaiPaguTpp),
            'iuran' => 'Rp. ' . number_format($nilaiPaguTpp * 4 / 100),
            'jml_hari_kerja' => $data->jml_hari_kerja,
            'jml_hadir' => $data->jml_hadir,
            'jml_sakit' => $data->jml_sakit,
            'jml_cuti' => $data->jml_cuti,
            'jml_dinas_luar' => $data->jml_dinas_luar,
            'jml_tidak_apel' => $data->jml_tidak_apel,
            'potongan_apel' => $data->potongan_apel,
            'tanpa_keterangan' => $data->tanpa_keterangan,
            'pembagi_nilai_kehadiran' => $pembagi_nilai_kehadiran,
            'pembagi_nilai_kinerja' => $pembagi_nilai_kinerja,
            'capaian' => number_format($nilaiKinerja),
            'jumlahKehadiran' => 'Rp ' . number_format($jumlahKehadiran),
            'potongan_jkn_pph' => $pphPsl + $bpjs,
            'total_tpp_bruto' => $nilaiKinerja + $jumlahKehadiran,
            'tpp_bulan_ini' => 'Rp. ' . number_format($tpp_bulan_ini),
            'potongan_jkn_pph_tmt' => 'Rp. ' . number_format($potongan_jkn_pph_tmt),
            'perkalian_pph' => $perkalian_pph,
            'terlambat_cepat_pulang' => $data->jml_menit_terlambat_masuk_kerja + $data->jml_menit_terlambat_pulang_kerja,
        ];
    }

}
