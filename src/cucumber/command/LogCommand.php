<?php
declare(strict_types=1);

namespace cucumber\command;

use cucumber\Cucumber;
use cucumber\utils\MessageFactory;
use pocketmine\command\CommandSender;

class LogCommand extends CucumberCommand
{

    public function __construct(Cucumber $plugin)
    {
        parent::__construct($plugin, 'log', 'cucumber.command.log', 'Log a message',
            1, '/log <message>');
    }

    public function _execute(CommandSender $sender, ParsedCommand $command): bool
    {
        [$message] = $command->get([0, -1]);
        $this->getPlugin()->getLogManager()->log($message);

        $sender->sendMessage(
            MessageFactory::format(
                $this->getPlugin()->getMessage('success.log'),
                ['message' => $message]
            )
        );

        return true;
    }

}