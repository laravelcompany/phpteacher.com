<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateReport implements ShouldQueue
{
  use Dispatchable;
  use InteractsWithQueue;
  use Queueable;
  use SerializesModels;

  public function __construct(
      public Bookmark $bookmark
  ) {}

  public function handle()
  {
    // do something here
  }
}