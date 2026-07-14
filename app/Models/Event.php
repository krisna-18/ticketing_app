<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'lokasi',
        'gambar',
        'tanggal_waktu',
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
    ];

    public function tikets()
    {
        return $this->hasMany(Tiket::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getStatusAttribute(): string
    {
        if (!$this->tanggal_waktu) {
            return 'Tanggal tidak tersedia';
        }
        $start = Carbon::parse($this->tanggal_waktu);
        $end = $start->copy()->addHours(3); 
        $now = Carbon::now();

        if ($now->lt($start)) {
            return 'Upcoming';
        } elseif ($now->between($start, $end)) {
            return 'Ongoing';
        } else {
            return 'Completed';
        }
    }

    public function hasSales(): bool
    {
        return $this->orders()->exists();
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('tanggal_waktu', '>', now());
    }
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('tanggal_waktu', '<=', now())
                     ->where('tanggal_waktu', '>=', now()->subHours(3));
    }
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('tanggal_waktu', '<', now()->subHours(3));
    }

    public function getImageUrlAttribute(): string
    {
        $gambar = $this->gambar;
        if (!$gambar && filter_var($gambar, FILTER_VALIDATE_URL)) {
            return $gambar;
        }
        if ($gambar && Storage::disk('public')->exists($gambar)) {
            return Storage::url($gambar);
        }
        return asset('storage/konser.jpg');
    }
}
