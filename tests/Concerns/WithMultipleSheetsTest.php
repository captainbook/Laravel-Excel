<?php

namespace Maatwebsite\Excel\Tests\Concerns;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Tests\TestCase;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Tests\Data\Stubs\Database\User;
use Maatwebsite\Excel\Tests\Data\Stubs\SheetWith100Rows;
use Maatwebsite\Excel\Tests\Data\Stubs\SheetForUsersFromView;
use PHPUnit\Framework\Assert;

class WithMultipleSheetsTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/../Data/Stubs/Database/Factories');
    }

    /**
     * @test
     */
    public function can_export_with_multiple_sheets_using_collections()
    {
        $export = new class implements WithMultipleSheets {
            use Exportable;

            /**
             * @return SheetWith100Rows[]
             */
            public function sheets() : array
            {
                return [
                    new SheetWith100Rows('A'),
                    new SheetWith100Rows('B'),
                    new SheetWith100Rows('C'),
                ];
            }
        };

        $export->store('from-view.xlsx');

        $this->assertCount(100, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 0));
        $this->assertCount(100, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 1));
        $this->assertCount(100, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 2));
    }

    /**
     * @test
     */
    public function can_export_multiple_sheets_from_view()
    {
        /** @var Collection|User[] $users */
        $users = factory(User::class)->times(300)->make();

        $export = new class($users) implements WithMultipleSheets {
            use Exportable;

            /**
             * @var Collection
             */
            protected $users;

            /**
             * @param Collection $users
             */
            public function __construct(Collection $users)
            {
                $this->users = $users;
            }

            /**
             * @return SheetForUsersFromView[]
             */
            public function sheets() : array
            {
                return [
                    new SheetForUsersFromView($this->users->forPage(1, 100)),
                    new SheetForUsersFromView($this->users->forPage(2, 100)),
                    new SheetForUsersFromView($this->users->forPage(3, 100)),
                ];
            }
        };

        $export->store('from-view.xlsx');

        $this->assertCount(101, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 0));
        $this->assertCount(101, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 1));
        $this->assertCount(101, $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-view.xlsx', 'Xlsx', 2));
    }

    /**
     * @test
     */
    public function can_import_multiple_sheets()
    {
        $import = new class implements WithMultipleSheets
        {
            use Importable;

            public function sheets(): array
            {
                return [
                    new class implements ToArray
                    {
                        public function array(array $array)
                        {
                            Assert::assertEquals([
                                ['1.A1', '1.B1'],
                                ['1.A2', '1.B2'],
                            ], $array);
                        }
                    },
                    new class implements ToArray
                    {
                        public function array(array $array)
                        {
                            Assert::assertEquals([
                                ['2.A1', '2.B1'],
                                ['2.A2', '2.B2'],
                            ], $array);
                        }
                    }
                ];
            }
        };

        $import->import('import-multiple-sheets.xlsx');
    }

    /**
     * @test
     */
    public function can_import_multiple_sheets_by_sheet_name()
    {
        $import = new class implements WithMultipleSheets
        {
            use Importable;

            public function sheets(): array
            {
                return [
                    'Sheet2' => new class implements ToArray
                    {
                        public function array(array $array)
                        {
                            Assert::assertEquals([
                                ['2.A1', '2.B1'],
                                ['2.A2', '2.B2'],
                            ], $array);
                        }
                    },
                    'Sheet1' => new class implements ToArray
                    {
                        public function array(array $array)
                        {
                            Assert::assertEquals([
                                ['1.A1', '1.B1'],
                                ['1.A2', '1.B2'],
                            ], $array);
                        }
                    },
                ];
            }
        };

        $import->import('import-multiple-sheets.xlsx');
    }
}