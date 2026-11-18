<?php
/**
 * Creates and signs latest.json for the one-click upgrade. Run by the owner/coordinator, never by the plugin.
 *
 *   php build/sign-release.php genkey <private-key-file>        makes a key pair; prints the PUBLIC key (put it in Model/Upgrade/ReleaseKey.php)
 *   php build/sign-release.php sign <private-key-file> <zip> <url> <min_from|-> <notes.json> > latest.json
 *
 * The private key file is written with mode 0600 and must be kept OUTSIDE the repository. Nothing secret is ever printed.
 */
declare(strict_types=1);

require __DIR__ . '/../Model/Upgrade/UpgradeRejected.php';
require __DIR__ . '/../Model/Upgrade/ReleaseManifest.php';
require __DIR__ . '/../Model/Upgrade/ReleaseManifestParser.php';

use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifest;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifestParser;

$cmd = $argv[1] ?? '';
if ($cmd === 'genkey' && isset($argv[2])) {
    if (file_exists($argv[2])) {
        fwrite(STDERR, "Refusing to overwrite {$argv[2]}\n");
        exit(1);
    }
    $pair = sodium_crypto_sign_keypair();
    file_put_contents($argv[2], base64_encode(sodium_crypto_sign_secretkey($pair)));
    chmod($argv[2], 0600);
    echo "PUBLIC_KEY_BASE64=" . base64_encode(sodium_crypto_sign_publickey($pair)) . "\n";
    exit(0);
}
if ($cmd === 'sign' && count($argv) === 7) {
    [, , $keyFile, $zip, $url, $minFrom, $notesFile] = $argv;
    $zipText = new ZipArchive();
    if ($zipText->open($zip) !== true) {
        fwrite(STDERR, "Cannot open zip\n");
        exit(1);
    }
    $composer = json_decode((string) $zipText->getFromName('FreeShippingBar/composer.json'), true);
    $zipText->close();
    $data = [
        'format' => ReleaseManifest::FORMAT,
        'version' => (string) ($composer['version'] ?? ''),
        'url' => $url,
        'sha256' => hash_file('sha256', $zip),
        'bytes' => filesize($zip),
        'notes' => json_decode((string) file_get_contents($notesFile), true) ?: [],
    ];
    if ($minFrom !== '-') {
        $data['min_from'] = $minFrom;
    }
    $secret = base64_decode(trim((string) file_get_contents($keyFile)), true);
    $data['signature'] = base64_encode(sodium_crypto_sign_detached((new ReleaseManifestParser())->canonical($data), $secret));
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}
fwrite(STDERR, "Usage: see the comment at the top of this file.\n");
exit(2);
