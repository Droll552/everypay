<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\CreateChargeHandler;
use App\Application\Command\SendChargeReportHandler;
use App\Application\Query\GetChargesForPeriodHandler;
use App\Application\ChargeReportBuilder;
use App\Infrastructure\Console\SendReportCommand;
use App\Infrastructure\Http\ChargeHandler;
use App\Infrastructure\Http\AuthMiddleware;
use App\Infrastructure\Http\Router;
use App\Infrastructure\LogMailer;
use App\Infrastructure\Persistence\SqliteChargeRepository;
use App\Infrastructure\Persistence\SqliteMerchantRepository;
use App\Infrastructure\PSP\FakePaypal;
use App\Infrastructure\PSP\FakeStripe;
use App\Infrastructure\PSP\PspRegistry;
use App\Domain\Merchant\PspType;
use PDO;
use RuntimeException;

/**
 * Minimal service container / composition root.
 * All wiring is explicit — no reflection, no magic.
 */
final class Container
{
    private PDO $pdo;
    private PspRegistry $pspRegistry;
    private SqliteMerchantRepository $merchantRepository;
    private SqliteChargeRepository $chargeRepository;
    private LogMailer $mailer;
    private AuthMiddleware $authMiddleware;
    private CreateChargeHandler $createChargeHandler;
    private SendChargeReportHandler $sendReportHandler;

    public function __construct(private readonly array $config)
    {
        $this->pdo = $this->buildPdo();

        $this->merchantRepository = new SqliteMerchantRepository($this->pdo);
        $this->chargeRepository = new SqliteChargeRepository($this->pdo);

        $this->pspRegistry = new PspRegistry();
        $this->pspRegistry->register(PspType::FakeStripe, new FakeStripe());
        $this->pspRegistry->register(PspType::FakePaypal, new FakePaypal());

        $logPath = $this->config['mail_log_path'] ?? '/var/log/app/mail.log';
        $this->mailer = new LogMailer($logPath);

        $this->authMiddleware = new AuthMiddleware($this->merchantRepository);
        $this->createChargeHandler = new CreateChargeHandler(
            $this->merchantRepository,
            $this->chargeRepository,
            $this->pspRegistry,
        );

        $queryHandler = new GetChargesForPeriodHandler($this->chargeRepository);
        $reportBuilder = new ChargeReportBuilder();

        $this->sendReportHandler = new SendChargeReportHandler(
            $this->merchantRepository,
            $queryHandler,
            $reportBuilder,
            $this->mailer,
        );
    }

    public function getRouter(): Router
    {
        $router = new Router();
        $chargeHandler = new ChargeHandler($this->createChargeHandler, $this->authMiddleware);

        $router->add('POST', '/charges', fn($req) => $chargeHandler->handle($req));

        return $router;
    }

    public function getSendReportCommand(): SendReportCommand
    {
        return new SendReportCommand($this->sendReportHandler);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getMerchantRepository(): SqliteMerchantRepository
    {
        return $this->merchantRepository;
    }

    public function getChargeRepository(): SqliteChargeRepository
    {
        return $this->chargeRepository;
    }

    public function getMailer(): LogMailer
    {
        return $this->mailer;
    }

    public function getCreateChargeHandler(): CreateChargeHandler
    {
        return $this->createChargeHandler;
    }

    public function getSendReportHandler(): SendChargeReportHandler
    {
        return $this->sendReportHandler;
    }

    private function buildPdo(): PDO
    {
        $dsn = $this->config['db_dsn'] ?? throw new RuntimeException('Missing db_dsn config.');

        $pdo = new PDO($dsn, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }
}
