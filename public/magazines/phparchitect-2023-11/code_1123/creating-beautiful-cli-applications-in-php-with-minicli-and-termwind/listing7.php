<?php

declare(strict_types=1);

namespace App\Command\Demo;

use App\Command\BaseController;

class TableController extends BaseController
{
    public function handle(): void
    {
        $headers = ['Header1', 'Header2', 'Header3'];
        $rows = [];

        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [
                (string) $i,
                (string) rand(0, 10),
                "other string {$i}",
            ];
        }

        $this->view('table', [
            'headers' => $headers,
            'rows' => $rows
        ]);
    }
}