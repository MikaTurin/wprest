<?php
namespace WP;


class Tracer
{
    protected $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function isCli()
    {
        return 'cli' === PHP_SAPI;
    }

    public function show()
    {
        $is = $this->isCli();
        if ($is) $e = PHP_EOL; else $e = '<br>';

        echo PHP_EOL.PHP_EOL;
        echo $e.$e.number_format(microtime(true) - $this->startTime, 8).$e;
        echo number_format(memory_get_peak_usage(true) / 1024 / 1024).' Mb'.$e.$e;
        if (!$is) echo '<pre>';
        print_r(get_included_files());
        if (!$is) echo '</pre>';
    }

}