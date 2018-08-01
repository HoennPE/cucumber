<?php
declare(strict_types=1);

namespace adeynes\cucumber\log;

use adeynes\cucumber\Cucumber;
use adeynes\cucumber\event\CucumberEvent;
use adeynes\cucumber\utils\ds\Stack;
use adeynes\cucumber\utils\MessageFactory;

final class LogManager
{

    /** @var Cucumber */
    private $plugin;

    /** @var string */
    private $dir;

    /** @var Stack<Logger> */
    private $loggers;

    /** @var string */
    private $global_template;

    /** @var string */
    private $time_format;

    public function __construct(Cucumber $plugin)
    {
        $this->plugin = $plugin;
        $this->dir = $this->getPlugin()->getConfig()->getNested('log.path') ?? 'log/';
        @mkdir($this->getDirectory());
        $this->loggers = new Stack;
        [$this->global_template, $this->time_format] = [$this->getPlugin()->getMessage('log.templates.global'),
                                                        $this->getPlugin()->getMessage('time-format') ?? 'Y-m-d\TH:i:s'];
    }

    public function getPlugin(): Cucumber
    {
        return $this->plugin;
    }

    public function getDirectory(): string
    {
        return $this->getPlugin()->getDataFolder() . $this->dir;
    }

    public function log(string $message): void
    {
        foreach ($this->loggers as $logger) {
            if ($logger->log($message) === false) break;
        }
    }

    /**
     * Pushes a logger to the top of the logger stack
     * @param Logger $logger
     * @return LogManager For chaining
     */
    public function addLogger(Logger $logger): self
    {
        $this->loggers->push($logger);
        return $this;
    }

    /**
     * Creates a log message for the given event
     * Uses the template for the event type
     * specified in messages.yml and populates
     * it with the data in CucumberEvent::getData()
     * @param CucumberEvent $ev
     * @return string
     */
    public function formatEventMessage(CucumberEvent $ev): string
    {
        return MessageFactory::format(
            $this->global_template,
            $this->generateGlobalTemplateData($ev->getTemplate(), $ev->getType(), $ev->getData())
        );
    }

    /**
     * Generates the data for the global template
     * @param string $template The event message template
     * @param string $type
     * @param array $data
     * @return array
     */
    private function generateGlobalTemplateData(string $template, string $type, array $data): array
    {
        return [
            'time' => date($this->time_format),
            'type' => $type,
            '...' => MessageFactory::format(
                $template,
                $data
            )
        ];
    }

}