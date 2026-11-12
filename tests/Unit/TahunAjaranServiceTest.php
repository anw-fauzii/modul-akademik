<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\TahunAjaranController;
use App\Http\Requests\Admin\DataMaster\TahunAjaranRequest;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\TahunAjaranService;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TahunAjaranServiceTest extends TestCase
{
    public function test_siswa_reset_attributes_match_fillable_columns_on_siswa_model(): void
    {
        $service = new TahunAjaranService;
        $attributes = $service->siswaResetAttributes();

        $this->assertSame([
            'akademik_master_kelas_id' => null,
            'karyawan_nipy' => null,
            'akademik_master_ekstrakuler_id' => null,
            'akademik_master_jemputan_id' => null,
        ], $attributes);

        $fillable = (new Siswa)->getFillable();
        foreach (array_keys($attributes) as $column) {
            $this->assertContains($column, $fillable);
        }
    }

    public function test_build_siswa_reset_query_without_tahun_ajaran_targets_all_assigned_students(): void
    {
        $service = new TahunAjaranService;
        $builder = $service->buildSiswaResetQuery();
        $sql = $builder->toSql();

        $this->assertSame('akademik_master_siswa', $builder->getModel()->getTable());
        $this->assertStringContainsString('akademik_master_kelas_id', $sql);
        $this->assertStringContainsString('karyawan_nipy', $sql);
        $this->assertStringContainsString('akademik_master_ekstrakuler_id', $sql);
        $this->assertStringContainsString('akademik_master_jemputan_id', $sql);
        $this->assertSame(4, substr_count(strtolower($sql), 'is not null'));
        $this->assertSame([], $builder->getBindings());
    }

    public function test_build_siswa_reset_query_with_tahun_ajaran_scopes_via_kelas_ekstrakurikuler_and_jemputan(): void
    {
        $service = new TahunAjaranService;
        $builder = $service->buildSiswaResetQuery(15);
        $sql = $builder->toSql();

        $this->assertStringContainsString('akademik_master_kelas', $sql);
        $this->assertStringContainsString('akademik_master_ekstrakuler', $sql);
        $this->assertStringContainsString('akademik_master_jemputan', $sql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertSame([15, 15, 15], $builder->getBindings());
    }

    public function test_store_creates_tahun_ajaran_and_resets_siswa_columns_inside_transaction(): void
    {
        $service = new TahunAjaranService;
        $created = null;

        $events = $this->recordDatabaseEvents(function () use ($service, &$created): void {
            $created = $service->store([
                'nama_tahun_ajaran' => '2027/2028',
                'semester' => '1',
            ]);
        });

        $this->assertInstanceOf(TahunAjaran::class, $created);
        $this->assertSame('transaction_start', $events[0]['type']);
        $this->assertSame('insert', $events[1]['type']);
        $this->assertStringContainsString('akademik_master_tahun_ajaran', $events[1]['sql']);

        $this->assertSame('update', $events[2]['type']);
        $this->assertStringContainsString('akademik_master_siswa', $events[2]['sql']);
        $this->assertStringContainsString('akademik_master_kelas_id', $events[2]['sql']);
        $this->assertStringContainsString('karyawan_nipy', $events[2]['sql']);
        $this->assertStringContainsString('akademik_master_ekstrakuler_id', $events[2]['sql']);
        $this->assertStringContainsString('akademik_master_jemputan_id', $events[2]['sql']);
        $this->assertSame(4, substr_count(strtolower($events[2]['sql']), 'is not null'));
        $this->assertSame([null, null, null, null], array_slice($events[2]['bindings'], 0, 4));
        $this->assertSame('transaction_commit', $events[3]['type']);
    }

    public function test_delete_active_tahun_ajaran_resets_all_siswa_columns_before_deleting_record(): void
    {
        $service = new TahunAjaranService;
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 7;
        $tahunAjaran->exists = true;

        $deleted = false;
        $events = $this->recordDatabaseEvents(function () use ($service, $tahunAjaran, &$deleted): void {
            $deleted = $service->delete($tahunAjaran);
        });

        $this->assertTrue($deleted);
        $this->assertSame('transaction_start', $events[0]['type']);
        $this->assertSame('update', $events[1]['type']);
        $this->assertStringContainsString('akademik_master_siswa', $events[1]['sql']);
        $this->assertSame(4, substr_count(strtolower($events[1]['sql']), 'is not null'));
        $this->assertSame([null, null, null, null], array_slice($events[1]['bindings'], 0, 4));

        $this->assertSame('delete', $events[2]['type']);
        $this->assertStringContainsString('akademik_master_tahun_ajaran', $events[2]['sql']);
        $this->assertSame([7], $events[2]['bindings']);
        $this->assertSame('transaction_commit', $events[3]['type']);
    }

    public function test_delete_inactive_tahun_ajaran_resets_only_linked_siswa_before_deleting_record(): void
    {
        $service = new TahunAjaranService;
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $tahunAjaran->id = 9;
        $tahunAjaran->exists = true;

        $deleted = false;
        $events = $this->recordDatabaseEvents(function () use ($service, $tahunAjaran, &$deleted): void {
            $deleted = $service->delete($tahunAjaran);
        });

        $this->assertTrue($deleted);
        $this->assertSame('transaction_start', $events[0]['type']);
        $this->assertSame('update', $events[1]['type']);
        $this->assertStringContainsString('akademik_master_siswa', $events[1]['sql']);
        $this->assertStringContainsString('akademik_master_kelas', $events[1]['sql']);
        $this->assertStringContainsString('akademik_master_ekstrakuler', $events[1]['sql']);
        $this->assertStringContainsString('akademik_master_jemputan', $events[1]['sql']);
        $this->assertSame([9, 9, 9], array_slice($events[1]['bindings'], -3));
        $this->assertSame([null, null, null, null], array_slice($events[1]['bindings'], 0, 4));

        $this->assertSame('delete', $events[2]['type']);
        $this->assertStringContainsString('akademik_master_tahun_ajaran', $events[2]['sql']);
        $this->assertSame([9], $events[2]['bindings']);
        $this->assertSame('transaction_commit', $events[3]['type']);
    }

    public function test_delete_returns_false_without_queries_when_model_does_not_exist(): void
    {
        $service = new TahunAjaranService;
        $unsaved = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'is_active' => true,
        ]);

        $deleted = true;
        $events = $this->recordDatabaseEvents(function () use ($service, $unsaved, &$deleted): void {
            $deleted = $service->delete($unsaved);
        });

        $this->assertFalse($deleted);
        $this->assertSame([], $events);
    }

    public function test_update_and_toggle_active_do_not_reset_siswa_columns(): void
    {
        $service = new TahunAjaranService;
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => false,
        ]);
        $tahunAjaran->id = 4;
        $tahunAjaran->exists = true;

        $events = $this->recordDatabaseEvents(function () use ($service, $tahunAjaran): void {
            $service->update($tahunAjaran, [
                'nama_tahun_ajaran' => '2026/2027',
                'semester' => '2',
            ]);
            $service->toggleActive($tahunAjaran);
        });

        foreach ($events as $event) {
            if (isset($event['sql'])) {
                $this->assertStringNotContainsString('akademik_master_siswa', $event['sql']);
            }
        }
    }

    public function test_controller_delegates_store_and_destroy_to_tahun_ajaran_service(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2027/2028',
            'semester' => '1',
            'is_active' => false,
        ]);
        $tahunAjaran->id = 12;

        $service = $this->createMock(TahunAjaranService::class);
        $service->expects($this->once())
            ->method('store')
            ->with(['nama_tahun_ajaran' => '2027/2028', 'semester' => '1'])
            ->willReturn($tahunAjaran);
        $service->expects($this->once())
            ->method('delete')
            ->with($tahunAjaran)
            ->willReturn(true);

        $request = TahunAjaranRequest::create(route('tahun-ajaran.store'), 'POST', [
            'nama_tahun_ajaran' => '2027/2028',
            'semester' => '1',
        ]);
        $validator = app('validator')->make($request->all(), [
            'nama_tahun_ajaran' => 'required|string',
            'semester' => 'required|string',
        ]);
        $validator->validate();
        $request->setValidator($validator);

        $controller = new TahunAjaranController($service);

        $storeResponse = $controller->store($request);
        $destroyResponse = $controller->destroy($tahunAjaran);

        $this->assertTrue($storeResponse->isRedirect(route('tahun-ajaran.index')));
        $this->assertTrue($destroyResponse->isRedirect(route('tahun-ajaran.index')));
    }

    /**
     * Menjalankan closure dengan koneksi database pencatat query tanpa membutuhkan ekstensi pdo_sqlite.
     *
     * @return list<array{type: string, sql?: string, bindings?: array<int, mixed>}>
     */
    private function recordDatabaseEvents(callable $callback): array
    {
        $connection = new class(static fn () => throw new RuntimeException('No PDO required'), 'testing', '', ['driver' => 'mysql']) extends MySqlConnection
        {
            /** @var list<array{type: string, sql?: string, bindings?: array<int, mixed>}> */
            public array $recordedQueries = [];

            public function getLastInsertId($sequence = null): string|int|null
            {
                return 1;
            }

            public function transaction(Closure $callback, $attempts = 1): mixed
            {
                $this->recordedQueries[] = ['type' => 'transaction_start'];
                $result = $callback($this);
                $this->recordedQueries[] = ['type' => 'transaction_commit'];

                return $result;
            }

            public function insert($query, $bindings = [], $sequence = null): bool
            {
                $this->recordedQueries[] = [
                    'type' => 'insert',
                    'sql' => $query,
                    'bindings' => $bindings,
                ];

                return true;
            }

            public function update($query, $bindings = []): int
            {
                $this->recordedQueries[] = [
                    'type' => 'update',
                    'sql' => $query,
                    'bindings' => $bindings,
                ];

                return 1;
            }

            public function delete($query, $bindings = []): int
            {
                $this->recordedQueries[] = [
                    'type' => 'delete',
                    'sql' => $query,
                    'bindings' => $bindings,
                ];

                return 1;
            }
        };

        $fakeResolver = new class($connection) implements ConnectionResolverInterface
        {
            public function __construct(private ConnectionInterface $connection) {}

            public function connection($name = null): ConnectionInterface
            {
                return $this->connection;
            }

            public function getDefaultConnection(): string
            {
                return 'testing';
            }

            public function setDefaultConnection($name): void {}

            /**
             * @param  array<int, mixed>  $parameters
             */
            public function __call(string $method, array $parameters): mixed
            {
                return $this->connection->{$method}(...$parameters);
            }
        };

        $previousDb = DB::getFacadeRoot();
        $previousModelResolver = Model::getConnectionResolver();

        DB::swap($fakeResolver);
        Model::setConnectionResolver($fakeResolver);

        try {
            $callback();
        } finally {
            DB::swap($previousDb);
            if ($previousModelResolver !== null) {
                Model::setConnectionResolver($previousModelResolver);
            }
        }

        return $connection->recordedQueries;
    }
}
