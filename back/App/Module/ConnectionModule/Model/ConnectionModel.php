<?php

namespace App\Module\ConnectionModule\Model;

use App\Connection\Connection;
use App\Model\AbstractModel;
use Firebase\JWT\JWT;
use phpDocumentor\Reflection\Types\String_;

class ConnectionModel extends AbstractModel
{

    public function checkIfUserExists(string $email, string $password): array
    {
        $this->send_query('
                        SELECT id_user, password FROM ccp_user
                        WHERE email = ?
                        ',
                        [$email]);
        if ($result = $this->getQuery()->fetch()) {
            if ($password === $result['password'] || password_verify($password, $result['password'])) {
                return $result;
            }
        } else {
            return [];
        }
    }

    public function getUserByEmail(string $email): array
    {
        $this->send_query('
            SELECT id_user FROM ccp_user
            WHERE email = ?
        ',[$email]);

        if ($result = $this->getQuery()->fetch()) {
            return $result;
        } else {
            return [];
        }
    }

    public function checkToken(string $token): bool
    {
        $this->send_query('SELECT token FROM ccp_token
                        WHERE token = ?
                        ',
                        [$token]);
        if ($tokenExists = $this->getQuery()->fetch()) {
            // Update token last update date if it is valid
            $this->send_query('UPDATE ccp_token
                            SET last_update_date = NOW()
                            WHERE token = ?
                            ',
                            [$token]);
            return true;
        } else {
            return false;
        }
    }

    public function generateToken(string $email, string $password, int $id_user): string
    {
        $this->send_query('SELECT token FROM ccp_token
                        WHERE id_user = ?
                        ',
                        [$id_user]);
        $userAlreadyHasAToken = $this->getQuery()->fetch();
        if ($userAlreadyHasAToken) {
            return $userAlreadyHasAToken['token'];
        } else {
            $privateKey = file_get_contents(__DIR__ . '/../../../../keys/private_key.pem');

            $payload = array(
                'email' => $email,
                'password' => $this->checkIfUserExists($email, $password)['password'],
                'time' => new \DateTime('NOW')
            );

            $jwt = JWT::encode($payload, $privateKey, 'RS512');

            $this->send_query('INSERT INTO ccp_token
                            (token, creation_date, last_update_date, id_user)
                            VALUES
                            (?, NOW(), NOW(), ?)
                            ',
                            [$jwt, $id_user]);
        }

        return $jwt;
    }

    public function getIcon(int $idUser, string $path, string $uploadDirectory): string
    {
        return $this->getOnFTP($idUser, $path, $uploadDirectory);
    }

    public function getPersonalInformation(string $email): array
    {
        $this->send_query('
            SELECT id_user, first_name, icon
            FROM ccp_user
            WHERE email LIKE ?
        ',
            [$email]);

        return $this->fetchData(['message' => 'User does not exist']);
    }

    public function getPersonalLobbies(string $email): array
    {
        $this->send_query('
            SELECT id_lobby, label_lobby, description, logo
            FROM ccp_lobby 
            NATURAL JOIN ccp_is_admin cia
            INNER JOIN ccp_user cu on cia.id_user = cu.id_user
            WHERE email = ?
        ',
            [$email]);
        return $this->fetchData(['message' => 'User does not own any lobby']);
    }

    public function newInscription($prenom , $nom , $pseudo , $email , $mdp): string_
    {
        // pas encore de vérification pour l'instant
        $this->send_query('INSERT INTO ccp_user VALUES (?,?,?,?,?)', [$prenom,$nom,$pseudo,$email,$mdp]);
        return $this->fetchData(['message' => 'inscription effectué']);
    }
}
