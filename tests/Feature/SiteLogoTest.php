<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteLogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingSeeder::class, \Database\Seeders\ParkSeeder::class, UserSeeder::class]);
        $this->admin = User::where('role', 'admin')->first();
    }

    private function save(array $extra = [])
    {
        return $this->actingAs($this->admin)->put('/admin/settings', $extra + [
            'admin_email' => 'admin@example.com', 'admin_phone' => '0772123456', 'usd_rate' => 3700, 'logo_show_name' => 1,
        ]);
    }

    public function test_admin_can_upload_hide_name_and_remove_logo(): void
    {
        $default = strtok(site_logo(), '?'); // whatever logo is active before an upload (e.g. public/images/logo.png)
        $this->get('/')->assertSee($default);

        $this->save(['site_logo' => UploadedFile::fake()->image('our-logo.png', 256, 256)])->assertSessionHas('success');
        $path = Setting::get('site_logo');
        $this->assertFileExists(public_path($path));
        $this->get('/')->assertSee($path)->assertSee('brand-text');

        $this->save(['logo_show_name' => 0]);
        $this->get('/')->assertSee($path)->assertDontSee('class="brand-text"', false);

        $this->save(['remove_logo' => 1]);
        $this->assertNull(Setting::get('site_logo'));
        $this->assertFileDoesNotExist(public_path($path));
        $this->get('/')->assertSee($default)->assertDontSee($path);
    }

    public function test_svg_logos_are_refused(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->save(['site_logo' => $svg])->assertSessionHasErrors('site_logo');
        $this->assertNull(Setting::get('site_logo'));
    }

    protected function tearDown(): void
    {
        foreach (File::glob(public_path('uploads/site/our-logo-*')) as $f) {
            File::delete($f);
        }
        parent::tearDown();
    }
}
