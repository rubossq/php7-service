<?php
namespace Famous\Lib\Utils;
class Secure{
	
	/* encrypt got key */
	public static function encrypt($s){
		return strtoupper(
					bin2hex(
						mcrypt_encrypt(
							Constant::SECURE_CIPHER, Constant::SECURE_KEY, $s, Constant::SECURE_MODE, Constant::SECURE_IV
						)
					)
			   );
	}

	/* decrypt got key */
	public static function decrypt($s){
		return mcrypt_decrypt(
					Constant::SECURE_CIPHER, Constant::SECURE_KEY, pack('H*', $s), Constant::SECURE_MODE, Constant::SECURE_IV
			   );
	}
}

?>