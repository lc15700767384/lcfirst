# laravel-auth
 laravel有自带的登录验证。建立对应的表和配置一些文件就能够使用。
##### Config配置
在 config/auth.php中，配置示例如下：
```
'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
 ],
 
'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],
		
		'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\Auth\User::class,
            'table' => 'blog_users',
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Auth\Admin::class,
            'table' => 'blog_admins',
        ],
    ],
```
##### laravel 脚手架实现认证体系
进到项目目录创建脚手架，执行：
```
php artisan make:auth
```
routes/web.php路由下代码 ```Auth::routes();``` 已有认证的默认的 ```'guard' => 'web' ```所有认证路由
database/migrations目录下已有创建表的代码
```
public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
```
项目目录下执行：`php artisan migrate` ，把表加入数据库
![file](/storage/uploads//20230325/JY8JD5llnr7ITW85cXk8xROGKX5wEhLkEGRcS1Rl.png)
上面错误只需要在 `Providers/AppServiceProvider.php boot()` 中添加 `Schema::defaultStringLength(191);`
控制器在 `\app\Http\Controllers\Auth` 目录下。

###### 上面 `guards` 配置了后台用户认证，下面仿照进行用户认证
路由配置：
```
//后台注册只在登录后暂未配置路由
Route::group(['prefix' => 'admin', ], function () {
    Route::get('/login', 'Auth\Admin\LoginController@showAdminLoginForm');
    Route::post('/login', 'Auth\Admin\LoginController@login');
    Route::post('/logout', 'Auth\Admin\LoginController@logout')->name('admin.logout');

    Route::group(['middleware' => 'auth:admin', ], function () {
		    //后台认证路由
				
		});
});
```
复制 `app/Http/Controllers\Auth` 下的 `LoginController.php和RegisterController.php` 到新建目录 `app/Http/Controllers/Auth/Admin` 下。
LoginController.php
```
public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }
		
		protected function guard()
    {
        return Auth::guard('admin');
    }
		
		public function showAdminLoginForm()
    {
        return view('admin.auth.login', ['guard' => 'admin']);
    }
		
		public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->get('remember'))) {

            return redirect()->intended('/admin');
        }
        return back()->withInput($request->only('email', 'remember'))->withErrors(['password' => 'Invalid account or password.']);
    }
		
		public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->forget($this->guard()->getName());

        $request->session()->regenerate();

        return redirect('/admin');
    }
```

RegisterController.php
```
protected $redirectTo = '/admin';

public function __construct()
    {
        $this->middleware('guest:admin');
    }
		
		protected function guard()
    {
        return Auth::guard('admin');
    }
		
		protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
		
		public function showAdminRegisterForm()
    {
        return view('admin.auth.register', ['guard' => 'admin']);
    }
		
		protected function create(Request $request)
    {
        $this->validator($request->all())->validate();
        $admin = Admin::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);
        return redirect()->intended('admin/login');
    }
```
模板将 `resources/views/auth` 目录复制到 `resources/views/admin` 
中间件 `RedirectIfAuthenticated.php` 改一下：
```
public function handle($request, Closure $next, $guard = null)
    {
        if ($guard == "admin") {
            if (Auth::guard($guard)->check()) {
                return redirect('/admin');
            }
        } elseif($guard == null) {
            if (Auth::guard($guard)->check() &&
                ! $request->is('email/*', 'logout', 'password/*')) {
                return redirect('/');
            }
        }

        return $next($request);
    }
```
新建admins表：
```
php artisan make:migration create_admins_table --create=admins
```
`database/migrations` 目录下新建admins表文件下编辑：
```
public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
```
再`php artisan migrate`，创建表到数据库。
`php artisan make:seeder AdminsTableSeeder`创建填充后台用户数据的文件
```
public function run()
    {
        DB::table('admins')->insert([
            'name'  => 'admin',
            'email' => 'test@admin.com',
            'password' => bcrypt('12345678'),
        ]);
    }
```
`DatabaseSeeder.php`文件改为
```
public function run()
    {
        $this->call(AdminTableSeeder::class);
    }
```
`php artisan db:seed --class=AdminsTableSeeder` 执行单个数据填充命令
新建`App/Models/Auth/Admin.php`,将下面代码写入文件：
```
<?php

namespace App\Models\Auth;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $guard = 'admin';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
```
上面是laravel用户认证流程，感谢您的认可，欢迎关注我的博客[zanealancy](https://blog.ebeast.club/)。 