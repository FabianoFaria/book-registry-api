<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    // user_id is deliberately NOT fillable: it is set through the relationship.
    protected $fillable = ['name', 'isbn', 'value'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'value' => 'decimal:2', // string "12.50", no float rounding
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
