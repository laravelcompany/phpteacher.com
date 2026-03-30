<?php

// enable profiling
xdebug_start_profiling();

// your PHP code here
// stop profiling
xdebug_stop_profiling();

// output profiling results

echo '<pre>';
echo file_get_contents(xdebug_get_profiler_filename());
echo '</pre>';