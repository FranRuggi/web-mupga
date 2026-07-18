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
    // Si RECLAMOS_R2_PUBLIC_URL viene sin esquema (ej. "reclamos.mupga.com.ar"
    // en vez de "https://reclamos.mupga.com.ar"), las URLs resultantes se
    // interpretan como rutas RELATIVAS dentro de <img src>, no como URLs
    // absolutas — rompe la carga embebida aunque la URL "andaría" pegada
    // directo en la barra de direcciones. Normalizamos acá para no depender
    // de que el .env esté cargado con el formato exacto.
    public static function normalizePublicUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

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
