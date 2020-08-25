<?php
declare(strict_types=1);

namespace adeynes\cucumber\command;

use adeynes\cucumber\Cucumber;
use adeynes\cucumber\mod\Mute;
use adeynes\cucumber\utils\CucumberException;
use adeynes\cucumber\utils\CucumberPlayer;
use adeynes\parsecmd\command\blueprint\CommandBlueprint;
use adeynes\parsecmd\command\CommandParser;
use adeynes\parsecmd\command\ParsedCommand;
use pocketmine\command\CommandSender;

class MuteCommand extends CucumberCommand
{

    public function __construct(Cucumber $plugin, CommandBlueprint $blueprint)
    {
        parent::__construct(
            $plugin,
            $blueprint,
            'mute',
            'cucumber.command.mute',
            'Mute a player',
            '/mute <player> [reason] [-d <duration>]'
        );
    }

    public function _execute(CommandSender $sender, ParsedCommand $command): bool
    {
        [$target_name, $reason] = $command->get(['player', 'reason']);
        $target_name = strtolower($target_name);
        // TODO: what happens if no reason: '' or null?
        if ($reason === '') {
            $reason = $this->getPlugin()->getMessage('moderation.mute.mute.default-reason');
        }
        $duration = $command->getFlag('duration');
        $expiration = $duration ? CommandParser::parseDuration($duration) : null;

        $mute = function () use ($sender, $target_name, $reason, $expiration) {
            try {
                $mute = new Mute($target_name, $reason, $expiration, $sender->getName(), time());
                $mute_data = $mute->getFormatData();
                $this->getPlugin()->getPunishmentRegistry()->addMute($mute);
                $mute->save($this->getPlugin()->getConnector());

                if ($target = CucumberPlayer::getOnlinePlayer($target_name)) {
                    $this->getPlugin()->formatAndSend($target, 'moderation.mute.mute.message', $mute_data);
                }

                $this->getPlugin()->formatAndSend($sender, 'success.mute', $mute_data);
                return true;
            } catch(CucumberException $exception) {
                $sender->sendMessage($exception->getMessage());
                return false;
            }
        };

        $this->doIfTargetExists($mute, $sender, $target_name);
        return true;
    }

}