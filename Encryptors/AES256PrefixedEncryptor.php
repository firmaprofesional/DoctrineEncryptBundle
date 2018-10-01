<?php

namespace TDM\DoctrineEncryptBundle\Encryptors;

/**
 * Class for AES-256 Prefixed encryption
 * 
 * @author Errin Pace
 */
class AES256PrefixedEncryptor implements EncryptorInterface {

    const CIPHER = 'aes-256-cbc';

    /**
     * Prefix to indicate if data is encrypted
     * @var string
     */
    private $prefix;

    /**
     * Secret key for aes algorythm
     * @var string
     */
    private $secretKey;

    /**
     * Secret key for aes algorythm
     * @var string
     */
    private $systemSalt;

    /**
     *
     * @var int
     */
    private $iv_size;

    /**
     * Initialization of encryptor
     * @param string $key
     * @param $systemSalt
     * @param $encryptedPrefix
     */
    public function __construct($key, $systemSalt, $encryptedPrefix)
    {
        $this->secretKey = $this->convertKey($key);
        $this->systemSalt = $systemSalt;
        $this->prefix = $encryptedPrefix;
        $this->iv_size = openssl_cipher_iv_length(self::CIPHER);
    }

    /**
     * Implementation of EncryptorInterface encrypt method
     * @param string $data
     * @param bool Deterministic
     * @return string
     */
    public function encrypt($data, $deterministic)
    {
        $iv = $this->determineIV($deterministic);
        // Encrypt plaintext data with given parameters
        $clearData = $this->addZeroPadding($this->systemSalt . $data);
        $encrypted = openssl_encrypt(
            $clearData,
            self::CIPHER,
            $this->secretKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );

        // Encode data with MIME base64
        $base64_encoded = base64_encode($iv . $encrypted);
        // Strip NULL-bytes from the end of the string
        $rtrimmed =  rtrim($base64_encoded, "\0");

        return $this->prefix . $rtrimmed;
    }

    /**
     * Implementation of EncryptorInterface decrypt method
     * @param string $data
     * @param bool Deterministic
     * @return string
     */
    public function decrypt($data, $deterministic)
    {
        // Return data if not annotated as encrypted
        if (strncmp($this->prefix, $data, strlen($this->prefix)) !== 0) {
            return $data;
        }

        // Strip annotation and decode data encoded with MIME base64
        $base64_decoded = base64_decode(substr($data, strlen($this->prefix)));

        // Split Initialization Vector
        $iv = substr($base64_decoded, 0, $this->iv_size);
        $iv_removed = substr($base64_decoded, $this->iv_size);

        // return decrypted, de-salted, and trimed value
        $saltedDecrypted = openssl_decrypt(
            $iv_removed,
            self::CIPHER,
            $this->secretKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );

        $result = rtrim(
            $this->removeSalt($saltedDecrypted),
            "\0"
        );

        return $result;
    }

    /**
     *
     * @param string $secretKey
     * @return string
     */
    private function convertKey($secretKey)
    {
        return pack('H*', hash('sha256', $secretKey));
    }

    /**
     * @return string
     */
    private function randomIV()
    {
        $iv = openssl_random_pseudo_bytes(
            openssl_cipher_iv_length(self::CIPHER)
        );

        return $iv;
    }

    /**
     * Return an initialization vector (IV) from a random source
     * @param bool $deterministic
     * @return string Initialization Vecotr
     */
    private function determineIV($deterministic)
    {
        return $deterministic ? str_repeat("\0", $this->iv_size) : $this->randomIV();
    }

    /**
     * Strips the salt off the decrypted value (if it is present)
     * @param string $saltedDecrypted
     * @return string
     */
    private function removeSalt($saltedDecrypted)
    {
        $systemSaltLength = strlen($this->systemSalt);
        if (substr($saltedDecrypted, 0, $systemSaltLength) === $this->systemSalt) {
            return substr($saltedDecrypted, $systemSaltLength);
        }

        return $saltedDecrypted;
    }

    /**
     * @param $clearData
     * @return string
     */
    private function addZeroPadding($clearData)
    {
        //¿hacen falta 0 al final?
        $length = strlen($clearData) % $this->iv_size;
        if ($length > 0) {
            //cuantos hacen falta
            $length = $this->iv_size - $length;
            //tamaño total hexa
            $length = strlen($clearData) + $length;
            $clearData = str_pad(
                $clearData,
                $length,
                "\0",
                STR_PAD_RIGHT
            );
        }

        return $clearData;
    }

}
