<?php

declare(strict_types=1);

namespace Chassis\Framework\Loaders;

use Chassis\Support\Env;
use Dotenv\Dotenv;
use Throwable;

class Environment
{
    protected string $filePath;
    protected ?string $fileName;

    /**
     * Create a new loads environment variables instance.
     *
     * @param string $path
     * @param string|null $name
     *
     * @return void
     */
    public function __construct(string $path, ?string $name = null)
    {
        $this->filePath = $path;
        $this->fileName = $name;
    }

    /**
     * Setup the environment variables.
     *
     * If no environment file exists, we continue silently.
     *
     * @return void
     */
    public function bootstrap()
    {
        try {
            $this->dotEnv()->load();
        } catch (Throwable $reason) {
            $this->writeErrorAndDie([$reason->getMessage()]);
        }
    }

    /**
     * Create a Dotenv instance.
     *
     * @return Dotenv
     */
    protected function dotEnv(): Dotenv
    {
        return Dotenv::create(
            Env::getRepository(),
            $this->filePath,
            $this->fileName
        );
    }

    /**
     * Write the error information to the screen and exit.
     *
     * @param string[] $errors
     *
     * @return void
     */
    protected function writeErrorAndDie(array $errors): void
    {
        $fileResource = fopen("php://stderr", "a");
        if ($fileResource !== false) {
            foreach ($errors as $error) {
                fwrite($fileResource, $error);
            }
            fclose($fileResource);
        }

        exit(1);
    }
}
