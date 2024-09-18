<?php
namespace App\Tests\Command;

use App\Command\FetchDataCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class FetchDataCommandTest extends KernelTestCase
{
    private $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = self::$kernel->getContainer()->get(FetchDataCommand::class);
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Reset any exception handlers or other global state here
        $this->commandTester = null;
    }

    public function testExecute()
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Data successfully saved', $output);
    }
}