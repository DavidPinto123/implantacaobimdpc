<?php

namespace App\Support\Livewire;

use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Livewire\invade;

/**
 * Cloudflare R2 is S3-compatible, but it does not fully support S3 ACLs.
 * Livewire's default S3 signer includes an ACL header, which can cause uploads
 * to fail on R2. This signer omits ACL while keeping the rest of the behavior.
 */
class R2GenerateSignedUploadUrl extends GenerateSignedUploadUrl
{
    public function forLocal()
    {
        return URL::temporarySignedRoute(
            'livewire.upload-file',
            now()->addMinutes(FileUploadConfiguration::maxUploadTime()),
        );
    }

    public function forS3($file, $visibility = 'private')
    {
        $storage = FileUploadConfiguration::storage();

        $driver = $storage->getDriver();

        // Flysystem V2+ doesn't allow direct access to adapter, so we need to invade instead.
        $adapter = invade($driver)->adapter;

        // Flysystem V2+ doesn't allow direct access to client, so we need to invade instead.
        $client = invade($adapter)->client;

        // Flysystem V2+ doesn't allow direct access to bucket, so we need to invade instead.
        $bucket = invade($adapter)->bucket;

        $fileType = $file->getMimeType();
        $fileHashName = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);
        $path = FileUploadConfiguration::path($fileHashName);

        // NOTE: Intentionally omit 'ACL' for R2 compatibility.
        $command = $client->getCommand('putObject', array_filter([
            'Bucket' => $bucket,
            'Key' => $path,
            'ContentType' => $fileType ?: 'application/octet-stream',
            'CacheControl' => null,
            'Expires' => null,
        ]));

        $signedRequest = $client->createPresignedRequest(
            $command,
            '+' . FileUploadConfiguration::maxUploadTime() . ' minutes',
        );

        $uri = $signedRequest->getUri();

        if (filled($url = $storage->getConfig()['temporary_url'] ?? null)) {
            $uri = invade($storage)->replaceBaseUrl($uri, $url);
        }

        return [
            'path' => $fileHashName,
            'url' => (string) $uri,
            'headers' => array_merge(
                $signedRequest->getHeaders(),
                ['Content-Type' => $fileType ?: 'application/octet-stream'],
            ),
        ];
    }
}

