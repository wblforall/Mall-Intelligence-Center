<?php

namespace App\Controllers\Api;

use App\Models\ApiTokenModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends Controller
{
    protected array $apiUser = [];

    protected function json(mixed $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setJSON($data);
    }

    protected function error(string $message, int $status = 400): ResponseInterface
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }

    protected function success(mixed $data, string $message = 'OK'): ResponseInterface
    {
        return $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function requireAuth(): bool
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            $this->json(['success' => false, 'message' => 'Unauthorized.'], 401)->send();
            return false;
        }

        $token = substr($header, 7);
        $user  = (new ApiTokenModel())->findUser($token);

        if (! $user) {
            $this->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401)->send();
            return false;
        }

        $this->apiUser = $user;
        return true;
    }
}
