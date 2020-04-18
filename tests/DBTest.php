<?php 

declare(strict_types=1);

use Config\Services;
use Config\Database;
use Config\Migrations;
use Arifrh\DynaModel\DB;
use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
	protected $loaded = false;

	/**
	 * Should the db be refreshed before
	 * each test?
	 *
	 * @var boolean
	 */
	protected $refresh = true;

	/**
	 * The seed file(s) used for all tests within this test case.
	 * Should be fully-namespaced or relative to $basePath
	 *
	 * @var string|array
	 */
	protected $seed = [
		'Arifrh\DynaModelTests\Database\Seeds\DynaModelTestSeeder'
	];

	/**
	 * The namespace(s) to help us find the migration classes.
	 * Empty is equivalent to running `spark migrate -all`.
	 * Note that running "all" runs migrations in date order,
	 * but specifying namespaces runs them in namespace order (then date)
	 *
	 * @var string|array|null
	 */
	protected $namespace = 'Arifrh\DynaModelTests';

	/**
	 * The path to the seeds directory.
	 * Allows overriding the default application directories.
	 *
	 * @var string
	 */
	protected $basePath = TESTPATH . 'Database';

	/**
	 * The name of the database group to connect to.
	 * If not present, will use the defaultGroup.
	 *
	 * @var string
	 */
	protected $DBGroup = 'tests';

	/**
	 * Our database connection.
	 *
	 * @var BaseConnection
	 */
	protected $db;

	/**
	 * Migration Runner instance.
	 *
	 * @var MigrationRunner|mixed
	 */
	protected $migrations;

	/**
	 * Seeder instance
	 *
	 * @var \CodeIgniter\Database\Seeder
	 */
	protected $seeder;

	/**
	 * Stores information needed to remove any
	 * rows inserted via $this->hasInDatabase();
	 *
	 * @var array
	 */
	protected $insertCache = [];

	//--------------------------------------------------------------------

	/**
	 * Load any database test dependencies.
	 */
	public function loadDependencies()
	{
		if ($this->db === null)
		{
			$this->db = Database::connect($this->DBGroup);
			$this->db->initialize();
		}

		if ($this->migrations === null)
		{
			// Ensure that we can run migrations
			$config          = new Migrations();
			$config->enabled = true;

			$this->migrations = Services::migrations($config, $this->db);
			$this->migrations->setSilent(false);
		}
	}

	public function loadSeeder()
	{
		if ($this->seeder === null)
		{
			$this->seeder = Database::seeder($this->DBGroup);
			$this->seeder->setSilent(true);
		}

		if (! empty($this->seed))
		{
			if (! empty($this->basePath))
			{
				$this->seeder->setPath(rtrim($this->basePath, '/') . '/Seeds');
			}

			$seeds = is_array($this->seed) ? $this->seed : [$this->seed];
			foreach ($seeds as $seed)
			{
				$this->seeder->call($seed);
			}
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Ensures that the database is cleaned up to a known state
	 * before each test runs.
	 *
	 * @throws ConfigException
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if (! $this->loaded)
		{
			Services::autoloader()->addNamespace('Arifrh\DynaModelTests', TESTPATH);
			$this->loaded = true;
		}

		$this->loadDependencies();

		if ($this->refresh === true)
		{
			// If no namespace was specified then rollback/migrate all
			if (empty($this->namespace))
			{
				$this->migrations->setNamespace(null);

				$this->migrations->regress(0, $this->DBGroup);

				$this->migrations->latest($this->DBGroup);
			}

			// Run migrations for each specified namespace
			else
			{
				$namespaces = is_array($this->namespace) ? $this->namespace : [$this->namespace];

				foreach ($namespaces as $namespace)
				{
					$this->migrations->setNamespace($namespace);
					$this->migrations->regress(0, $this->DBGroup);
				}

				foreach ($namespaces as $namespace)
				{
					$this->migrations->setNamespace($namespace);
					$this->migrations->latest($this->DBGroup);
				}
			}
		}

		$this->loadSeeder();
    }

    public function tearDown(): void
    {
		
    }

    public function testDbtable()
    {
		$authors = DB::table('authors');
		$this->assertInstanceOf(\CodeIgniter\Model::class, $authors);
	}
	
	public function testDbNonExistingTable()
    {
        $this->expectException(Arifrh\DynaModel\Exceptions\DBException::class);
        $categories = DB::table('categories');
    }
}