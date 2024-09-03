<?php

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class CustomPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        return $this->make_password($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if ('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        // return $this->make_password($plainPassword) === $hashedPassword;
        return $this->verify_Password($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }

    private function make_password($password)
    {
        $algorithm = "pbkdf2_sha256";
        $iterations = 600000;

        $newSalt = random_bytes(6);
        $newSalt = base64_encode($newSalt);

        $hash = hash_pbkdf2("SHA256", $password, $newSalt, $iterations, 0, true);
        $toDBStr = $algorithm . "$" . $iterations . "$" . $newSalt . "$" . base64_encode($hash);

        // This string is to be saved into DB, just like what Django generate.
        return $toDBStr;
    }

    private function verify_Password($dbString, $password)
    {
        $pieces = explode("$", $dbString);
        $iterations = (int) $pieces[1];
        $salt = $pieces[2];
        $old_hash = $pieces[3];

        if ($iterations <= 0) {
            return false;
        }
        $hash = hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true);
        $hash = base64_encode($hash);

        if ($hash == $old_hash) {
            // login ok.
            return true;
        } else {
            //login fail       
            return false;
        }
    }
}
