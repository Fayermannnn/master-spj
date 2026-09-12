<?php

declare(strict_types=1);

use App\Domain\DocumentGenerator\Services\VariableResolver;
use App\Models\Payment;
use App\Models\Project;

/**
 * "Terbilang" (angka dieja jadi kalimat) diuji langsung lewat
 * `resolveScalar()` — bukan me-refleksikan method private — supaya
 * test tetap mengikuti kontrak publik yang sama dipakai
 * DocumentGeneratorService, bukan implementasi internalnya.
 */
function terbilangFor(float $amount): ?string
{
    $payment = Payment::factory()->make(['amount' => $amount]);

    return app(VariableResolver::class)->resolveScalar('payment.amount_terbilang', Project::factory()->make(), $payment);
}

it('spells out zero', function (): void {
    expect(terbilangFor(0))->toBe('Nol Rupiah');
});

it('spells out numbers under twenty using the belas suffix', function (): void {
    expect(terbilangFor(15))->toBe('Lima Belas Rupiah');
});

it('spells out tens', function (): void {
    expect(terbilangFor(20))->toBe('Dua Puluh Rupiah');
    expect(terbilangFor(75))->toBe('Tujuh Puluh Lima Rupiah');
});

it('spells out hundreds using seratus for exactly 100', function (): void {
    expect(terbilangFor(100))->toBe('Seratus Rupiah');
    expect(terbilangFor(250))->toBe('Dua Ratus Lima Puluh Rupiah');
});

it('spells out thousands using seribu for exactly 1000', function (): void {
    expect(terbilangFor(1_000))->toBe('Seribu Rupiah');
    expect(terbilangFor(5_500))->toBe('Lima Ribu Lima Ratus Rupiah');
});

it('spells out millions and billions', function (): void {
    expect(terbilangFor(1_000_000))->toBe('Satu Juta Rupiah');
    expect(terbilangFor(100_000_000))->toBe('Seratus Juta Rupiah');
    expect(terbilangFor(1_980_610_920))->toBe('Satu Miliar Sembilan Ratus Delapan Puluh Juta Enam Ratus Sepuluh Ribu Sembilan Ratus Dua Puluh Rupiah');
});

it('rounds fractional rupiah before spelling it out', function (): void {
    expect(terbilangFor(1_500_000.75))->toBe('Satu Juta Lima Ratus Ribu Satu Rupiah');
});
