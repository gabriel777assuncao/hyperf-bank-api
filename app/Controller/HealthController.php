<?php

declare(strict_types=1);

namespace App\Controller;

class HealthController extends AbstractController
{
    public function check()
    {
        return $this->response->json([
            'status' => 'ok',
            'timestamp' => time(),
        ]);
    }
}
