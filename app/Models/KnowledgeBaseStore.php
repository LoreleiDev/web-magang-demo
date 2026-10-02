<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Gemini File Search store per cakupan knowledge base.
 * Cakupan: "sekolah:{kode program keahlian}" atau "industri:{perusahaan_id}".
 *
 * @property int $id
 * @property string $cakupan
 * @property string $nama_store Nama resource Gemini, mis. "fileSearchStores/abc".
 */
#[Table('knowledge_base_store')]
#[Fillable(['cakupan', 'nama_store'])]
class KnowledgeBaseStore extends Model
{
    public static function cakupanDokumen(DokumenKnowledgeBase $dokumen): string
    {
        return $dokumen->jenis->value.':'.($dokumen->program_keahlian ?? $dokumen->perusahaan_id);
    }
}
