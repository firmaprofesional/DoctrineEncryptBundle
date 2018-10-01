#Configuration Reference

All available configuration options are listed below with their default values.

``` yaml
fp_doctrine_encrypt:  
# Secret key for encrypt algorithm. All secret key checks are encryptor tasks only.
    secret_key:           ~ # Required
# ORM and MongoDB are supported using "orm" or "odm" respectively.
    db_driver:            ~ # Required 
# You must provide a system salt.  This value is prefixed to data prior to 
# being encrypted.  This prevents rainbow table attacks.
    system_salt:          ~ # Required 
# You can provide your own prefix value or use the default, which is "_ENC_".  
# This is prefixed (in plain text) to every value that is encrypted.  The system 
# will not decrypt a value which does not have the prefix.
    encrypted_prefix:     ~ # Optional 
# You can optionally provide a service as an encryptor.  The service must 
# implement EncryptorServiceInterface.  If you do not provide your own service, 
# it will use the default service which is AES256PrefixedEncryptor
    encryptor_service:    ~
```
