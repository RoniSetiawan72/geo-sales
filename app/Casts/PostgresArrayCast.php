<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PostgresArrayCast implements CastsAttributes
{
    /**
     * Cast nilai dari database (format Postgres {"a","b"}) menjadi array PHP.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (empty($value)) {
            return [];
        }

        // Menghapus kurung kurawal {} bawaan postgres
        $clean = str_replace(['{', '}'], '', $value);
        if ($clean === '') {
            return [];
        }

        // Memisahkan berdasarkan koma dan membersihkan tanda kutip double
        return array_map(function ($val) {
            return trim($val, '"');
        }, explode(',', $clean));
    }

    /**
     * Cast nilai dari array PHP ['a', 'b'] menjadi format Postgres {"a","b"} sebelum disimpan.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_array($value)) {
            $cleanValues = array_map(function ($val) {
                // Escape jika ada tanda kutip di dalam nilai string
                return '"' . str_replace('"', '\\"', $val) . '"';
            }, $value);

            return '{' . implode(',', $cleanValues) . '}';
        }

        return $value;
    }
}
