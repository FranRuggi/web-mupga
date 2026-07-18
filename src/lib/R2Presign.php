<?php
/**
 * Presigned URLs (PUT) para Cloudflare R2 — AWS Signature V4 implementado
 * a mano, sin AWS SDK (el proyecto no usa composer). R2 es S3-compatible:
 * mismo algoritmo que S3, con region fija 'auto' y service 's3'.
 *
 * Path-style request: https://<endpoint>/<bucket>/<key>
 */

class R2Presign
{
    public static function presignPut(
        string $endpoint,
        string $bucket,
        string $accessKey,
        string $secretKey,
        string $objectKey,
        string $contentType,
        int $expiresSeconds = 300
    ): string {
        $region  = 'auto';
        $service = 's3';
        $host    = parse_url($endpoint, PHP_URL_HOST);

        $now       = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $amzDate   = $now->format('Ymd\THis\Z');
        $dateStamp = $now->format('Ymd');

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $canonicalUri = '/' . $bucket . '/' . implode('/', array_map('rawurlencode', explode('/', $objectKey)));

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => "{$accessKey}/{$credentialScope}",
            'X-Amz-Date'          => $amzDate,
            'X-Amz-Expires'       => (string) $expiresSeconds,
            'X-Amz-SignedHeaders' => 'content-type;host',
        ];
        ksort($queryParams, SORT_STRING);

        $queryParts = [];
        foreach ($queryParams as $k => $v) {
            $queryParts[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $canonicalQueryString = implode('&', $queryParts);

        // Firmamos content-type: el PUT real tiene que mandar exactamente este
        // header, así el whitelist de tipos queda reforzado por la firma y no
        // solo por la validación en este endpoint.
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\n";
        $signedHeaders     = 'content-type;host';

        $canonicalRequest = implode("\n", [
            'PUT',
            $canonicalUri,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            'UNSIGNED-PAYLOAD',
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate     = hash_hmac('sha256', $dateStamp, "AWS4{$secretKey}", true);
        $kRegion   = hash_hmac('sha256', $region, $kDate, true);
        $kService  = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning  = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return rtrim($endpoint, '/') . $canonicalUri . '?' . $canonicalQueryString . '&X-Amz-Signature=' . $signature;
    }
}
