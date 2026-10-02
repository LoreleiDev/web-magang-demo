<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $siswa_id
 * @property list<array{peran: string, isi: string, sumber?: list<string>}> $pesan peran: siswa | mentor
 */
#[Table('chat_ai_mentor')]
#[Fillable(['siswa_id', 'pesan'])]
class ChatAiMentor extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pesan' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
