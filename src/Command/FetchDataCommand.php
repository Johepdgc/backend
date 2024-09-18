<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use phpseclib3\Net\SFTP;

#[AsCommand(name: 'app:fetch-data', description: 'Fetch data from API and save as JSON and CSV.')]
class FetchDataCommand extends Command
{
    private $client;
    private $connection;

    public function __construct(HttpClientInterface $client, Connection $connection)
    {
        $this->client = $client;
        $this->connection = $connection; // Inject database connection
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('date', InputArgument::OPTIONAL, 'Date for the data file (YYYYMMDD)', date('Ymd'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $date = $input->getArgument('date');

        // Fetch data from API
        $response = $this->client->request('GET', 'https://dummyjson.com/users');
        if ($response->getStatusCode() !== 200) {
            $io->error('Failed to fetch data from API. Status code: ' . $response->getStatusCode());
            return Command::FAILURE;
        }
        $data = $response->toArray();

        // Ensure the data has a 'users' key
        if (!isset($data['users']) || !is_array($data['users'])) {
            $io->error('Invalid data structure: users key not found or is not an array');
            return Command::FAILURE;
        }

        $users = $data['users'];

        // Initialize counters for summary
        $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
        $ageCounts = [
            '00-10' => ['male' => 0, 'female' => 0, 'other' => 0],
            '11-20' => ['male' => 0, 'female' => 0, 'other' => 0],
            '21-30' => ['male' => 0, 'female' => 0, 'other' => 0],
            '31-40' => ['male' => 0, 'female' => 0, 'other' => 0],
            '41-50' => ['male' => 0, 'female' => 0, 'other' => 0],
            '51-60' => ['male' => 0, 'female' => 0, 'other' => 0],
            '61-70' => ['male' => 0, 'female' => 0, 'other' => 0],
            '71-80' => ['male' => 0, 'female' => 0, 'other' => 0],
            '81-90' => ['male' => 0, 'female' => 0, 'other' => 0],
            '91+' => ['male' => 0, 'female' => 0, 'other' => 0],
        ];
        $cityCounts = [];
        $soCounts = [];

        // Iterate through users to populate counters
        foreach ($users as $user) {
            // Gender count
            $gender = strtolower($user['gender']);
            if (isset($genderCounts[$gender])) {
                $genderCounts[$gender]++;
            } else {
                $genderCounts['other']++;
            }

            // Age count based on user['age']
            $age = $user['age'];
            if ($age >= 0 && $age <= 10) {
                $ageGroup = '00-10';
            } elseif ($age <= 20) {
                $ageGroup = '11-20';
            } elseif ($age <= 30) {
                $ageGroup = '21-30';
            } elseif ($age <= 40) {
                $ageGroup = '31-40';
            } elseif ($age <= 50) {
                $ageGroup = '41-50';
            } elseif ($age <= 60) {
                $ageGroup = '51-60';
            } elseif ($age <= 70) {
                $ageGroup = '61-70';
            } elseif ($age <= 80) {
                $ageGroup = '71-80';
            } elseif ($age <= 90) {
                $ageGroup = '81-90';
            } else {
                $ageGroup = '91+';
            }
            $ageCounts[$ageGroup][$gender]++;

            // City count
            $city = $user['city'] ?? 'Unknown';
            if (!isset($cityCounts[$city])) {
                $cityCounts[$city] = ['male' => 0, 'female' => 0, 'other' => 0];
            }
            $cityCounts[$city][$gender]++;

            // SO count
            $so = $user['so'] ?? 'Unknown';
            if (!isset($soCounts[$so])) {
                $soCounts[$so] = 0;
            }
            $soCounts[$so]++;
        }

        // Create Summary CSV (summary_[YYYYMMDD].csv)
        $summaryFilename = sprintf('summary_%s.csv', $date);
        $summaryFile = fopen($summaryFilename, 'w');

        // Write summary for total number of users
        $userCount = count($users);
        fputcsv($summaryFile, ['Total Users', 'Date']);
        fputcsv($summaryFile, [$userCount, $date]);

        // Write gender summary
        fputcsv($summaryFile, ['Gender', 'Total']);
        foreach ($genderCounts as $gender => $count) {
            fputcsv($summaryFile, [$gender, $count]);
        }

        // Write age summary
        fputcsv($summaryFile, []);
        fputcsv($summaryFile, ['Age', 'Male', 'Female', 'Other']);
        foreach ($ageCounts as $ageRange => $counts) {
            fputcsv($summaryFile, array_merge([$ageRange], $counts));
        }

        // Write city summary
        fputcsv($summaryFile, []);
        fputcsv($summaryFile, ['City', 'Male', 'Female', 'Other']);
        foreach ($cityCounts as $city => $counts) {
            fputcsv($summaryFile, array_merge([$city], $counts));
        }

        // Write SO summary
        fputcsv($summaryFile, []);
        fputcsv($summaryFile, ['SO', 'Total']);
        foreach ($soCounts as $so => $count) {
            fputcsv($summaryFile, [$so, $count]);
        }

        fclose($summaryFile);
        $io->success(sprintf('Summary file created: %s', $summaryFilename));

        // Insert summary data into the database
        $connection = $this->connection; // Get Doctrine's DBAL connection
        $connection->beginTransaction(); // Start a transaction

        try {
            // Insert gender summary into the database
            foreach ($genderCounts as $gender => $count) {
                $connection->insert('summary', [
                    'summary_date' => $date,
                    'category' => 'gender',
                    'subcategory' => $gender,
                    'total' => $count,
                ]);
            }

            // Insert age summary into the database
            foreach ($ageCounts as $ageRange => $counts) {
                foreach ($counts as $gender => $count) {
                    $connection->insert('summary', [
                        'summary_date' => $date,
                        'category' => 'age',
                        'subcategory' => "$ageRange ($gender)",
                        'total' => $count,
                    ]);
                }
            }

            // Insert city summary into the database
            foreach ($cityCounts as $city => $counts) {
                foreach ($counts as $gender => $count) {
                    $connection->insert('summary', [
                        'summary_date' => $date,
                        'category' => 'city',
                        'subcategory' => "$city ($gender)",
                        'total' => $count,
                    ]);
                }
            }

            // Insert SO summary into the database
            foreach ($soCounts as $so => $count) {
                $connection->insert('summary', [
                    'summary_date' => $date,
                    'category' => 'so',
                    'subcategory' => $so,
                    'total' => $count,
                ]);
            }

            $connection->commit(); // Commit the transaction
            $io->success('Summary data successfully inserted into the database.');

        } catch (\Exception $e) {
            $connection->rollBack(); // Roll back the transaction on error
            $io->error('Failed to insert summary data: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // --- SFTP Upload ---
        $sftp = new SFTP($_ENV['SFTP_HOST'], $_ENV['SFTP_PORT']);
        if (!$sftp->login($_ENV['SFTP_USERNAME'], $_ENV['SFTP_PASSWORD'])) {
            $io->error(sprintf('SFTP login failed for host %s and username %s', $_ENV['SFTP_HOST'], $_ENV['SFTP_USERNAME']));
            return Command::FAILURE;
        }

        // List of local files to upload
        $localFiles = [
            sprintf('data_%s.json', $date),
            sprintf('ETL_%s.csv', $date),
            sprintf('summary_%s.csv', $date)
        ];

        // Remote directory path from the .env file
        $remoteDir = $_ENV['SFTP_REMOTE_DIR'];

        // Upload files to SFTP

        foreach ($localFiles as $localFile) {
            $remoteFile = $remoteDir . '/' . basename($localFile); // Set remote file path

            // Upload the file
            if (!$sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE)) {
                $io->error(sprintf('Failed to upload file: %s', $localFile));
                return Command::FAILURE;
            }
        }

        $io->success('All files successfully uploaded to SFTP.');

        return Command::SUCCESS;
    }
}
