<?php

declare(strict_types=1);

namespace App\Controller;

class HealthController extends AbstractController
{
    public function check()
    {
        return $this->response->json([
            'status' => 'healthy',
            'timestamp' => time(),
        ]);
    }
}
