<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class TransaksiExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export(): void
    {
        $this->get(route('transaksi.export'))->assertRedirect(route('login'));
    }

    public function test_export_writes_transaction_columns(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['name' => 'Social Trip Bandung']);
        $voucher = KodeVoucher::factory()->create(['id_event' => $event->id, 'kode' => 'HEMAT', 'nilai_diskon' => 25000]);
        Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => $voucher->id,
            'invoice' => 'INV-001',
            'name' => 'Budi',
            'jumlah_tiket' => 2,
            'total_pembayaran' => 150000,
            'status_pembayaran' => 'Success',
            'tanggal_register' => '2026-11-01 08:30:00',
            'tanggal_pembayaran' => '2026-11-02 09:45:00',
        ]);

        $sheet = $this->sheetFrom($this->get(route('transaksi.export'))->assertOk());

        $this->assertSame('Invoice', $sheet->getCell('A1')->getValue());
        $this->assertSame('Metode Pembayaran', $sheet->getCell('N1')->getValue());
        $this->assertSame('INV-001', $sheet->getCell('A2')->getValue());
        $this->assertSame('Social Trip Bandung', $sheet->getCell('B2')->getValue());
        $this->assertSame('HEMAT', $sheet->getCell('H2')->getValue());
        $this->assertEquals(25000, $sheet->getCell('I2')->getValue());
        $this->assertEquals(150000, $sheet->getCell('J2')->getValue());
        $this->assertSame('Success', $sheet->getCell('L2')->getValue());
        $this->assertSame('2026-11-01 08:30', Date::excelToDateTimeObject($sheet->getCell('K2')->getValue())->format('Y-m-d H:i'));
        $this->assertSame('2026-11-02 09:45', Date::excelToDateTimeObject($sheet->getCell('M2')->getValue())->format('Y-m-d H:i'));
        $this->assertSame('#,##0', $sheet->getStyle('J2')->getNumberFormat()->getFormatCode());
        $this->assertSame('dd-mm-yyyy hh:mm', $sheet->getStyle('K2')->getNumberFormat()->getFormatCode());
    }

    public function test_export_includes_utm_columns(): void
    {
        $this->actingAs(User::factory()->create());

        Transaksi::factory()->create([
            'utm_source' => 'instagram',
            'utm_medium' => 'story',
            'utm_campaign' => '=cmd|calc',
        ]);

        $sheet = $this->sheetFrom($this->get(route('transaksi.export'))->assertOk());

        $this->assertSame('UTM Source', $sheet->getCell('O1')->getValue());
        $this->assertSame('UTM Medium', $sheet->getCell('P1')->getValue());
        $this->assertSame('UTM Campaign', $sheet->getCell('Q1')->getValue());
        $this->assertSame('instagram', $sheet->getCell('O2')->getValue());
        $this->assertSame('story', $sheet->getCell('P2')->getValue());
        // Nilai UTM berasal dari URL publik: harus tetap teks, bukan formula.
        $this->assertFalse($sheet->getCell('Q2')->isFormula());
        $this->assertSame('=cmd|calc', $sheet->getCell('Q2')->getValue());
    }

    public function test_customer_input_is_written_as_text_not_formula_or_number(): void
    {
        $this->actingAs(User::factory()->create());

        Transaksi::factory()->create([
            'name' => '=HYPERLINK("http://evil.test","klik")',
            'telepon' => '628123456789',
        ]);

        $sheet = $this->sheetFrom($this->get(route('transaksi.export'))->assertOk());

        $this->assertFalse($sheet->getCell('C2')->isFormula());
        $this->assertSame('=HYPERLINK("http://evil.test","klik")', $sheet->getCell('C2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
        $this->assertSame('628123456789', $sheet->getCell('E2')->getValue());
    }

    public function test_zero_total_is_written_and_unpaid_date_is_blank(): void
    {
        $this->actingAs(User::factory()->create());

        Transaksi::factory()->create(['total_pembayaran' => 0, 'tanggal_pembayaran' => null]);

        $sheet = $this->sheetFrom($this->get(route('transaksi.export'))->assertOk());

        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('J2')->getDataType());
        $this->assertEquals(0, $sheet->getCell('J2')->getValue());
        $this->assertNull($sheet->getCell('M2')->getValue());
    }

    public function test_export_keeps_name_of_soft_deleted_event(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['name' => 'Event Lama']);
        Transaksi::factory()->create(['id_event' => $event->id]);
        $event->delete();

        $sheet = $this->sheetFrom($this->get(route('transaksi.export'))->assertOk());

        $this->assertSame('Event Lama', $sheet->getCell('B2')->getValue());
    }

    public function test_export_applies_active_filters_and_skips_soft_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create();
        Transaksi::factory()->create(['invoice' => 'COCOK', 'id_event' => $event->id, 'status_pembayaran' => 'Success', 'tanggal_register' => '2026-11-01 10:00:00']);
        Transaksi::factory()->create(['invoice' => 'STATUS-LAIN', 'id_event' => $event->id, 'status_pembayaran' => 'Pending', 'tanggal_register' => '2026-11-01 10:00:00']);
        Transaksi::factory()->create(['invoice' => 'EVENT-LAIN', 'status_pembayaran' => 'Success', 'tanggal_register' => '2026-11-01 10:00:00']);
        Transaksi::factory()->create(['invoice' => 'DI-LUAR-TANGGAL', 'id_event' => $event->id, 'status_pembayaran' => 'Success', 'tanggal_register' => '2026-10-01 10:00:00']);
        Transaksi::factory()->create(['invoice' => 'TERHAPUS', 'id_event' => $event->id, 'status_pembayaran' => 'Success', 'tanggal_register' => '2026-11-01 10:00:00'])->delete();

        $response = $this->get(route('transaksi.export', [
            'tanggal_awal' => '2026-11-01',
            'tanggal_akhir' => '2026-11-30',
            'id_event' => $event->id,
            'status_pembayaran' => 'Success',
        ]))->assertOk();

        $this->assertSame(['COCOK'], $this->invoicesIn($this->sheetFrom($response)));
    }

    public function test_export_applies_search(): void
    {
        $this->actingAs(User::factory()->create());

        Transaksi::factory()->create(['invoice' => 'INV-A', 'name' => 'Siti Aminah']);
        Transaksi::factory()->create(['invoice' => 'INV-B', 'name' => 'Budi Santoso']);

        $response = $this->get(route('transaksi.export', ['search' => 'Siti']))->assertOk();

        $this->assertSame(['INV-A'], $this->invoicesIn($this->sheetFrom($response)));
    }

    public function test_export_button_carries_active_filter_but_not_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('transaksi.filter', ['status_pembayaran' => 'Success', 'page' => 2]))
            ->assertOk()
            ->assertSee(route('transaksi.export', ['status_pembayaran' => 'Success']), false);
    }

    private function sheetFrom(TestResponse $response): Worksheet
    {
        // Binder bersifat statis: tanpa reset, binder export ikut memengaruhi reader dan semua angka terbaca teks.
        Cell::setValueBinder(new DefaultValueBinder);

        $reader = IOFactory::createReader('Xlsx');

        return $reader->load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
    }

    /**
     * @return array<int, string>
     */
    private function invoicesIn(Worksheet $sheet): array
    {
        $invoices = [];

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $invoices[] = $sheet->getCell("A{$row}")->getValue();
        }

        return $invoices;
    }
}
