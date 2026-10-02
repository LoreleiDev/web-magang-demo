<?php

namespace App\Mail;

use App\Models\VerifikasiMateri;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email hasil pemeriksaan materi untuk guru pembuat materi (bagian 11.2).
 */
class MasukanMateriMail extends Mailable
{
    public function __construct(public VerifikasiMateri $verifikasi) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[MagangBridge] Masukan untuk materi \"{$this->verifikasi->materi->judul}\"",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.masukan-materi',
            with: [
                'judul' => $this->verifikasi->materi->judul,
                'perusahaan' => $this->verifikasi->perusahaan->nama,
                'pemeriksa' => $this->verifikasi->pemeriksa->name,
                'hasil' => $this->verifikasi->hasil->label(),
                'masukan' => $this->verifikasi->masukan,
                'urlEdit' => route('guru.materi.edit', $this->verifikasi->materi_id),
            ],
        );
    }
}
