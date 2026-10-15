<?php
// Verify the real private transfer against an isolated SQL Server database.
try {
    require dirname(__DIR__,2).'/vendor/autoload.php';
    $app = require dirname(__DIR__,2).'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $target = getenv('SQLSERVER_TEST_DATABASE');
    $root = getenv('PAOLO_ADMIN_QA_ROOT');
    if (!preg_match('/^paolo_admin_[a-f0-9]{8}_sqlserver_test$/', $target ?: '') || !preg_match('/^paolo-admin-preview-[a-f0-9]{32}$/', basename($root ?: ''))) throw new RuntimeException('Disposable database and storage required.');
    $source = Illuminate\Support\Facades\DB::connection();
    if ($source->getDatabaseName() === $target) throw new RuntimeException('Source and target must differ.');
    $tables = ['users','tbl_category','tbl_product','tbl_stock_in','tbl_sale','tbl_sold_item'];
    $before = array_map(fn($table) => $source->table($table)->count(), $tables);
    $transfer = app(App\Services\LaptopAdminTransfer::class);
    $data = $transfer->read();
    $sourceAdmin = $source->table('users')->where('username',$data['username'])->first();
    if (!$sourceAdmin || !hash_equals($sourceAdmin->password,$data['password_hash'])) throw new RuntimeException('Administrator transfer does not match source.');
    $contents = Illuminate\Support\Facades\File::get($transfer->path());
    $app->useStoragePath($root.'/storage');
    Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($transfer->path()));
    Illuminate\Support\Facades\File::put($transfer->path(),$contents);
    config(['database.connections.deployment_check'=>array_replace(config('database.connections.sqlsrv'),['database'=>$target,'url'=>null]),'database.default'=>'deployment_check']);
    Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);
    $code = Illuminate\Support\Facades\Artisan::call('app:initialize-laptop',['--no-interaction'=>true]);
    if ($code !== 0) throw new RuntimeException('Fresh setup failed.');
    $admin = App\Models\User::sole();
    if ($admin->username !== $data['username'] || !$admin->isAdmin() || !hash_equals($admin->getRawOriginal('password'),$data['password_hash'])) throw new RuntimeException('Administrator login was not retained.');
    foreach (array_slice($tables,1) as $table) if (Illuminate\Support\Facades\DB::table($table)->exists()) throw new RuntimeException('Business records found in fresh database.');
    if (App\Models\Status::count() !== 4 || App\Models\PaymentMethod::count() !== 2 || App\Models\AccountRecoveryCode::count() !== 0) throw new RuntimeException('Unexpected setup records.');
    App\Models\Category::create(['Name'=>'New laptop data']);
    $admin->update(['password'=>Illuminate\Support\Facades\Hash::make('TemporaryTestPassword2026!')]);
    Illuminate\Support\Facades\File::put($transfer->path(),$contents);
    $code = Illuminate\Support\Facades\Artisan::call('app:initialize-laptop',['--no-interaction'=>true]);
    if ($code !== 0 || App\Models\Category::count() !== 1 || !Illuminate\Support\Facades\Hash::check('TemporaryTestPassword2026!',$admin->fresh()->password)) throw new RuntimeException('Rerun did not preserve new data and password.');
    if ($before !== array_map(fn($table) => $source->table($table)->count(),$tables) || !hash_equals($source->table('users')->where('id',$sourceAdmin->id)->value('password'),$sourceAdmin->password)) throw new RuntimeException('Source records changed.');
    echo json_encode(['result'=>'passed','admin_login_retained'=>true,'old_business_records_imported'=>0,'other_accounts_imported'=>0,'rerun_preserves_new_data'=>true,'source_database_unchanged'=>true]).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR,'Administrator deployment verification failed ('.get_class($error).'). No credentials are printed.'.PHP_EOL);
    exit(1);
}
