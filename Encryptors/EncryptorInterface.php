<?php

namespace FP\DoctrineEncryptBundle\Encryptors;

/**
 * Encryptor interface for encryptors
 * 
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
interface EncryptorInterface {

    /**
     * Initialize the encryptor.
     * @param string $secretKey - The encryption key
     * @param string $systemSalt - The system-wide salt (for prevention of 
     * rainbow table attacks)
     * @param string $encryptedPrefix - Allows the system to identify when a 
     * value is encrypted or not and how to handle it.
     */
    public function __construct($secretKey, $systemSalt, $encryptedPrefix);

    /**
     * Must accept data and return encrypted data 
     * @param string $data The data to be encrypted
     * @param bool $deterministic Should the data use a shared initialization 
     * vector or should the initialization vector be determined individually for 
     * each data element.  
     * TRUE - Use the same initialization vector.  
     * FALSE - Use a new initialization vector for each.
     */
    public function encrypt($data, $deterministic);

    /**
     * Must accept data and return decrypted data 
     * @param string $data The data to be decrypted
     * @param bool $deterministic Should the data use a shared initialization 
     * vector or should the initialization vector be determined individually for 
     * each data element.  
     * TRUE - Use the same initialization vector.  
     * FALSE - Use a new initialization vector for each.
     */
    public function decrypt($data, $deterministic);
}
