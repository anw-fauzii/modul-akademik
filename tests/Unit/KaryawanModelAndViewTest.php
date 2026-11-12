<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\KaryawanController;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class KaryawanModelAndViewTest extends TestCase
{
    public function test_foto_url_resolves_from_config_services_kepegawaian(): void
    {
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8090/');
        config()->set('services.kepegawaian.url', null);

        $karyawan = Karyawan::make([
            'nipy' => '22990184-1',
            'nama_lengkap' => 'Iin Solihin',
        ]);

        $this->assertSame(
            'http://192.168.0.111:8090/data-master/karyawan/22990184-1/foto',
            $karyawan->foto_url
        );

        config()->set('services.kepegawaian.app_url', '');
        config()->set('services.kepegawaian.url', 'https://pegawai.example.sch.id/');
        $this->assertSame(
            'https://pegawai.example.sch.id/data-master/karyawan/22990184-1/foto',
            $karyawan->foto_url
        );

        config()->set('services.kepegawaian.url', '');
        $this->assertNull($karyawan->foto_url);
    }

    public function test_show_view_renders_same_origin_foto_proxy_route_without_direct_cross_port_img(): void
    {
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8090');

        $jenjang = JenjangPendidikan::make([
            'nama_jenjang_pendidikan' => 'S1',
        ]);

        $karyawan = Karyawan::make([
            'nipy' => '22990184-1',
            'nama_lengkap' => 'Iin Solihin',
            'gelar' => 'S.Pd',
            'jabatan' => 'Guru Kelas',
            'unit' => 'SD',
            'sertifikasi' => true,
        ]);
        $karyawan->setRelation('user', null);
        $karyawan->setRelation('jenjangPendidikan', $jenjang);

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.karyawan.show', [
                'karyawan' => $karyawan,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $expectedProxyUrl = route('karyawan.foto', $karyawan);

        $this->assertStringContainsString(
            '<img src="'.$expectedProxyUrl.'"',
            $html
        );
        $this->assertStringNotContainsString(
            '<img src="http://192.168.0.111:8090/',
            $html
        );
        $this->assertStringContainsString(
            "this.classList.remove('d-block'); this.classList.add('d-none');",
            $html
        );
    }

    public function test_karyawan_foto_route_is_protected_by_auth_and_sso_middlewares(): void
    {
        $route = app('router')->getRoutes()->getByName('karyawan.foto');

        $this->assertNotNull($route);
        $this->assertSame('data-master/karyawan/{karyawan}/foto', $route->uri());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('sso.check', $route->gatherMiddleware());
        $this->assertContains('nocache', $route->gatherMiddleware());
    }

    public function test_foto_proxy_streams_valid_image_with_optional_api_key_and_bearer_token(): void
    {
        config()->set('app.url', 'http://192.168.0.111:8080');
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8090');
        config()->set('services.kepegawaian.api_key', 'secret-api-key');

        $karyawan = Karyawan::make(['nipy' => '22990184-1']);

        Http::fake([
            'http://192.168.0.111:8090/data-master/karyawan/22990184-1/foto' => Http::response(
                'FAKE-JPEG-BYTES',
                200,
                ['Content-Type' => 'image/jpeg; charset=binary']
            ),
        ]);

        $request = Request::create('http://192.168.0.111:8080/data-master/karyawan/22990184-1/foto', 'GET');
        $session = app('session')->driver();
        $session->put('access_token', 'sso-token-123');
        $request->setLaravelSession($session);

        $response = app(KaryawanController::class)->foto($request, $karyawan);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('FAKE-JPEG-BYTES', $response->getContent());

        Http::assertSent(function (ClientRequest $sent): bool {
            return $sent->url() === 'http://192.168.0.111:8090/data-master/karyawan/22990184-1/foto'
                && $sent->hasHeader('X-API-Key', 'secret-api-key')
                && $sent->hasHeader('Authorization', 'Bearer sso-token-123');
        });
    }

    public function test_foto_proxy_follows_same_origin_image_redirect(): void
    {
        config()->set('app.url', 'http://192.168.0.111:8080');
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8090');
        config()->set('services.kepegawaian.api_key', null);

        $karyawan = Karyawan::make(['nipy' => '22990184-1']);

        Http::fake([
            'http://192.168.0.111:8090/data-master/karyawan/22990184-1/foto' => Http::response(
                '',
                302,
                ['Location' => '/storage/foto/iin.png']
            ),
            'http://192.168.0.111:8090/storage/foto/iin.png' => Http::response(
                'FAKE-PNG-BYTES',
                200,
                ['Content-Type' => 'image/png']
            ),
        ]);

        $request = Request::create('http://192.168.0.111:8080/data-master/karyawan/22990184-1/foto', 'GET');
        $response = app(KaryawanController::class)->foto($request, $karyawan);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame('FAKE-PNG-BYTES', $response->getContent());
    }

    public function test_foto_proxy_rejects_login_redirect_sso_redirect_and_html_responses_with_404(): void
    {
        config()->set('app.url', 'http://192.168.0.111:8080');
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8090');

        $karyawan = Karyawan::make(['nipy' => '22990184-1']);
        $request = Request::create('http://192.168.0.111:8080/data-master/karyawan/22990184-1/foto', 'GET');
        $controller = app(KaryawanController::class);

        $cases = [
            Http::response('', 302, ['Location' => 'http://192.168.0.111:8090/login']),
            Http::response('', 302, ['Location' => 'http://192.168.0.111:8000/oauth/authorize']),
            Http::response('<html>Login</html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']),
            fn () => throw new ConnectionException('Connection refused'),
        ];

        foreach ($cases as $stub) {
            Http::fake([
                'http://192.168.0.111:8090/data-master/karyawan/22990184-1/foto' => $stub,
            ]);

            try {
                $controller->foto($request, $karyawan);
                $this->fail('Expected NotFoundHttpException when remote photo is invalid.');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_foto_proxy_prevents_self_loop_to_current_app(): void
    {
        config()->set('app.url', 'http://192.168.0.111:8080');
        config()->set('services.kepegawaian.app_url', 'http://192.168.0.111:8080');

        $karyawan = Karyawan::make(['nipy' => '22990184-1']);
        $request = Request::create('http://192.168.0.111:8080/data-master/karyawan/22990184-1/foto', 'GET');

        Http::fake();

        $this->expectException(NotFoundHttpException::class);

        try {
            app(KaryawanController::class)->foto($request, $karyawan);
        } finally {
            Http::assertNothingSent();
        }
    }
}
