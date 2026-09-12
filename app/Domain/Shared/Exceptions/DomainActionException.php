<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/**
 * Dilempar oleh service/action class saat sebuah aksi tidak dapat
 * dilanjutkan karena aturan bisnis (bukan karena input tidak valid —
 * itu tugas validation rules). Pesannya harus jelas dan aman ditampilkan
 * langsung ke user, mis. "Organisasi tidak dapat dihapus karena masih
 * memiliki user aktif."
 */
class DomainActionException extends RuntimeException {}
