<?php
// Disposable UI verification only. Never uses the shop database.
$root = getenv('PAOLO_FILTER_QA_ROOT');
if (! $root || ! preg_match('/^paolo-filter-preview-[a-f0-9]{32}$/', basename($root))) throw new RuntimeException('Temporary preview root required.');
$project = dirname(__DIR__, 2);
foreach (['APP_ENV'=>'local', 'APP_DEBUG'=>'true', 'APP_URL'=>'http://127.0.0.1:8877', 'APP_KEY'=>'base64:'.base64_encode(str_repeat('q',32)), 'DB_CONNECTION'=>'sqlite', 'DB_DATABASE'=>$root.'/preview.sqlite', 'DB_URL'=>'', 'SESSION_DRIVER'=>'file', 'SESSION_COOKIE'=>'paolo_filter_preview', 'CACHE_STORE'=>'file', 'MAIL_MAILER'=>'array', 'QUEUE_CONNECTION'=>'sync'] as $name=>$value) { putenv($name.'='.$value); $_ENV[$name]=$_SERVER[$name]=$value; }
require $project.'/vendor/autoload.php';
$app = require $project.'/bootstrap/app.php';
$app->useStoragePath($root.'/storage');
if (PHP_SAPI === 'cli-server') {
    file_put_contents($root.'/requests.log', $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].PHP_EOL, FILE_APPEND);
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/' && is_file($project.'/public'.$path) && realpath($project.'/public'.$path) && str_starts_with(realpath($project.'/public'.$path), realpath($project.'/public').DIRECTORY_SEPARATOR)) return false;
    $app->handleRequest(Illuminate\Http\Request::capture());
} else {
    foreach (['framework/views','framework/sessions','framework/cache/data','logs'] as $dir) { if (!is_dir($root.'/storage/'.$dir)) mkdir($root.'/storage/'.$dir,0777,true); }
    touch($root.'/preview.sqlite');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]);
    $admin = App\Models\User::factory()->create(['name'=>'Preview Owner', 'username'=>'preview', 'role'=>'Admin', 'password'=>Illuminate\Support\Facades\Hash::make('PreviewOnly123!')]);
    $category = App\Models\Category::create(['Name'=>'Automotive Parts']);
    foreach (['Active','Archived','Low Stock','Out of Stock'] as $name) App\Models\Status::create(['Name'=>$name]);
    $status = App\Models\Status::where('Name','Active')->value('ID');
    foreach (range(1,20) as $i) {
        $product = App\Models\Product::create(['Name'=>sprintf('Engine Oil %02d',$i), 'Category_ID'=>$category->ID, 'Status_ID'=>$status]);
        App\Models\StockIn::create(['Product_ID'=>$product->ID,'User_ID'=>$admin->id,'Quantity'=>$i,'Remaining_Quantity'=>$i,'Retail_Price'=>100+$i,'Cost_Price'=>50,'Condition'=>'Good','Has_Expiration'=>false]);
    }
    echo "Disposable filter preview initialized.\n";
}
