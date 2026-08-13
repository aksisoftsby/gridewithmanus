<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== distinct merchants.city counts ===\n";
$cities = DB::select("select city, count(*) c from merchants group by city order by c desc limit 30");
foreach ($cities as $c) echo $c->city." => ".$c->c."\n";

echo "=== kota table sample ===\n";
$k = DB::select("select id, nama from kota_kabupatens where nama ilike '%bandung%' limit 5");
foreach ($k as $x) echo $x->id." ".$x->nama."\n";

echo "=== provinsi count ===\n";
echo DB::table('provinsis')->count()."\n";
echo "=== kota count ===\n";
echo DB::table('kota_kabupatens')->count()."\n";
echo "=== users with role MERCHANT/DRIVER + counts ===\n";
$roles = DB::select("select role, count(*) c from users group by role");
foreach ($roles as $r) echo $r->role." => ".$r->c."\n";
