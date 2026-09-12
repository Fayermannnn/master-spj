<?php

declare(strict_types=1);

use App\Domain\DocumentGenerator\Services\VariableResolver;
use App\Models\Personnel;
use App\Models\Project;

it('generates one attendance row per day in the given month with a blank signature column', function (): void {
    $rows = app(VariableResolver::class)->resolveTableRows('attendance', Project::factory()->make(), '2026-02');

    // 2026 bukan tahun kabisat -> Februari 28 hari.
    expect($rows)->toHaveCount(28);
    expect($rows[0]['attendance.date'])->toBe('01 Februari 2026');
    expect($rows[0]['attendance.day_name'])->not->toBeEmpty();
    expect($rows[0]['attendance.signature'])->toBe('.....................');
    expect($rows[27]['attendance.date'])->toBe('28 Februari 2026');
});

it('generates 31 rows for a 31-day month', function (): void {
    $rows = app(VariableResolver::class)->resolveTableRows('attendance', Project::factory()->make(), '2026-03');

    expect($rows)->toHaveCount(31);
});

it('returns no attendance rows when no month is given', function (): void {
    $rows = app(VariableResolver::class)->resolveTableRows('attendance', Project::factory()->make());

    expect($rows)->toBe([]);
});

it('resolves attendance.personnel_name and attendance.month_name scalars', function (): void {
    $resolver = app(VariableResolver::class);
    $project = Project::factory()->make();
    $personnel = Personnel::factory()->make(['name' => 'Budi Santoso']);

    expect($resolver->resolveScalar('attendance.personnel_name', $project, null, null, null, null, $personnel))->toBe('Budi Santoso');
    expect($resolver->resolveScalar('attendance.month_name', $project, null, null, null, null, null, '2026-03'))->toBe('Maret 2026');
});
