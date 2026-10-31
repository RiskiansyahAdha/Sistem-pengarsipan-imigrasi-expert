<?php

namespace App\Models;

use App\Support\StatistikUmur;
use Illuminate\Database\Eloquent\Model;

class Pemohon extends Model
{
    protected $fillable = ['nomor', 'nama', 'tanggal_lahir', 'tanggal_masuk', 'jenis', 'umur', 'bulan', 'tahun'];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'umur' => 'integer',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    /** Nama bulan dalam bahasa Indonesia: $pemohon->nama_bulan */
    public function getNamaBulanAttribute(): string
    {
        return StatistikUmur::NAMA_BULAN[$this->bulan] ?? '-';
    }

    /** "Senin, 27-10-2026" atau "Tidak ada data masuk" bila kolom D kosong. */
    public function getTanggalMasukLabelAttribute(): string
    {
        if ($this->tanggal_masuk === null) {
            return 'Tidak ada data masuk';
        }

        return StatistikUmur::HARI[(int) $this->tanggal_masuk->format('N')].', '
            .$this->tanggal_masuk->format('d-m-Y');
    }

    /** "Prioritas" / "Non prioritas": $pemohon->jenis_label */
    public function getJenisLabelAttribute(): string
    {
        return StatistikUmur::JENIS[$this->jenis] ?? '-';
    }
}
