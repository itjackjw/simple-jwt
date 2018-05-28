<?php

namespace Qbhy\SimpleJwt;

use Qbhy\SimpleJwt\Exceptions\InvalidTokenException;
use Qbhy\SimpleJwt\Exceptions\SignatureException;

/**
 * User: qbhy
 * Date: 2018/5/28
 * Time: 下午12:06
 */
class JWT
{
    /** @var array */
    protected $headers = [
        'alg' => 'jwt',
    ];

    /** @var array */
    protected $payload = [];

    /** @var Encoder */
    protected $encoder;

    /** @var Encrypter */
    protected $encrypter;

    /**
     * JWT constructor.
     *
     * @param array            $headers
     * @param array            $payload
     * @param string|Encrypter $secret
     * @param null|Encoder     $encoder
     */
    public function __construct(array $headers, array $payload, $secret, $encoder = null)
    {
        $this->setHeaders($this->headers + $headers);
        $this->setPayload($payload);
        $this->setEncoder($encoder ?? new Base64Encoder());
        $this->setEncrypter(AbstractEncrypter::formatEncrypter($secret, Md5Encrypter::class));

    }

    public function token(): string
    {
        $signatureString = $this->generateSignatureString();

        $signature = $this::getEncoder()->encode(
            $this->encrypter->signature($signatureString)
        );

        return "{$signatureString}.{$signature}";
    }

    /**
     * @param Encrypter $encrypter
     *
     * @return static
     */
    public function setEncrypter(Encrypter $encrypter): JWT
    {
        $this->encrypter = $encrypter;

        return $this;
    }

    /**
     * @return Encoder
     */
    public function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @param Encoder $encoder
     *
     * @return JWT
     */
    public function setEncoder(Encoder $encoder): JWT
    {
        $this->encoder = $encoder;

        return $this;
    }

    public function generateSignatureString(): string
    {
        $headersString = $this::getEncoder()->encode(json_encode($this->headers));
        $payloadString = $this::getEncoder()->encode(json_encode($this->payload));

        return "{$headersString}.{$payloadString}";
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return static
     */
    public function setHeaders(array $headers): JWT
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     *
     * @return static
     */
    public function setPayload(array $payload): JWT
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @param string           $token
     * @param string|Encrypter $secret
     * @param Encoder          $encoder
     *
     * @return static
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\SignatureException
     */
    public static function decryptToken(string $token, $secret, Encoder $encoder = null)
    {
        $arr = explode('.', $token);

        if (count($arr) !== 3) {
            throw new InvalidTokenException('Invalid token');
        }

        $encrypter = AbstractEncrypter::formatEncrypter($secret, Md5Encrypter::class);
        $encoder   = $encoder ?? new Base64Encoder();

        $signatureString = "{$arr[0]}.{$arr[1]}";

        if ($encrypter->check($signatureString, $encoder->decode($arr[2]))) {
            return new static(
                json_decode($encoder->decode($arr[0]), true),
                json_decode($encoder->decode($arr[1]), true),
                $encrypter
            );
        }

        throw new SignatureException('invalid signature');
    }

}