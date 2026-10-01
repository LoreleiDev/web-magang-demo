<?php

use App\Enums\JenisMedia;
use App\Support\MediaLink;

test('ID YouTube diambil dari berbagai format link', function (string $url) {
    expect(MediaLink::ambilId(JenisMedia::Youtube, $url))->toBe('ecCuyq-Wprc');
})->with([
    'https://www.youtube.com/watch?v=ecCuyq-Wprc',
    'https://youtube.com/watch?v=ecCuyq-Wprc&t=42s',
    'https://m.youtube.com/watch?v=ecCuyq-Wprc',
    'https://youtu.be/ecCuyq-Wprc',
    'https://youtu.be/ecCuyq-Wprc?si=abc',
    'https://www.youtube.com/shorts/ecCuyq-Wprc',
    'https://www.youtube.com/embed/ecCuyq-Wprc',
    '  https://youtu.be/ecCuyq-Wprc  ',
]);

test('link video selain YouTube ditolak', function (string $url) {
    expect(MediaLink::ambilId(JenisMedia::Youtube, $url))->toBeNull();
})->with([
    'https://vimeo.com/123456',
    'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view',
    'https://youtube.com.palsu.id/watch?v=ecCuyq-Wprc',
    'https://www.youtube.com/watch?v=pendek',
    'javascript:alert(1)',
    'bukan link',
]);

test('ID Google Drive diambil dari link Drive dan Docs', function (string $url, string $id) {
    expect(MediaLink::ambilId(JenisMedia::Dokumen, $url))->toBe($id)
        ->and(MediaLink::ambilId(JenisMedia::Gambar, $url))->toBe($id);
})->with([
    ['https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing', '1AbCdEfGhIjKlMnOp'],
    ['https://drive.google.com/open?id=1AbCdEfGhIjKlMnOp', '1AbCdEfGhIjKlMnOp'],
    ['https://drive.google.com/uc?id=1AbCdEfGhIjKlMnOp&export=download', '1AbCdEfGhIjKlMnOp'],
    ['https://docs.google.com/document/d/1AbCdEfGhIjKlMnOp/edit', '1AbCdEfGhIjKlMnOp'],
    ['https://docs.google.com/presentation/d/1AbCdEfGhIjKlMnOp/edit#slide=id.p', '1AbCdEfGhIjKlMnOp'],
]);

test('link gambar/dokumen selain Google Drive ditolak', function (string $url) {
    expect(MediaLink::ambilId(JenisMedia::Dokumen, $url))->toBeNull();
})->with([
    'https://www.youtube.com/watch?v=ecCuyq-Wprc',
    'https://dropbox.com/s/abc/file.pdf',
    'https://drive.google.com.palsu.id/file/d/1AbCdEfGhIjKlMnOp/view',
    'https://drive.google.com/drive/folders',
]);

test('URL embed dan URL buka dibentuk dari ID', function () {
    expect(MediaLink::urlEmbed(JenisMedia::Youtube, 'ecCuyq-Wprc'))->toBe('https://www.youtube-nocookie.com/embed/ecCuyq-Wprc')
        ->and(MediaLink::urlEmbed(JenisMedia::Dokumen, 'abc123XYZ_-'))->toBe('https://drive.google.com/file/d/abc123XYZ_-/preview')
        ->and(MediaLink::urlBuka(JenisMedia::Gambar, 'abc123XYZ_-'))->toBe('https://drive.google.com/file/d/abc123XYZ_-/view');
});
