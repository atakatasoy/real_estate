<?php

declare(strict_types = 1);

namespace App\Core\Auth;

use \Firebase\JWT\SignatureInvalidException;
use \Firebase\JWT\BeforeValidException;
use \Firebase\JWT\ExpiredException;
use \Firebase\JWT\JWT;

use Illuminate\Support\Facades\Cookie;

use App\Core\Auth\Exceptions\UnauthenticatedUserException;
use App\Core\Auth\Exceptions\InvalidTokenException;
use \Exception;

use App\Models\User;

/**
 * Responsible of issuing and validating tokens and logging in users
 * 
 * 
 * This is an oversimplified implementation. Advanced one would be implemented using built-in custom auth drivers
 */
class TokenHandler {
    /**
     * Represents the seconds that needs to be pass for token invalidation
     * @var int
     */
    private $expiresIn;

    /**
     * Represents the JWT secret that application uses to sign tokens
     * @var string
     */
    private $secret;

    /**
     * Represents the token issuer
     * @var string
     */
    private $issuer;

    public function __construct($secret, $expiresIn, $issuer)
    {
        if(!is_int($expiresIn)){
            $expiresIn = (int)$expiresIn;
        }

        $this->expiresIn = $expiresIn;
        $this->secret = $secret;
        $this->issuer = $issuer;
    }

    /**
     * Issues a token cookie for user
     * @param User $user
     * @return string
     */
    public function issue(User $user)
    {
        return tap(
            $this->createToken($user), 
            fn($token) => Cookie::queue('token', $token, 120)
        );
    }

    /**
     * Makes the required validations for given token and returns the user id on success
     * @param string $token
     * @throws InvalidTokenExceptionuse
     * @return int 
     */
    protected function validate(string $token)
    {
        try{
            $payload = JWT::decode($token, $this->secret, array_keys(JWT::$supported_algs));
        }catch(Exception $e){
            throw new InvalidTokenException();
        }

        $invalid =  !isset($payload->iss) || $payload->iss != $this->issuer;
        // $invalid &= !isset($payload->iat) || $payload->iat > time();
        // $invalid &= !isset($payload->exp) || $payload->exp < time();
        $invalid &= !isset($payload->sub);

        if($invalid) throw new InvalidTokenException();

        return $payload->sub;
    }

    /**
     * Creates a token for given user
     * @param User $user
     * @return string
     */
    private function createToken(User $user)
    {
        $payload = [
            'iss' => $this->issuer,
            'iat' => time(),
            'exp' => time() + $this->expiresIn,
            'sub' => $user->id
        ];

        return JWT::encode($payload, $this->secret);
    }

    /**
     * Logs in a user using given token
     * 
     * @param string $token
     * @throws InvalidTokenException
     * @throws UnauthenticatedUserException
     * @return User
     */
    public function attemptToLogin()
    {
        if(!$token = Cookie::get('token')){
            throw new UnauthenticatedUserException();
        }

        $userId = $this->validate($token);

        return User::find($userId);
    }
}