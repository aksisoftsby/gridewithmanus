<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dump = function ($table) {
    echo "=== $table ===\n";
    $cols = DB::select("select column_name, data_type, udt_name from information_schema.columns where table_name='$table' order by ordinal_position");
    foreach ($cols as $c) echo $c->column_name." ".$c->udt_name."\n";
};

$dump('users');
$dump('merchants');
foreach (['drivers', 'kendaraans'] as $t) {
    $n = DB::select("select 1 from information_schema.tables where table_name='$t'");
    if ($n) $dump($t);
}
